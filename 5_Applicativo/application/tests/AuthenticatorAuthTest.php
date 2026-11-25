<?php

use controller\Authenticator;
use libs\Auth;
use models\Users;

require_once __DIR__ . '/support/CreatesTestData.php';

/**
 * Completa AutenticatorTest: copre authenticate() e logout(),
 * login() e register() sono già testati lì.
 * Gli utenti creati hanno password "Password123!".
 */
class AuthenticatorAuthTest extends ControllerTestCase
{
    use CreatesTestData;

    protected function tearDown(): void
    {
        $this->cleanTestData();
        $_POST = [];
        $_SESSION = [];
        parent::tearDown();
    }

    public function testAuthenticateWithValidCredentials(): void
    {
        $user = $this->createUser();
        $_POST = ['email' => $user->email, 'password' => 'Password123!'];

        $authenticator = new Authenticator();
        $this->assertRedirectsTo(URL . 'home/index', function () use ($authenticator) {
            $authenticator->authenticate();
        });

        $this->assertTrue(Auth::isLogged());
        $this->assertEquals($user->id, $_SESSION['user']->id);
    }

    public function testAuthenticateWithInvalidEmailFormat(): void
    {
        $_POST = ['email' => 'non-una-email', 'password' => 'Password123!'];

        $authenticator = new Authenticator();
        $this->assertRedirectsTo(URL . 'authenticator/login', function () use ($authenticator) {
            $authenticator->authenticate();
        });

        $this->assertSame('Email non valida', $_SESSION['errors']['email']);
        $this->assertFalse(Auth::isLogged());
    }

    public function testAuthenticateWithWrongPassword(): void
    {
        $user = $this->createUser();
        $_POST = ['email' => $user->email, 'password' => 'PasswordSbagliata1!'];

        $authenticator = new Authenticator();
        $this->assertRedirectsTo(URL . 'authenticator/login', function () use ($authenticator) {
            $authenticator->authenticate();
        });

        $this->assertSame('Credenziali errate', $_SESSION['errors']['login']);
        $this->assertSame($user->email, $_SESSION['old']['email']);
        $this->assertFalse(Auth::isLogged());
    }

    public function testAuthenticateWithUnknownEmail(): void
    {
        $_POST = ['email' => 'nessuno.unittest@example.com', 'password' => 'Password123!'];

        $authenticator = new Authenticator();
        $this->assertRedirectsTo(URL . 'authenticator/login', function () use ($authenticator) {
            $authenticator->authenticate();
        });

        $this->assertSame('Credenziali errate', $_SESSION['errors']['login']);
        $this->assertFalse(Auth::isLogged());
    }

    public function testAuthenticateWithInactiveAccount(): void
    {
        $user = $this->createUser(['is_active' => 0]);
        $_POST = ['email' => $user->email, 'password' => 'Password123!'];

        $authenticator = new Authenticator();
        $this->assertRedirectsTo(URL . 'authenticator/login', function () use ($authenticator) {
            $authenticator->authenticate();
        });

        $this->assertSame('Account inesistente', $_SESSION['errors']['login']);
        $this->assertFalse(Auth::isLogged());
    }

    public function testLogout(): void
    {
        $_SESSION['user'] = $this->createUser();
        $this->assertTrue(Auth::isLogged());

        $authenticator = new Authenticator();
        $this->assertRedirectsTo(URL . 'authenticator/login', function () use ($authenticator) {
            $authenticator->logout();
        });

        $this->assertFalse(Auth::isLogged());
    }
}
