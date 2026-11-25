<?php

use controller\Trash;
use models\Payments;
use models\Users;

require_once __DIR__ . '/support/CreatesTestData.php';

class TrashTest extends ControllerTestCase
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

    private function trashedPayment(array $attrs = []): Payments
    {
        return $this->createPayment($this->user, $this->category, $this->method, array_merge(['is_trashed' => 1], $attrs));
    }

    // ---------------------------------------------------------------- accesso

    public function testConstructorRedirectsWhenNotLogged(): void
    {
        $_SESSION = [];

        $this->expectException(RedirectException::class);
        new Trash();
    }

    // ------------------------------------------------------------------ index

    public function testIndexRendersTrash(): void
    {
        $this->trashedPayment();

        $trash = new Trash();
        $output = $this->assertNoRedirect(function () use ($trash) {
            $trash->index();
        });

        $this->assertNotEmpty($output);
    }

    public function testIndexWithInvertedRangesDoesNotBreak(): void
    {
        $_GET = [
            'date_min'   => '2026-12-31',
            'date_max'   => '2026-01-01',
            'amount_min' => '100',
            'amount_max' => '10',
        ];

        $trash = new Trash();
        $output = $this->assertNoRedirect(function () use ($trash) {
            $trash->index();
        });

        $this->assertNotEmpty($output);
    }

    // ------------------------------------------------------------------- show

    public function testShowOwnTrashedPayment(): void
    {
        $existing = $this->trashedPayment();
        $_POST['payment_id'] = $existing->id;

        $trash = new Trash();
        $output = $this->assertNoRedirect(function () use ($trash) {
            $trash->show();
        });

        $this->assertNotEmpty($output);
    }

    public function testShowPaymentOfAnotherUserRedirects(): void
    {
        $existing = $this->trashedPayment();
        $_POST['payment_id'] = $existing->id;

        $_SESSION['user'] = $this->createUser();

        $trash = new Trash();
        $this->assertRedirectsTo(URL . 'trash/index', function () use ($trash) {
            $trash->show();
        });

        $this->assertSame('Non hai accesso a questa pagina', $_SESSION['error']);
    }

    public function testShowNonExistingPayment(): void
    {
        $_POST['payment_id'] = -1;

        $trash = new Trash();
        $this->assertRedirectsTo(URL . 'trash/index', function () use ($trash) {
            $trash->show();
        });

        $this->assertSame('Pagamento non trovato', $_SESSION['error']);
    }

    // ---------------------------------------------------------------- restore

    public function testRestorePayment(): void
    {
        $existing = $this->trashedPayment();
        $_POST['payment_id'] = $existing->id;

        $trash = new Trash();
        $this->assertRedirectsTo(URL . 'trash/index', function () use ($trash) {
            $trash->restore();
        });

        $this->assertSame('Pagamento ripristinato', $_SESSION['success']);
        $this->assertFalse((bool) Payments::find($existing->id)->is_trashed);
    }

    public function testRestorePaymentOfAnotherUserIsDenied(): void
    {
        $existing = $this->trashedPayment();
        $_POST['payment_id'] = $existing->id;

        $_SESSION['user'] = $this->createUser();

        $trash = new Trash();
        $this->assertRedirectsTo(URL . 'payment/index', function () use ($trash) {
            $trash->restore();
        });

        $this->assertSame('Non hai accesso a questa pagina', $_SESSION['error']);
        $this->assertTrue((bool) Payments::find($existing->id)->is_trashed, 'Il pagamento non deve essere ripristinato');
    }

    public function testRestoreNonExistingPayment(): void
    {
        $_POST['payment_id'] = -1;

        $trash = new Trash();
        $this->assertRedirectsTo(URL . 'payment/index', function () use ($trash) {
            $trash->restore();
        });

        $this->assertSame('Pagamento non trovato', $_SESSION['error']);
    }

    // ----------------------------------------------- delete (pagina di conferma)

    public function testDeleteShowsConfirmPage(): void
    {
        $existing = $this->trashedPayment();
        $_POST['payment_id'] = $existing->id;

        $trash = new Trash();
        $output = $this->assertNoRedirect(function () use ($trash) {
            $trash->delete();
        });

        $this->assertNotEmpty($output);
    }

    public function testDeleteConfirmPageDeniedToAnotherUser(): void
    {
        $existing = $this->trashedPayment();
        $_POST['payment_id'] = $existing->id;

        $_SESSION['user'] = $this->createUser();

        $trash = new Trash();
        $this->assertRedirectsTo(URL . 'trash/index', function () use ($trash) {
            $trash->delete();
        });
    }

    public function testDeleteConfirmPageAllowedToAdmin(): void
    {
        $existing = $this->trashedPayment();
        $_POST['payment_id'] = $existing->id;

        $_SESSION['user'] = $this->createUser(['role' => 'admin']);

        $trash = new Trash();
        $output = $this->assertNoRedirect(function () use ($trash) {
            $trash->delete();
        });

        $this->assertNotEmpty($output);
    }

    public function testDeleteConfirmPageWithNonExistingPayment(): void
    {
        $_POST['payment_id'] = -1;

        // Qui l'eccezione di findOrFail() viene presa dal catch del controller
        $trash = new Trash();
        $this->assertRedirectsTo(URL . 'trash/index', function () use ($trash) {
            $trash->delete();
        });

        $this->assertArrayHasKey('errors', $_SESSION);
    }

    // ------------------------------------------------------------ finalDelete
    //
    // NB: nei test header() lancia RedirectException, che dentro un try/catch (\Throwable)
    // del controller viene intercettata dal catch. Il redirect finale resta lo stesso
    // (trash/index), ma $_SESSION['errors'] può essere valorizzato anche nei casi di successo:
    // per questo nei test sotto non si controlla l'assenza di errori.

    public function testFinalDeleteWithRestoreRefundsBalance(): void
    {
        $existing = $this->trashedPayment(['amount' => 25.00, 'is_completed' => 'Yes']);

        // Saldo e spese mensili DOPO l'inserimento (eventuali trigger lo hanno già modificato)
        $before = Users::find($this->user->id);
        $balanceBefore = (float) $before->balance;
        $monthlyBefore = (float) $before->monthly_expenses;

        $_POST = ['payment_id' => $existing->id, 'restore' => 'TRUE'];

        $trash = new Trash();
        $this->assertRedirectsTo(URL . 'trash/index', function () use ($trash) {
            $trash->finalDelete();
        });

        $this->assertSame('Pagamento eliminato', $_SESSION['success']);

        $after = Users::find($this->user->id);

        $this->assertEqualsWithDelta($balanceBefore + 25.00, (float) $after->balance, 0.001);
        // Il pagamento è del mese corrente, quindi rientrano anche le spese mensili
        $this->assertEqualsWithDelta($monthlyBefore - 25.00, (float) $after->monthly_expenses, 0.001);

        $payment = Payments::find($existing->id);
        $this->assertFalse((bool) $payment->is_active);
        $this->assertFalse((bool) $payment->is_trashed);
    }

    public function testFinalDeleteWithRestoreOfPreviousMonthDoesNotTouchMonthlyExpenses(): void
    {
        $existing = $this->trashedPayment([
            'amount'           => 25.00,
            'is_completed'     => 'Yes',
            'transaction_date' => date('Y-m-d H:i:s', strtotime('first day of -2 months')),
        ]);

        $before = Users::find($this->user->id);
        $balanceBefore = (float) $before->balance;
        $monthlyBefore = (float) $before->monthly_expenses;

        $_POST = ['payment_id' => $existing->id, 'restore' => 'TRUE'];

        $trash = new Trash();
        $this->assertRedirectsTo(URL . 'trash/index', function () use ($trash) {
            $trash->finalDelete();
        });

        $after = Users::find($this->user->id);
        $this->assertEqualsWithDelta($balanceBefore + 25.00, (float) $after->balance, 0.001);
        $this->assertEqualsWithDelta($monthlyBefore, (float) $after->monthly_expenses, 0.001);
    }

    public function testFinalDeleteWithRestoreOfIncomeRemovesMoneyFromBalance(): void
    {
        $existing = $this->trashedPayment(['amount' => 40.00, 'type' => 'Entrata', 'is_completed' => 'Yes']);

        $before = Users::find($this->user->id);
        $balanceBefore = (float) $before->balance;
        $monthlyBefore = (float) $before->monthly_expenses;

        $_POST = ['payment_id' => $existing->id, 'restore' => 'TRUE'];

        $trash = new Trash();
        $this->assertRedirectsTo(URL . 'trash/index', function () use ($trash) {
            $trash->finalDelete();
        });

        // Annullare un'Entrata significa togliere quei soldi dal saldo, e le spese mensili non cambiano
        $after = Users::find($this->user->id);
        $this->assertEqualsWithDelta($balanceBefore - 40.00, (float) $after->balance, 0.001);
        $this->assertEqualsWithDelta($monthlyBefore, (float) $after->monthly_expenses, 0.001);
    }

    public function testFinalDeleteWithoutRestoreKeepsBalance(): void
    {
        $existing = $this->trashedPayment(['amount' => 25.00, 'is_completed' => 'Yes']);

        $before = Users::find($this->user->id);
        $balanceBefore = (float) $before->balance;
        $monthlyBefore = (float) $before->monthly_expenses;

        $_POST = ['payment_id' => $existing->id, 'restore' => 'FALSE'];

        $trash = new Trash();
        $this->assertRedirectsTo(URL . 'trash/index', function () use ($trash) {
            $trash->finalDelete();
        });




        $after = Users::find($this->user->id);


        $this->assertEqualsWithDelta($balanceBefore, (float) $after->balance, 0.001);
        $this->assertEqualsWithDelta($monthlyBefore, (float) $after->monthly_expenses, 0.001);
        $this->assertFalse((bool) Payments::find($existing->id)->is_active);
    }

    public function testFinalDeleteOfAnotherUsersPaymentIsDenied(): void
    {
        $existing = $this->trashedPayment();
        $_POST = ['payment_id' => $existing->id, 'restore' => 'FALSE'];

        $_SESSION['user'] = $this->createUser();

        $trash = new Trash();
        $this->assertRedirectsTo(URL . 'trash/index', function () use ($trash) {
            $trash->finalDelete();
        });

        $payment = Payments::find($existing->id);
        $this->assertTrue((bool) $payment->is_active, 'Il pagamento non deve essere eliminato');
        $this->assertTrue((bool) $payment->is_trashed);
    }

    public function testFinalDeleteNonExistingPayment(): void
    {
        $_POST = ['payment_id' => -1, 'restore' => 'FALSE'];

        $trash = new Trash();
        $this->assertRedirectsTo(URL . 'trash/index', function () use ($trash) {
            $trash->finalDelete();
        });

        $this->assertSame('Utente o pagamento non trovato', $_SESSION['errors']);
    }
}
