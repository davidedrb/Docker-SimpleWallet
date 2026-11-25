<?php

use controller\Payment;
use models\Payments;

require_once __DIR__ . '/support/CreatesTestData.php';

class PaymentTest extends ControllerTestCase
{
    use CreatesTestData;

    private $user;
    private $category;
    private $method;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user     = $this->createUser();
        $this->category = $this->createCategory($this->user->id);
        $this->method   = $this->createMethod($this->user->id);

        $_SESSION['user'] = $this->user;
    }

    protected function tearDown(): void
    {
        $this->cleanTestData();
        $_POST = [];
        $_GET = [];
        $_SESSION = [];
        parent::tearDown();
    }

    /** Dati POST validi per store()/update(), sovrascrivibili. */
    private function validPost(array $override = []): array
    {
        return array_merge([
            'type'              => 'Uscita',
            'amount'            => '12,50',
            'transaction_date'  => '2026-11-09',
            'category_id'       => (string) $this->category->id,
            'payment_method_id' => (string) $this->method->id,
            'is_completed'      => 'Yes',
            'description'       => 'UnitTest nuovo pagamento',
        ], $override);
    }

    private function newPayment(array $attrs = []): Payments
    {
        return $this->createPayment($this->user, $this->category, $this->method, $attrs);
    }

    // ---------------------------------------------------------------- accesso

    public function testConstructorRedirectsWhenNotLogged(): void
    {
        $_SESSION = [];

        // Auth::requireLogin() fa header('Location: ...') + exit: nei test diventa RedirectException
        $this->expectException(RedirectException::class);
        new Payment();
    }

    // ------------------------------------------------------------------ index

    public function testIndexRendersHistory(): void
    {
        $this->newPayment();

        $payment = new Payment();
        $output = $this->assertNoRedirect(function () use ($payment) {
            $payment->index();
        });

        $this->assertNotEmpty($output);
    }

    public function testIndexRedirectsAdminToAdministrator(): void
    {
        $_SESSION['user'] = $this->createUser(['role' => 'admin']);

        $payment = new Payment();
        $this->assertRedirectsTo(URL . 'administrator/stats', function () use ($payment) {
            $payment->index();
        });
    }

    public function testIndexWithValidFilters(): void
    {
        $this->newPayment();

        $_GET = [
            'category_id'       => (string) $this->category->id,
            'payment_method_id' => (string) $this->method->id,
            'type'              => 'Uscita',
            'is_completed'      => 'Yes',
            'date_min'          => '2026-01-01',
            'date_max'          => '2026-12-31',
            'amount_min'        => '-100',
            'amount_max'        => '100',
            'page'              => '999',   // pagina oltre l'ultima: viene riportata all'ultima
        ];

        $payment = new Payment();
        $output = $this->assertNoRedirect(function () use ($payment) {
            $payment->index();
        });

        $this->assertNotEmpty($output);
    }

    public function testIndexWithInvertedRangesDoesNotBreak(): void
    {
        // min > max: l'intervallo non viene applicato ma la pagina si vede comunque
        $_GET = [
            'date_min'   => '2026-12-31',
            'date_max'   => '2026-01-01',
            'amount_min' => '100',
            'amount_max' => '10',
        ];

        $payment = new Payment();
        $output = $this->assertNoRedirect(function () use ($payment) {
            $payment->index();
        });

        $this->assertNotEmpty($output);
    }

    // ------------------------------------------------------------------- show

    public function testShowOwnPayment(): void
    {
        $existing = $this->newPayment();
        $_POST['payment_id'] = $existing->id;

        $payment = new Payment();
        $output = $this->assertNoRedirect(function () use ($payment) {
            $payment->show();
        });

        $this->assertNotEmpty($output);
    }

    public function testShowPaymentOfAnotherUserRedirects(): void
    {
        $existing = $this->newPayment();
        $_POST['payment_id'] = $existing->id;

        // Entra un altro utente
        $_SESSION['user'] = $this->createUser();

        $payment = new Payment();
        $this->assertRedirectsTo(URL . 'payment/index', function () use ($payment) {
            $payment->show();
        });

        $this->assertSame('Non hai accesso a questa pagina', $_SESSION['error']);
    }

    public function testShowNonExistingPayment(): void
    {
        $_POST['payment_id'] = -1;

        $payment = new Payment();
        $this->assertRedirectsTo(URL . 'payment/index', function () use ($payment) {
            $payment->show();
        });
    }

    // ----------------------------------------------------------------- create

    public function testCreateRendersForm(): void
    {
        $payment = new Payment();
        $output = $this->assertNoRedirect(function () use ($payment) {
            $payment->create();
        });

        $this->assertNotEmpty($output);
    }

    // ------------------------------------------------------------------ store

    public function testStorePayment(): void
    {
        $_POST = $this->validPost();

        $payment = new Payment();
        $this->assertRedirectsTo(URL . 'payment/index', function () use ($payment) {
            $payment->store();
        });

        $this->assertSame('Pagamento aggiunto', $_SESSION['success']);

        $saved = Payments::where('user_id', $this->user->id)
            ->where('description', 'UnitTest nuovo pagamento')
            ->first();

        $this->assertNotNull($saved);
        $this->assertSame('Uscita', $saved->type);
        $this->assertEqualsWithDelta(12.50, (float) $saved->amount, 0.001);   // la virgola diventa punto
        $this->assertSame('2026-11-09', substr($saved->transaction_date, 0, 10));
        $this->assertEquals($this->category->id, $saved->category_id);
        $this->assertEquals($this->method->id, $saved->payment_method_id);
    }

    public function testStorePaymentWithDateAndTime(): void
    {
        $_POST = $this->validPost(['transaction_date' => '2026-11-09 18:30']);

        $payment = new Payment();
        $this->assertRedirectsTo(URL . 'payment/index', function () use ($payment) {
            $payment->store();
        });

        $saved = Payments::where('user_id', $this->user->id)
            ->where('description', 'UnitTest nuovo pagamento')
            ->firstOrFail();

        $this->assertSame('2026-11-09 18:30:00', $saved->transaction_date);
    }

    public function testStorePaymentWithoutDateUsesNow(): void
    {
        $_POST = $this->validPost(['transaction_date' => '']);

        $payment = new Payment();
        $this->assertRedirectsTo(URL . 'payment/index', function () use ($payment) {
            $payment->store();
        });

        $saved = Payments::where('user_id', $this->user->id)
            ->where('description', 'UnitTest nuovo pagamento')
            ->firstOrFail();

        $this->assertSame(date('Y-m-d'), substr($saved->transaction_date, 0, 10));
    }

    public function testStorePaymentWithAllInvalidFields(): void
    {
        $_POST = $this->validPost([
            'type'              => 'Boh',
            'amount'            => '-5',
            'transaction_date'  => '31/12/2026',
            'category_id'       => 'abc',
            'payment_method_id' => 'abc',
            'is_completed'      => 'Forse',
            'description'       => '',
        ]);

        $payment = new Payment();
        $this->assertRedirectsTo(URL . 'payment/create', function () use ($payment) {
            $payment->store();
        });

        foreach (['type', 'amount', 'transaction_date', 'category_id', 'payment_method_id', 'is_completed', 'description'] as $field) {
            $this->assertArrayHasKey($field, $_SESSION['errors'], "Manca l'errore per il campo $field");
        }

        // I vecchi valori tornano nel form e non è stato salvato niente
        $this->assertArrayHasKey('old', $_SESSION);
        $this->assertSame(0, Payments::where('user_id', $this->user->id)->count());
    }

    public function testStorePaymentWithZeroAmount(): void
    {
        $_POST = $this->validPost(['amount' => '0']);

        $payment = new Payment();
        $this->assertRedirectsTo(URL . 'payment/create', function () use ($payment) {
            $payment->store();
        });

        $this->assertArrayHasKey('amount', $_SESSION['errors']);
    }

    public function testStorePaymentWithTooLongDescription(): void
    {
        $_POST = $this->validPost(['description' => str_repeat('a', 1001)]);

        $payment = new Payment();
        $this->assertRedirectsTo(URL . 'payment/create', function () use ($payment) {
            $payment->store();
        });

        $this->assertArrayHasKey('description', $_SESSION['errors']);
    }

    public function testStorePaymentWithCategoryOfAnotherUser(): void
    {
        $other = $this->createUser();
        $foreignCategory = $this->createCategory($other->id, true, 'UnitTest categoria altrui');
        $foreignMethod   = $this->createMethod($other->id);

        $_POST = $this->validPost([
            'category_id'       => (string) $foreignCategory->id,
            'payment_method_id' => (string) $foreignMethod->id,
        ]);

        $payment = new Payment();
        $this->assertRedirectsTo(URL . 'payment/create', function () use ($payment) {
            $payment->store();
        });

        $this->assertArrayHasKey('category_id', $_SESSION['errors']);
        $this->assertArrayHasKey('payment_method_id', $_SESSION['errors']);
        $this->assertSame(0, Payments::where('user_id', $this->user->id)->count());
    }

    public function testStorePaymentWithInactiveCategoryOrMethod(): void
    {
        $inactiveCategory = $this->createCategory($this->user->id, false, 'UnitTest categoria off');
        $inactiveMethod   = $this->createMethod($this->user->id, false);

        $_POST = $this->validPost([
            'category_id'       => (string) $inactiveCategory->id,
            'payment_method_id' => (string) $inactiveMethod->id,
        ]);

        $payment = new Payment();
        $this->assertRedirectsTo(URL . 'payment/create', function () use ($payment) {
            $payment->store();
        });

        $this->assertArrayHasKey('category_id', $_SESSION['errors']);
        $this->assertArrayHasKey('payment_method_id', $_SESSION['errors']);
    }

    // ------------------------------------------------------------------- edit

    public function testEditRendersForm(): void
    {
        $existing = $this->newPayment();
        $_POST['payment_id'] = $existing->id;

        $payment = new Payment();
        $output = $this->assertNoRedirect(function () use ($payment) {
            $payment->edit();
        });

        $this->assertNotEmpty($output);
    }

    public function testEditNonExistingPaymentRedirects(): void
    {
        $_POST['payment_id'] = -1;

        $payment = new Payment();
        $this->assertRedirectsTo(URL . 'payment/index', function () use ($payment) {
            $payment->edit();
        });

        $this->assertSame('Pagamento non trovato', $_SESSION['error']);
    }

    public function testEditPaymentOfAnotherUserRedirects(): void
    {
        $existing = $this->newPayment();
        $_POST['payment_id'] = $existing->id;

        $_SESSION['user'] = $this->createUser();

        $payment = new Payment();
        $this->assertRedirectsTo(URL . 'payment/index', function () use ($payment) {
            $payment->edit();
        });

        $this->assertSame('Pagamento non trovato', $_SESSION['error']);
    }

    // ----------------------------------------------------------------- update

    public function testUpdatePayment(): void
    {
        $existing = $this->newPayment();
        $otherCategory = $this->createCategory($this->user->id, true, 'UnitTest altra categoria');
        $otherMethod   = $this->createMethod($this->user->id);

        $_POST = $this->validPost([
            'payment_id'        => $existing->id,
            'amount'            => '20.00',
            'transaction_date'  => '2026-10-01',
            'category_id'       => (string) $otherCategory->id,
            'payment_method_id' => (string) $otherMethod->id,
            'description'       => 'UnitTest pagamento modificato',
        ]);

        $payment = new Payment();
        $this->assertRedirectsTo(URL . 'payment/index', function () use ($payment) {
            $payment->update();
        });

        $this->assertSame('Pagamento modificato', $_SESSION['success']);

        $updated = Payments::find($existing->id);
        $this->assertEqualsWithDelta(20.00, (float) $updated->amount, 0.001);
        $this->assertSame('UnitTest pagamento modificato', $updated->description);
        $this->assertEquals($otherCategory->id, $updated->category_id);
        $this->assertEquals($otherMethod->id, $updated->payment_method_id);
        $this->assertSame('2026-10-01', substr($updated->transaction_date, 0, 10));
    }

    public function testUpdatePaymentWithInvalidDataIsNotSaved(): void
    {
        $existing = $this->newPayment();

        $_POST = $this->validPost([
            'payment_id'  => $existing->id,
            'amount'      => 'abc',
            'description' => 'UnitTest non deve essere salvata',
        ]);

        $payment = new Payment();
        $this->assertRedirectsTo(URL . 'payment/index', function () use ($payment) {
            $payment->update();
        });

        $this->assertArrayHasKey('amount', $_SESSION['errors']);
        $this->assertSame('UnitTest pagamento', Payments::find($existing->id)->description);
    }

    public function testUpdatePaymentOfAnotherUserIsDenied(): void
    {
        $existing = $this->newPayment();

        $_POST = $this->validPost([
            'payment_id'  => $existing->id,
            'description' => 'UnitTest modifica non autorizzata',
        ]);

        $_SESSION['user'] = $this->createUser();

        $payment = new Payment();
        $this->assertRedirectsTo(URL . 'payment/index', function () use ($payment) {
            $payment->update();
        });

        $this->assertSame('Pagamento non trovato', $_SESSION['error']);
        $this->assertSame('UnitTest pagamento', Payments::find($existing->id)->description);
    }

    public function testUpdateNonExistingPayment(): void
    {
        $_POST = $this->validPost(['payment_id' => -1]);

        $payment = new Payment();
        $this->assertRedirectsTo(URL . 'payment/index', function () use ($payment) {
            $payment->update();
        });

        $this->assertSame('Pagamento non trovato', $_SESSION['error']);
    }

    // ----------------------------------------------------------------- delete

    public function testDeleteMovesPaymentToTrash(): void
    {
        $existing = $this->newPayment();
        $_POST['payment_id'] = $existing->id;

        $payment = new Payment();
        $this->assertRedirectsTo(URL . 'payment/index', function () use ($payment) {
            $payment->delete();
        });

        $this->assertSame('Pagamento cestinato', $_SESSION['success']);

        $trashed = Payments::find($existing->id);
        $this->assertNotNull($trashed, 'Il pagamento deve restare nel DB (soft delete)');
        $this->assertTrue((bool) $trashed->is_trashed);
        $this->assertTrue((bool) $trashed->is_active);
    }

    public function testDeleteNonExistingPayment(): void
    {
        $_POST['payment_id'] = -1;

        $payment = new Payment();
        $this->assertRedirectsTo(URL . 'payment/index', function () use ($payment) {
            $payment->delete();
        });
    }

    public function testDeletePaymentOfAnotherUserIsDenied(): void
    {
        $existing = $this->newPayment();
        $_POST['payment_id'] = $existing->id;

        $_SESSION['user'] = $this->createUser();

        $payment = new Payment();
        $this->assertRedirectsTo(URL . 'payment/index', function () use ($payment) {
            $payment->delete();
        });

        $this->assertFalse((bool) Payments::find($existing->id)->is_trashed, 'Il pagamento non deve finire nel cestino');
    }
}
