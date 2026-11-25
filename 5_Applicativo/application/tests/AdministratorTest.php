<?php

use controller\Administrator;
use models\Users;

require_once __DIR__ . '/support/CreatesTestData.php';

class AdministratorTest extends ControllerTestCase
{
    use CreatesTestData;

    private const DENIED = 'Non hai accesso a questa pagina';

    protected function tearDown(): void
    {
        $this->cleanTestData();
        $_SESSION = [];
        parent::tearDown();
    }

    private function loginAsAdmin(): Users
    {
        $admin = $this->createUser(['role' => 'admin']);
        $_SESSION['user'] = $admin;
        return $admin;
    }

    private function loginAsNormalUser(): Users
    {
        $user = $this->createUser();
        $_SESSION['user'] = $user;
        return $user;
    }

    public function testConstructorRedirectsWhenNotLogged(): void
    {
        $_SESSION = [];

        $this->expectException(RedirectException::class);
        new Administrator();
    }

    // ------------------------------------------------------------------ stats

    public function testStatsRendersForAdmin(): void
    {
        $this->loginAsAdmin();

        $administrator = new Administrator();
        $output = $this->assertNoRedirect(function () use ($administrator) {
            $administrator->stats();
        });

        $this->assertNotEmpty($output);
    }

    public function testStatsDeniedToNormalUser(): void
    {
        $this->loginAsNormalUser();

        $administrator = new Administrator();
        $this->assertRedirectsTo(URL, function () use ($administrator) {
            $administrator->stats();
        });

        $this->assertSame(self::DENIED, $_SESSION['errors']);
    }

    // ---------------------------------------------------------------- archive

    public function testArchiveRendersUsersForAdmin(): void
    {
        $this->loginAsAdmin();
        $this->createUser(['is_active' => 0]);   // un account inattivo da mostrare

        $administrator = new Administrator();
        $output = $this->assertNoRedirect(function () use ($administrator) {
            $administrator->archive();
        });

        $this->assertNotEmpty($output);
    }

    public function testArchiveWithValidFilters(): void
    {
        $this->loginAsAdmin();
        $this->createUser(['is_active' => 0]);
        $_GET = ['role' => 'user', 'is_active' => '0', 'page' => '1'];

        $administrator = new Administrator();
        $output = $this->assertNoRedirect(function () use ($administrator) {
            $administrator->archive();
        });

        $this->assertNotEmpty($output);
    }

    public function testArchiveIgnoresInvalidFilters(): void
    {
        $this->loginAsAdmin();
        $_GET = ['role' => 'superuser', 'is_active' => 'abc', 'page' => '-5'];

        $administrator = new Administrator();
        $output = $this->assertNoRedirect(function () use ($administrator) {
            $administrator->archive();
        });

        $this->assertNotEmpty($output);
    }

    public function testArchiveDeniedToNormalUser(): void
    {
        $this->loginAsNormalUser();

        $administrator = new Administrator();
        $this->assertRedirectsTo(URL, function () use ($administrator) {
            $administrator->archive();
        });

        $this->assertSame(self::DENIED, $_SESSION['errors']);
    }

    // ------------------------------------------------------------------- logs

    public function testLogsRendersForAdmin(): void
    {
        $this->loginAsAdmin();

        $administrator = new Administrator();
        $output = $this->assertNoRedirect(function () use ($administrator) {
            $administrator->logs();
        });

        $this->assertNotEmpty($output);
    }

    public function testLogsWithValidFilters(): void
    {
        $this->loginAsAdmin();
        $_GET = [
            'user_name' => 'Unit',
            'date_min'  => '2026-01-01',
            'date_max'  => '2026-12-31',
            'page'      => '1',
        ];

        $administrator = new Administrator();
        $output = $this->assertNoRedirect(function () use ($administrator) {
            $administrator->logs();
        });

        $this->assertNotEmpty($output);
    }

    public function testLogsWithInvertedDatesStillRenders(): void
    {
        $this->loginAsAdmin();
        $_GET = ['date_min' => '2026-12-31', 'date_max' => '2026-01-01'];

        $administrator = new Administrator();
        $output = $this->assertNoRedirect(function () use ($administrator) {
            $administrator->logs();
        });

        $this->assertNotEmpty($output);
    }

    public function testLogsDeniedToNormalUser(): void
    {
        $this->loginAsNormalUser();

        $administrator = new Administrator();
        $this->assertRedirectsTo(URL, function () use ($administrator) {
            $administrator->logs();
        });

        $this->assertSame(self::DENIED, $_SESSION['errors']);
    }

    // ------------------------------------------------------------------- show

    public function testShowRendersUserForAdmin(): void
    {
        $this->loginAsAdmin();
        $target = $this->createUser();
        $_POST['user_id'] = (string) $target->id;

        $administrator = new Administrator();
        $output = $this->assertNoRedirect(function () use ($administrator) {
            $administrator->show();
        });

        $this->assertNotEmpty($output);
    }

    public function testShowUserNotFound(): void
    {
        $this->loginAsAdmin();
        $_POST['user_id'] = '-1';

        $administrator = new Administrator();
        $this->assertRedirectsTo(URL . 'administrator/archive', function () use ($administrator) {
            $administrator->show();
        });

        $this->assertSame('Utente non trovato', $_SESSION['errors']);
    }

    public function testShowDeniedToNormalUser(): void
    {
        $this->loginAsNormalUser();

        $administrator = new Administrator();
        $this->assertRedirectsTo(URL, function () use ($administrator) {
            $administrator->show();
        });

        $this->assertSame(self::DENIED, $_SESSION['errors']);
    }

    // ------------------------------------------------------------------- edit

    public function testEditRendersFormForAdmin(): void
    {
        $this->loginAsAdmin();
        $target = $this->createUser();
        $_POST['user_id'] = (string) $target->id;

        $administrator = new Administrator();
        $output = $this->assertNoRedirect(function () use ($administrator) {
            $administrator->edit();
        });

        $this->assertNotEmpty($output);
    }

    public function testEditUserNotFound(): void
    {
        $this->loginAsAdmin();
        $_POST['user_id'] = '-1';

        $administrator = new Administrator();
        $this->assertRedirectsTo(URL . 'administrator/archive', function () use ($administrator) {
            $administrator->edit();
        });

        $this->assertSame('Utente non trovato', $_SESSION['errors']);
    }

    public function testEditDeniedToNormalUser(): void
    {
        $this->loginAsNormalUser();

        $administrator = new Administrator();
        $this->assertRedirectsTo(URL, function () use ($administrator) {
            $administrator->edit();
        });

        $this->assertSame(self::DENIED, $_SESSION['errors']);
    }

    // ----------------------------------------------------------------- update

    private function validUpdateData(Users $target): array
    {
        return [
            'user_id'          => (string) $target->id,
            'first_name'       => 'Mario',
            'last_name'        => 'Rossi',
            'email'            => 'unittest.' . bin2hex(random_bytes(6)) . '@example.com',
            'password'         => 'NewPassword123!',
            'confirm_password' => 'NewPassword123!',
            'spending_limit'   => '300.00',
            'balance'          => '150.00',
        ];
    }

    public function testUpdateUserSuccess(): void
    {
        $this->loginAsAdmin();
        $target = $this->createUser();
        $_POST = $this->validUpdateData($target);

        $administrator = new Administrator();
        $this->assertRedirectsTo(URL . 'administrator/archive', function () use ($administrator) {
            $administrator->update();
        });

        $this->assertSame('Utente modificato con successo', $_SESSION['success']);

        $updated = Users::find($target->id);
        $this->assertSame('Mario', $updated->first_name);
        $this->assertSame('Rossi', $updated->last_name);
        $this->assertSame($_POST['email'], $updated->email);
        $this->assertEquals(300.00, $updated->spending_limit);
        $this->assertEquals(150.00, $updated->balance);
        $this->assertTrue(password_verify('NewPassword123!', $updated->password));
    }

    public function testUpdateUserInvalidDataRedirectsToEdit(): void
    {
        $this->loginAsAdmin();
        $target = $this->createUser();
        $_POST = $this->validUpdateData($target);
        $_POST['email'] = 'non-una-email';
        $_POST['confirm_password'] = 'Diversa123!';
        $_POST['balance'] = 'abc';

        $administrator = new Administrator();
        $this->assertRedirectsTo(URL . 'administrator/edit/' . $target->id, function () use ($administrator) {
            $administrator->update();
        });

        $this->assertArrayHasKey('email', $_SESSION['errors']);
        $this->assertArrayHasKey('conf_pwd', $_SESSION['errors']);
        $this->assertArrayHasKey('balance', $_SESSION['errors']);

        // Nessuna modifica salvata
        $this->assertSame($target->email, Users::find($target->id)->email);
    }

    public function testUpdateUserEmailAlreadyUsed(): void
    {
        $this->loginAsAdmin();
        $target = $this->createUser();
        $other  = $this->createUser();
        $_POST = $this->validUpdateData($target);
        $_POST['email'] = $other->email;

        $administrator = new Administrator();
        $this->assertRedirectsTo(URL . 'administrator/edit/' . $target->id, function () use ($administrator) {
            $administrator->update();
        });

        $this->assertSame('Questa email è già utilizzata da un altro utente.', $_SESSION['errors']['email']);
    }

    public function testUpdateUserNotFound(): void
    {
        $this->loginAsAdmin();
        $_POST['user_id'] = '-1';

        $administrator = new Administrator();
        $this->assertRedirectsTo(URL . 'administrator/archive', function () use ($administrator) {
            $administrator->update();
        });

        $this->assertSame('Utente non trovato', $_SESSION['errors']);
    }

    public function testUpdateDeniedToNormalUser(): void
    {
        $this->loginAsNormalUser();

        $administrator = new Administrator();
        $this->assertRedirectsTo(URL, function () use ($administrator) {
            $administrator->update();
        });

        $this->assertSame(self::DENIED, $_SESSION['errors']);
    }

    // ------------------------------------------------------------- deactivate

    public function testDeactivateUser(): void
    {
        $this->loginAsAdmin();
        $target = $this->createUser();
        $_POST['user_id'] = (string) $target->id;

        $administrator = new Administrator();
        $this->assertRedirectsTo(URL . 'administrator/archive', function () use ($administrator) {
            $administrator->deactivate();
        });

        $this->assertSame('Utente disattivato', $_SESSION['success']);
        $this->assertFalse((bool) Users::find($target->id)->is_active);
    }

    public function testDeactivateOwnAccountIsRefused(): void
    {
        $admin = $this->loginAsAdmin();
        $_POST['user_id'] = (string) $admin->id;

        $administrator = new Administrator();
        $this->assertRedirectsTo(URL . 'administrator/archive', function () use ($administrator) {
            $administrator->deactivate();
        });

        $this->assertSame('Non puoi disattivare il tuo account', $_SESSION['errors']);
        $this->assertTrue((bool) Users::find($admin->id)->is_active);
    }

    public function testDeactivateUserNotFound(): void
    {
        $this->loginAsAdmin();
        $_POST['user_id'] = '-1';

        $administrator = new Administrator();
        $this->assertRedirectsTo(URL . 'administrator/archive', function () use ($administrator) {
            $administrator->deactivate();
        });

        $this->assertSame('Utente non trovato', $_SESSION['errors']);
    }

    public function testDeactivateDeniedToNormalUser(): void
    {
        $this->loginAsNormalUser();
        $target = $this->createUser();
        $_POST['user_id'] = (string) $target->id;

        $administrator = new Administrator();
        $this->assertRedirectsTo(URL, function () use ($administrator) {
            $administrator->deactivate();
        });

        $this->assertSame(self::DENIED, $_SESSION['errors']);
        $this->assertTrue((bool) Users::find($target->id)->is_active);
    }

    // --------------------------------------------------------------- activate

    public function testActivateUser(): void
    {
        $this->loginAsAdmin();
        $target = $this->createUser(['is_active' => 0]);
        $_POST['user_id'] = (string) $target->id;

        $administrator = new Administrator();
        $this->assertRedirectsTo(URL . 'administrator/archive', function () use ($administrator) {
            $administrator->activate();
        });

        $this->assertSame('Utente riattivato', $_SESSION['success']);
        $this->assertTrue((bool) Users::find($target->id)->is_active);
    }

    public function testActivateUserNotFound(): void
    {
        $this->loginAsAdmin();
        $_POST['user_id'] = '-1';

        $administrator = new Administrator();
        $this->assertRedirectsTo(URL . 'administrator/archive', function () use ($administrator) {
            $administrator->activate();
        });

        $this->assertSame('Utente non trovato', $_SESSION['errors']);
    }

    public function testActivateDeniedToNormalUser(): void
    {
        $this->loginAsNormalUser();
        $target = $this->createUser(['is_active' => 0]);
        $_POST['user_id'] = (string) $target->id;

        $administrator = new Administrator();
        $this->assertRedirectsTo(URL, function () use ($administrator) {
            $administrator->activate();
        });

        $this->assertSame(self::DENIED, $_SESSION['errors']);
        $this->assertFalse((bool) Users::find($target->id)->is_active);
    }
}
