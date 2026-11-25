<?php


use libs\Auth;
use libs\Flash;
use models\Users;

use controller\Authenticator;
use controller\User;

require_once __DIR__ . '/support/CreatesTestData.php';

class AutenticatorTest extends ControllerTestCase
{
    use CreatesTestData;

    //utente tenda di accedere al login ma è gia loggato

    private $user;
    protected function setUp(): void{
        parent::setUp();

        // BUG FIX: c'era "$this->user = new Users();" subito sovrascritto dall'array. In sessione qui basta un array,
        // serve solo ad Auth::isLogged(): per le azioni che usano $_SESSION['user']->id si usa un utente vero (createUser)
        $this->user = [
            'id' => 2,
            'email' => 'luca.bianchi@email.com',
            'first_name' => 'Luca',
            'last_name' => 'Bianchi',
        ];
    }

    protected function tearDown(): void
    {
        // Rimozione dell'utente creato da testStoreUser() e degli utenti di prova
        Users::where('email', 'utente.unittest@example.com')->delete();
        $this->cleanTestData();

        $_POST = [];
        $_SESSION = [];
        parent::tearDown();
    }

    public function testLoginUserAlreadyLogged(): void
    {
        $_SESSION['user'] = $this->user;

        $authenticator = new Authenticator();

        // login() deve fare redirect alla home (header + exit nel controller)
        $this->assertRedirectsTo(URL . 'home/index', function () use ($authenticator) {
            $authenticator->login();
        });

        // L'utente resta loggato
        $this->assertTrue(Auth::isLogged());
    }

    //utente accede al login ma non è loggato
    public function testLoginUserNotAlreadyLogged(): void
    {
        // BUG FIX: session_destroy() senza sessione attiva (CLI) dà un warning; setUp() ha già svuotato $_SESSION
        $_SESSION = [];

        $authenticator = new Authenticator();

        $output = $this->assertNoRedirect(function () use ($authenticator) {
            $authenticator->login();
        });

        $this->assertFalse(Auth::isLogged());
        $this->assertNotEmpty($output);

    }

    public function testRegisterUserAlreadyLogged(): void{
        $_SESSION['user'] = $this->user;

        $authenticator = new Authenticator();
        $this->assertRedirectsTo(URL . 'home/index', function () use ($authenticator) {
            $authenticator->register();
        });

        $this->assertTrue(Auth::isLogged());
    }

    public function testRegisterUserNotAlreadyLogged(): void {
        $_SESSION = [];   // BUG FIX: vedi testLoginUserNotAlreadyLogged
        $authenticator = new Authenticator();
        $output = $this->assertNoRedirect(function () use ($authenticator) {
            $authenticator->register();
        });

        $this->assertFalse(Auth::isLogged());
        $this->assertNotEmpty($output);
    }

    public function testStoreUser(): void
    {

        //rimozione utente test
        // BUG FIX: senza ->delete() "if($oldUser)" era sempre vero (query builder) e il delete cancellava per caso: ora è esplicito
        Users::where('email', 'utente.unittest@example.com')->delete();

        $_POST = [
            'first_name'       => 'Utente',
            'last_name'        => 'UnitTest',
            'email'            => 'utente.unittest@example.com',
            'password'         => 'Password123!',
            'confirm_password' => 'Password123!'
        ];

        $user = new User();
            $this->assertRedirectsTo(URL . 'authenticator/login', function () use ($user) {
                $user->store();
            });

        $this->assertTrue(!Auth::isLogged());
        $this->assertTrue(Users::userExists('utente.unittest@example.com'));

        $_POST = [];
        $_SESSION = [];

    }

    public function testStoreUserAlreadyCreated(): void{

        // BUG FIX: il test dipendeva da testStoreUser() eseguito prima: ora l'utente viene creato qui
        Users::where('email', 'utente.unittest@example.com')->delete();
        Users::create([
            'first_name' => 'Utente',
            'last_name'  => 'UnitTest',
            'email'      => 'utente.unittest@example.com',
            'password'   => password_hash('Password123!', PASSWORD_BCRYPT),
        ]);

        $_POST = [
            'first_name'       => 'Utente',
            'last_name'        => 'UnitTest',
            'email'            => 'utente.unittest@example.com',
            'password'         => 'Password123!',
            'confirm_password' => 'Password123!'
        ];

        $user = new User();
        $this->assertRedirectsTo(URL . 'authenticator/register', function () use ($user) {
            $user->store();
        });

        $this->assertTrue(!Auth::isLogged());

        $_POST = [];
    }

    public function testDeleteUser(): void{

        // BUG FIX: il test passava l'HASH della password (quindi testava il ramo "password errata") e dipendeva da testStoreUser().
        // Ora si crea un utente con password nota e si manda la password in chiaro
        $oldUser = $this->createUser();

        $_SESSION['user'] = $oldUser;
        $_POST = ['password' => 'Password123!'];

        $user = new User();

        $this->assertRedirectsTo(URL . 'authenticator/login', function () use ($user) {
            $user->finalDelete();
        });

        // Soft delete: l'account esiste ancora ma è disattivato e la sessione è chiusa
        $this->assertFalse(Auth::isLogged());
        $this->assertFalse((bool) Users::find($oldUser->id)->is_active);

        $_POST = [];
    }

    public function testDeleteUserWrongPassword(): void{
        $oldUser = $this->createUser();

        $_SESSION['user'] = $oldUser;
        $_POST = ['password' => $oldUser->password];   // l'hash non è la password: deve essere rifiutato

        $user = new User();

        $this->assertRedirectsTo(URL . 'user/delete', function () use ($user) {
            $user->finalDelete();
        });

        $this->assertTrue(Auth::isLogged());
        $this->assertTrue((bool) Users::find($oldUser->id)->is_active);

        $_POST = [];
    }

    public function testDeleteUserAlreadyDeleted(): void{
        // Utente in sessione ma non più presente nel DB (id inesistente).
        // Auth::requireLogin() (chiamata all'inizio di User::delete()) rileva che l'utente non esiste,
        // chiude la sessione e rimanda al login: il ramo "Utente non trovato" del controller non viene raggiunto.
        $ghost = new Users();
        $ghost->id = -1;
        $_SESSION['user'] = $ghost;

        $user = new User();
        $this->assertRedirectsTo(URL . 'authenticator/login', function () use ($user) {
            $user->delete();
        });

        $this->assertFalse(Auth::isLogged());
        $this->assertSame('Il tuo account è stato disattivato.', $_SESSION['errors']);
        $this->assertNull(Users::find(-1));
    }
}
