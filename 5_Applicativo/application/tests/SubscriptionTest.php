<?php

use controller\Subscription;
use models\Subscriptions;

require_once __DIR__ . '/support/CreatesTestData.php';

class SubscriptionTest extends ControllerTestCase
{
    use CreatesTestData;

    private $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = $this->createUser();
        $_SESSION['user'] = $this->user;
    }

    protected function tearDown(): void
    {
        $this->cleanTestData();
        $_POST = [];
        $_SESSION = [];
        parent::tearDown();
    }

    private function validPost(array $override = []): array
    {
        return array_merge([
            'name'               => 'UnitTest abbonamento nuovo',
            'price'              => '9,90',
            'frequency_months'   => '1',
            'first_payment_date' => '2026-01-05',
        ], $override);
    }

    // ---------------------------------------------------------------- accesso

    public function testConstructorRedirectsWhenNotLogged(): void
    {
        $_SESSION = [];

        $this->expectException(RedirectException::class);
        new Subscription();
    }

    // ------------------------------------------------------------ index/create

    public function testIndexRendersList(): void
    {
        $this->createSubscription($this->user);

        $subscription = new Subscription();
        $output = $this->assertNoRedirect(function () use ($subscription) {
            $subscription->index();
        });

        $this->assertNotEmpty($output);
    }

    public function testCreateRendersForm(): void
    {
        $subscription = new Subscription();
        $output = $this->assertNoRedirect(function () use ($subscription) {
            $subscription->create();
        });

        $this->assertNotEmpty($output);
    }

    // ------------------------------------------------------------------ store

    public function testStoreSubscription(): void
    {
        $_POST = $this->validPost();

        $subscription = new Subscription();
        $this->assertRedirectsTo(URL . 'subscription/index', function () use ($subscription) {
            $subscription->store();
        });

        $this->assertSame('Abbonamento creato con successo', $_SESSION['success']);

        $saved = Subscriptions::where('user_id', $this->user->id)
            ->where('name', 'UnitTest abbonamento nuovo')
            ->first();

        $this->assertNotNull($saved);
        $this->assertEqualsWithDelta(9.90, (float) $saved->price, 0.001);   // la virgola diventa punto
        $this->assertEquals(1, $saved->frequency_months);
        $this->assertSame('2026-01-05', $saved->first_payment_date);
        $this->assertTrue((bool) $saved->is_active);
    }

    public function testStoreSubscriptionWithInvalidData(): void
    {
        $_POST = $this->validPost([
            'price'              => 'abc',
            'frequency_months'   => '0',
            'first_payment_date' => '05/01/2026',
        ]);

        $subscription = new Subscription();
        $this->assertRedirectsTo(URL . 'subscription/create', function () use ($subscription) {
            $subscription->store();
        });

        $this->assertArrayHasKey('price', $_SESSION['errors']);
        $this->assertArrayHasKey('frequency_months', $_SESSION['errors']);
        $this->assertArrayHasKey('first_payment_date', $_SESSION['errors']);
        $this->assertArrayHasKey('old', $_SESSION);
        $this->assertSame(0, Subscriptions::where('user_id', $this->user->id)->count());
    }

    public function testStoreSubscriptionWithZeroPrice(): void
    {
        $_POST = $this->validPost(['price' => '0']);

        $subscription = new Subscription();
        $this->assertRedirectsTo(URL . 'subscription/create', function () use ($subscription) {
            $subscription->store();
        });

        $this->assertArrayHasKey('price', $_SESSION['errors']);
    }

    public function testStoreSubscriptionWithImpossibleDate(): void
    {
        // Formato giusto ma il 30 febbraio non esiste
        $_POST = $this->validPost(['first_payment_date' => '2026-02-30']);

        $subscription = new Subscription();
        $this->assertRedirectsTo(URL . 'subscription/create', function () use ($subscription) {
            $subscription->store();
        });

        $this->assertArrayHasKey('first_payment_date', $_SESSION['errors']);
    }

    // ------------------------------------------------------------------- edit

    public function testEditRendersForm(): void
    {
        $existing = $this->createSubscription($this->user);
        $_POST['id_subscription'] = $existing->id;

        $subscription = new Subscription();
        $output = $this->assertNoRedirect(function () use ($subscription) {
            $subscription->edit();
        });

        $this->assertNotEmpty($output);
    }

    public function testEditNonExistingSubscriptionRedirects(): void
    {
        $_POST['id_subscription'] = -1;

        $subscription = new Subscription();
        $this->assertRedirectsTo(URL . 'subscription/index', function () use ($subscription) {
            $subscription->edit();
        });
    }

    public function testEditSubscriptionOfAnotherUserRedirects(): void
    {
        $existing = $this->createSubscription($this->user);
        $_POST['id_subscription'] = $existing->id;

        $_SESSION['user'] = $this->createUser();

        $subscription = new Subscription();
        $this->assertRedirectsTo(URL . 'subscription/index', function () use ($subscription) {
            $subscription->edit();
        });
    }

    // ----------------------------------------------------------------- update

    public function testUpdateSubscription(): void
    {
        $existing = $this->createSubscription($this->user);

        $_POST = $this->validPost([
            'id_subscription'    => $existing->id,
            'name'               => 'UnitTest abbonamento modificato',
            'price'              => '12.99',
            'frequency_months'   => '3',
            'first_payment_date' => '2026-02-10',
        ]);

        $subscription = new Subscription();
        $this->assertRedirectsTo(URL . 'subscription/index', function () use ($subscription) {
            $subscription->update();
        });

        $this->assertSame('Abbonamento modificato con successo', $_SESSION['success']);

        $updated = Subscriptions::find($existing->id);
        $this->assertSame('UnitTest abbonamento modificato', $updated->name);
        $this->assertEqualsWithDelta(12.99, (float) $updated->price, 0.001);
        $this->assertEquals(3, $updated->frequency_months);
        $this->assertSame('2026-02-10', $updated->first_payment_date);
    }

    public function testUpdateSubscriptionWithInvalidData(): void
    {
        $existing = $this->createSubscription($this->user);

        $_POST = $this->validPost([
            'id_subscription' => $existing->id,
            'price'           => 'x',
        ]);

        $subscription = new Subscription();
        $this->assertRedirectsTo(URL . 'subscription/edit/' . $existing->id, function () use ($subscription) {
            $subscription->update();
        });

        $this->assertArrayHasKey('price', $_SESSION['errors']);

        // Il record non deve essere cambiato
        $this->assertSame('UnitTest abbonamento', Subscriptions::find($existing->id)->name);
    }

    public function testUpdateNonExistingSubscription(): void
    {
        $_POST = $this->validPost(['id_subscription' => -1]);

        $subscription = new Subscription();
        $this->assertRedirectsTo(URL . 'subscription/index', function () use ($subscription) {
            $subscription->update();
        });
    }

    public function testUpdateSubscriptionOfAnotherUserIsDenied(): void
    {
        $existing = $this->createSubscription($this->user);

        $_POST = $this->validPost([
            'id_subscription' => $existing->id,
            'name'            => 'UnitTest modifica non autorizzata',
        ]);

        $_SESSION['user'] = $this->createUser();

        $subscription = new Subscription();
        $this->assertRedirectsTo(URL . 'subscription/index', function () use ($subscription) {
            $subscription->update();
        });

        $this->assertSame('UnitTest abbonamento', Subscriptions::find($existing->id)->name);
    }

    public function testStoreSubscriptionWithoutFrequencyField(): void
    {
        $_POST = $this->validPost();
        unset($_POST['frequency_months']);

        $subscription = new Subscription();
        $this->assertRedirectsTo(URL . 'subscription/create', function () use ($subscription) {
            $subscription->store();
        });

        $this->assertArrayHasKey('frequency_months', $_SESSION['errors']);
    }

    // ----------------------------------------------------------------- delete

    public function testDeleteSubscriptionIsSoftDelete(): void
    {
        $existing = $this->createSubscription($this->user);
        $_POST['id_subscription'] = $existing->id;

        $subscription = new Subscription();
        $this->assertRedirectsTo(URL . 'subscription/index', function () use ($subscription) {
            $subscription->delete();
        });

        $this->assertSame('Abbonamento eliminato con successo', $_SESSION['success']);

        $deleted = Subscriptions::find($existing->id);
        $this->assertNotNull($deleted, 'Il record deve restare nel DB');
        $this->assertFalse((bool) $deleted->is_active);
    }

    public function testDeleteNonExistingSubscription(): void
    {
        $_POST['id_subscription'] = -1;

        $subscription = new Subscription();
        $this->assertRedirectsTo(URL . 'subscription/index', function () use ($subscription) {
            $subscription->delete();
        });
    }

    public function testDeleteSubscriptionOfAnotherUserIsDenied(): void
    {
        $existing = $this->createSubscription($this->user);
        $_POST['id_subscription'] = $existing->id;

        $_SESSION['user'] = $this->createUser();

        $subscription = new Subscription();
        $this->assertRedirectsTo(URL . 'subscription/index', function () use ($subscription) {
            $subscription->delete();
        });

        $this->assertTrue((bool) Subscriptions::find($existing->id)->is_active);
    }
}
