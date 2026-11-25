<?php

use controller\User;
use libs\Auth;
use models\Users;

require_once __DIR__ . '/support/CreatesTestData.php';

/**
 * Test di controller\User. Store e finalDelete sono già toccati in AutenticatorTest:
 * qui ci sono i casi di validazione, edit, update, delete e finalDelete con password giusta/sbagliata.
 */
class UserTest extends ControllerTestCase
{
    use CreatesTestData;

    protected function tearDown(): void
    {
        $this->cleanTestData();
        $_POST = [];
        $_SESSION = [];
        parent::tearDown();
    }

    private function newEmail(): string
    {
        return 'unittest.' . bin2hex(random_bytes(6)) . '@example.com';
    }

    // ------------------------------------------------------------------ store

    public function testStoreCreatesUserWithHashedPassword(): void
    {
        $email = $this->newEmail();
        $_POST = [
            'first_name'       => 'Mario',
            'last_name'        => 'Rossi',
            'email'            => $email,
            'password'         => 'Password123!',
            'confirm_password' => 'Password123!',
        ];

        $user = new User();
        $this->assertRedirectsTo(URL . 'authenticator/login', function () use ($user) {
            $user->store();
        });

        $this->assertSame('Utente creato con successo', $_SESSION['success']);

        $saved = Users::where('email', $email)->first();
        $this->assertNotNull($saved);
        $this->assertNotSame('Password123!', $saved->password, 'La password non deve essere salvata in chiaro');
        $this->assertTrue(password_verify('Password123!', $saved->password));
    }

    public function testStoreWithInvalidDataRedirectsToRegister(): void
    {
        $_POST = [
            'first_name'       => 'Mario',
            'last_name'        => 'Rossi',
            'email'            => 'non-una-email',
            'password'         => 'debole',
            'confirm_password' => 'diversa',
        ];

        $user = new User();
        $this->assertRedirectsTo(URL . 'authenticator/register', function () use ($user) {
            $user->store();
        });

        $this->assertArrayHasKey('email', $_SESSION['errors']);
        $this->assertArrayHasKey('password', $_SESSION['errors']);
        $this->assertArrayHasKey('conf_pwd', $_SESSION['errors']);
        $this->assertArrayHasKey('old', $_SESSION);
    }

    public function testStoreWithDifferentPasswordConfirmation(): void
    {
        $_POST = [
            'first_name'       => 'Mario',
            'last_name'        => 'Rossi',
            'email'            => $this->newEmail(),
            'password'         => 'Password123!',
            'confirm_password' => 'Password123?',
        ];

        $user = new User();
        $this->assertRedirectsTo(URL . 'authenticator/register', function () use ($user) {
            $user->store();
        });

        $this->assertArrayHasKey('conf_pwd', $_SESSION['errors']);
        $this->assertArrayNotHasKey('password', $_SESSION['errors']);
    }

    public function testStoreWithAlreadyUsedEmail(): void
    {
        $existing = $this->createUser();

        $_POST = [
            'first_name'       => 'Mario',
            'last_name'        => 'Rossi',
            'email'            => $existing->email,
            'password'         => 'Password123!',
            'confirm_password' => 'Password123!',
        ];

        $user = new User();
        $this->assertRedirectsTo(URL . 'authenticator/register', function () use ($user) {
            $user->store();
        });

        $this->assertSame('Questa mail è gia stata utilizzata', $_SESSION['errors']['email']);
        $this->assertSame(1, Users::where('email', $existing->email)->count());
    }

    // ------------------------------------------------------------------- edit

    public function testEditRedirectsWhenNotLogged(): void
    {
        $_SESSION = [];

        $user = new User();
        $this->expectException(RedirectException::class);
        $user->edit();
    }

    public function testEditRendersProfileForm(): void
    {
        $_SESSION['user'] = $this->createUser();

        $user = new User();
        $output = $this->assertNoRedirect(function () use ($user) {
            $user->edit();
        });

        $this->assertNotEmpty($output);
    }

    // ----------------------------------------------------------------- update

    public function testUpdateChangesProfileKeepingPassword(): void
    {
        $logged = $this->createUser();
        $_SESSION['user'] = $logged;
        $newEmail = $this->newEmail();

        $_POST = [
            'first_name'       => 'Nuovo',
            'last_name'        => 'Nome',
            'email'            => $newEmail,
            'password'         => '',      // vuota = non cambiare password
            'confirm_password' => '',
            'spending_limit'   => '300',
            'balance'          => '1500',
        ];

        $user = new User();
        $this->assertRedirectsTo(URL, function () use ($user) {
            $user->update();
        });

        $this->assertSame('Utente modificato con successo', $_SESSION['success']);

        $updated = Users::find($logged->id);
        $this->assertSame('Nuovo', $updated->first_name);
        $this->assertSame('Nome', $updated->last_name);
        $this->assertSame($newEmail, $updated->email);
        $this->assertEqualsWithDelta(300, (float) $updated->spending_limit, 0.001);
        $this->assertEqualsWithDelta(1500, (float) $updated->balance, 0.001);
        $this->assertSame($logged->password, $updated->password, 'La password non deve cambiare');
    }

    public function testUpdateWithInvalidDataRedirectsToEdit(): void
    {
        $logged = $this->createUser();
        $_SESSION['user'] = $logged;

        $_POST = [
            'first_name'       => 'Nuovo',
            'last_name'        => 'Nome',
            'email'            => 'non-una-email',
            'password'         => '',
            'confirm_password' => '',
            'spending_limit'   => '300',
            'balance'          => 'abc',
        ];

        $user = new User();
        $this->assertRedirectsTo(URL . 'user/edit/' . $logged->id, function () use ($user) {
            $user->update();
        });

        $this->assertArrayHasKey('email', $_SESSION['errors']);
        $this->assertArrayHasKey('balance', $_SESSION['errors']);

        // Il profilo non deve essere cambiato
        $this->assertSame($logged->email, Users::find($logged->id)->email);
    }

    public function testUpdateWithEmailOfAnotherUserIsRejected(): void
    {
        $logged = $this->createUser();
        $other  = $this->createUser();
        $_SESSION['user'] = $logged;

        $_POST = [
            'first_name'       => 'Nuovo',
            'last_name'        => 'Nome',
            'email'            => $other->email,
            'password'         => '',
            'confirm_password' => '',
            'spending_limit'   => '300',
            'balance'          => '1500',
        ];

        $user = new User();
        $this->assertRedirectsTo(URL . 'user/edit/' . $logged->id, function () use ($user) {
            $user->update();
        });

        $this->assertSame('Questa mail è gia stata utilizzata', $_SESSION['errors']['email']);
        $this->assertSame($logged->email, Users::find($logged->id)->email);
    }

    public function testUpdateWithNewPasswordHashesIt(): void
    {
        $logged = $this->createUser();
        $_SESSION['user'] = $logged;

        $_POST = [
            'first_name'       => 'Nuovo',
            'last_name'        => 'Nome',
            'email'            => $logged->email,
            'password'         => 'NuovaPassword1!',
            'confirm_password' => 'NuovaPassword1!',
            'spending_limit'   => '300',
            'balance'          => '1500',
        ];

        $user = new User();
        $this->assertRedirectsTo(URL, function () use ($user) {
            $user->update();
        });

        $updated = Users::find($logged->id);
        $this->assertNotSame('NuovaPassword1!', $updated->password);
        $this->assertTrue(password_verify('NuovaPassword1!', $updated->password));
    }

    public function testUpdateWithInvalidSpendingLimitWithoutPasswordChange(): void
    {
        $logged = $this->createUser();
        $_SESSION['user'] = $logged;

        $_POST = [
            'first_name'       => 'Nuovo',
            'last_name'        => 'Nome',
            'email'            => $logged->email,
            'password'         => '',
            'confirm_password' => '',
            'spending_limit'   => 'abc',
            'balance'          => '1500',
        ];

        $user = new User();
        $this->assertRedirectsTo(URL . 'user/edit/' . $logged->id, function () use ($user) {
            $user->update();
        });

        // Prima il limite veniva controllato solo cambiando la password
        $this->assertArrayHasKey('spending_limit', $_SESSION['errors']);
    }

    // ----------------------------------------------------------------- delete

    public function testDeleteShowsPasswordConfirmationPage(): void
    {
        $_SESSION['user'] = $this->createUser();

        $user = new User();
        $output = $this->assertNoRedirect(function () use ($user) {
            $user->delete();
        });

        $this->assertNotEmpty($output);
    }

    // ------------------------------------------------------------ finalDelete

    public function testFinalDeleteWithWrongPassword(): void
    {
        $logged = $this->createUser();
        $_SESSION['user'] = $logged;
        $_POST = ['password' => 'PasswordSbagliata1!'];

        $user = new User();
        $this->assertRedirectsTo(URL . 'user/delete', function () use ($user) {
            $user->finalDelete();
        });

        $this->assertSame('Password errata', $_SESSION['errors']['password']);
        $this->assertTrue(Auth::isLogged());
        $this->assertTrue((bool) Users::find($logged->id)->is_active, 'L\'account non deve essere disattivato');
    }

    public function testFinalDeleteWithCorrectPasswordDeactivatesAccount(): void
    {
        $logged = $this->createUser();      // password: Password123!
        $_SESSION['user'] = $logged;
        $_POST = ['password' => 'Password123!'];

        $user = new User();
        $this->assertRedirectsTo(URL . 'authenticator/login', function () use ($user) {
            $user->finalDelete();
        });

        // Soft delete: il record resta, ma è inattivo
        $deleted = Users::find($logged->id);
        $this->assertNotNull($deleted);
        $this->assertFalse((bool) $deleted->is_active);
        $this->assertFalse(Auth::isLogged(), 'Dopo l\'eliminazione la sessione deve essere chiusa');
    }
}
