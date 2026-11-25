<?php

use controller\Home;

require_once __DIR__ . '/support/CreatesTestData.php';

class HomeTest extends ControllerTestCase
{
    use CreatesTestData;

    protected function tearDown(): void
    {
        $this->cleanTestData();
        $_SESSION = [];
        parent::tearDown();
    }

    public function testConstructorRedirectsWhenNotLogged(): void
    {
        $_SESSION = [];

        $this->expectException(RedirectException::class);
        new Home();
    }

    public function testIndexRendersDashboardForUser(): void
    {
        $user = $this->createUser();
        $this->createPayment($user, $this->createCategory($user->id), $this->createMethod($user->id));
        $_SESSION['user'] = $user;

        $home = new Home();
        $output = $this->assertNoRedirect(function () use ($home) {
            $home->index();
        });

        $this->assertNotEmpty($output);
    }

    public function testIndexRendersDashboardForUserWithoutPayments(): void
    {
        $_SESSION['user'] = $this->createUser();

        $home = new Home();
        $output = $this->assertNoRedirect(function () use ($home) {
            $home->index();
        });

        $this->assertNotEmpty($output);
    }

    public function testIndexRedirectsAdminToAdministrator(): void
    {
        $_SESSION['user'] = $this->createUser(['role' => 'admin']);

        $home = new Home();
        $this->assertRedirectsTo(URL . 'administrator/stats', function () use ($home) {
            $home->index();
        });
    }
}
