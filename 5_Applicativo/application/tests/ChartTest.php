<?php

use controller\Chart;

require_once __DIR__ . '/support/CreatesTestData.php';

class ChartTest extends ControllerTestCase
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
        new Chart();
    }

    public function testIndexRendersChartsWithoutPayments(): void
    {
        $_SESSION['user'] = $this->createUser();

        $chart = new Chart();
        $output = $this->assertNoRedirect(function () use ($chart) {
            $chart->index();
        });

        $this->assertNotEmpty($output);
    }

    public function testIndexRendersChartsWithPayments(): void
    {
        $user     = $this->createUser();
        $category = $this->createCategory($user->id);
        $method   = $this->createMethod($user->id);

        // Uscite del mese corrente (alimentano torta e grafico lineare) e una Entrata
        $this->createPayment($user, $category, $method, ['amount' => 15.50, 'type' => 'Uscita']);
        $this->createPayment($user, $category, $method, ['amount' => 4.50, 'type' => 'Uscita']);
        $this->createPayment($user, $category, $method, ['amount' => 100, 'type' => 'Entrata']);

        $_SESSION['user'] = $user;

        $chart = new Chart();
        $output = $this->assertNoRedirect(function () use ($chart) {
            $chart->index();
        });

        $this->assertNotEmpty($output);
    }
}
