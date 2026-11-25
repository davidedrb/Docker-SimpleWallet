<?php

use PHPUnit\Framework\TestCase;

abstract class ControllerTestCase extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $_SESSION = [];
        $_POST = [];
        $_GET = [];

        if (!defined('URL')) {
            define('URL', 'http://localhost:8080/');
        }

        // I require delle view usano path relativi ('application/views/...')
        chdir(dirname(__DIR__, 2));
    }

    /**
     * Esegue $action e verifica che termini con un redirect verso $expected.
     */
    protected function assertRedirectsTo(string $expected, callable $action): void
    {
        ob_start();
        try {
            $action();
        } catch (RedirectException $e) {
            ob_end_clean();
            $this->assertSame($expected, $e->location);
            return;
        } catch (\Throwable $e) {
            // BUG FIX: con un'altra eccezione l'output buffer restava aperto e PHPUnit segnalava il test come "risky"
            ob_end_clean();
            throw $e;
        }
        ob_end_clean();
        $this->fail("Nessun redirect (atteso: $expected)");
    }

    /**
     * Esegue l'azione e verifica che NON avvenga alcun redirect.
     * Ritorna l'output generato (utile per controllare la view).
     */
    protected function assertNoRedirect(callable $action): string
    {
        ob_start();
        try {
            $action();
        } catch (RedirectException $e) {
            ob_end_clean();
            $this->fail("Redirect inatteso verso: {$e->location}");
        } catch (\Throwable $e) {
            ob_end_clean();   // BUG FIX: vedi assertRedirectsTo()
            throw $e;
        }
        return ob_get_clean();
    }


}
