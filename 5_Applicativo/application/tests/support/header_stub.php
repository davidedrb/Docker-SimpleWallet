<?php
/**
 * Stub usato SOLO dai test (caricato da tests/bootstrap.php).
 *
 * PHP risolve una chiamata non qualificata come header() prima nel namespace
 * corrente (controller\header, libs\header) e solo dopo nella funzione globale.
 * Definendo qui una header() "finta", il codice si ferma con un'eccezione
 * PRIMA di raggiungere exit, senza modificare né controller né libs.
 */

class RedirectException extends \Error
{
    public function __construct(public string $location)
    {
        parent::__construct("Redirect verso: $location");
    }
}

// Namespace dell'app in cui viene chiamata header()
foreach (['controller', 'libs'] as $ns) {
    eval("
        namespace $ns;

        function header(string \$header, bool \$replace = true, int \$code = 0): void
        {
            if (stripos(\$header, 'Location:') === 0) {
                throw new \\RedirectException(trim(substr(\$header, 9)));
            }
        }

        // In CLI non c'è una sessione attiva: evita il warning
        function session_regenerate_id(bool \$delete = false): bool
        {
            return true;
        }
    ");
}
