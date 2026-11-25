<?php

namespace controller;

use libs\Auth;
use libs\Flash;
use libs\Validator;
use models\Payments;
use models\Users;

// PDF e Opzioni
use Dompdf\Dompdf;
use Dompdf\Options;

class Statement
{
    private Validator $validator;

    public function __construct()
    {
        Auth::requireLogin();
        $this->validator = new Validator();
    }

    public function index()
    {
        $id = $_SESSION['user']->id;

        $user = Users::find($id);

        if (!$user) {
            Flash::set('errors', 'Utente non trovato');
            header('Location: ' . URL . 'payment/index');
            exit;
        }

        $firstPayment = Payments::where('user_id', '=', $id)
            ->where('is_active', '=', TRUE)
            ->where('is_trashed', '=', FALSE)
            ->orderBy('transaction_date', 'asc')
            ->first();

        $firstYear = $firstPayment
            ? (int) date('Y', strtotime($firstPayment->transaction_date))
            : (int) date('Y');

        $errors = Flash::get('errors', []);
        $success = Flash::get('success', null);

        $pageTitle = 'Estratto conto';

        require 'application/views/shared/header.php';
        require 'application/views/statement/index.php';
        require 'application/views/shared/footer.php';
    }


    public function generatePDF()
    {
        $errors = [];

        $user = Users::find($_SESSION['user']->id);

        if (!$user) {
            Flash::set('errors', 'Utente non trovato');
            header('Location: ' . URL . 'statement/index');
            exit;
        }

        if ($user->role === 'admin') {
            Flash::set('errors', 'Come amministratore non disponi di questa funzionalità');
            header('Location: ' . URL . 'statement/index');
            exit;
        }

        $firstPayment = Payments::where('user_id', $user->id)
            ->where('is_active', true)
            ->where('is_trashed', false)
            ->orderBy('transaction_date', 'asc')
            ->first();

        $firstYear = $firstPayment
            ? (int) date('Y', strtotime($firstPayment->transaction_date))
            : (int) date('Y');

        // Recupero dei filtri
        $year = $_GET['year'] ?? date('Y');
        $month = $_GET['month'] ?? '';

        // Verifico se è stato selezionato tutto lo storico
        $allPeriod = $year === 'all';

        if (!$allPeriod) {

            $year = filter_var($year, FILTER_VALIDATE_INT);

            if (!$year || $year < $firstYear || $year > (int) date('Y')) {
                Flash::set('errors', 'L\'anno non è stato selezionato o non è una data valida');
                header('Location: ' . URL . 'statement/index');
                exit;
            }

            // Definizione del periodo
            if ($month !== '') {
                $month = filter_var($month, FILTER_VALIDATE_INT);

                if (!$month || $month < 1 || $month > 12) {
                    Flash::set('errors', 'Il mese selezionato non è valido');
                    header('Location: ' . URL . 'statement/index');
                    exit;
                }

                $startDate = sprintf('%04d-%02d-01 00:00:00', $year, $month);
                $endDate = date('Y-m-t 23:59:59', strtotime($startDate));

            } else {
                $startDate = $year . '-01-01 00:00:00';
                $endDate = $year . '-12-31 23:59:59';
            }

        } else {
            // Intero storico: nessun filtro temporale
            $month = '';
            $startDate = null;
            $endDate = null;
        }

        // Recupero delle transazioni completate
        $completedQuery = Payments::where('user_id', $user->id)
            ->where('is_active', true)
            ->where('is_trashed', false)
            ->where('is_completed', 'Yes');

        if (!$allPeriod) {
            $completedQuery->where('transaction_date', '>=', $startDate);
        }

        $completedPayments = $completedQuery->get();

        // Calcolo del saldo movimentato dalla data iniziale a oggi
        $netAmount = 0;

        foreach ($completedPayments as $payment) {
            if ($payment->type === 'Entrata') {
                $netAmount += (float) $payment->amount;
            } elseif ($payment->type === 'Uscita') {
                $netAmount -= (float) $payment->amount;
            }
        }

        // Ricostruzione del saldo iniziale
        $openingBalance = (float) $user->balance - $netAmount;

        // Recupero delle transazioni da mostrare nel PDF
        $transactionsQuery = Payments::with(['category', 'payment_method'])
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->where('is_trashed', false);

        if (!$allPeriod) {
            $transactionsQuery->whereBetween('transaction_date', [$startDate, $endDate]);
        }

        $transactions = $transactionsQuery
            ->orderBy('transaction_date', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        // Calcolo del saldo progressivo e del totale
        $runningBalance = $openingBalance;
        $totalAmount = 0;

        foreach ($transactions as $payment) {
            if ($payment->is_completed === 'Yes') {
                if ($payment->type === 'Entrata') {
                    $runningBalance += (float) $payment->amount;
                    $totalAmount += (float) $payment->amount;

                } elseif ($payment->type === 'Uscita') {
                    $runningBalance -= (float) $payment->amount;
                    $totalAmount -= (float) $payment->amount;
                }
            }

            // Saldo dopo questa transazione
            $payment->balance_after = $runningBalance;
        }

        $finalBalance = $runningBalance;

        // Funzione per proteggere il contenuto HTML
        $escape = function ($value) {
            return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
        };

        // Definizione del periodo da mostrare
        $period = $allPeriod
            ? 'Intero storico'
            : ($month !== ''
                ? sprintf('%02d/%04d', $month, $year)
                : (string) $year);

        // Creazione del documento HTML
        ob_start();

        require 'application/views/statement/pdf.php';

        $html = ob_get_clean();

        // Configurazione Dompdf
        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');

        // Formato A4 orizzontale
        $dompdf->setPaper('A4', 'landscape');

        $dompdf->render();

        // Numero di pagina
        $canvas = $dompdf->getCanvas();

        $canvas->page_text(
            750,
            570,
            "Pagina {PAGE_NUM} di {PAGE_COUNT}",
            null,
            8,
            [0.4, 0.4, 0.4]
        );

        // Download del PDF
        $filename = $allPeriod
            ? 'estratto_conto_completo'
            : 'estratto_conto_' . $year;

        if (!$allPeriod && $month !== '') {
            $filename .= '_' . sprintf('%02d', $month);
        }

        $dompdf->stream($filename . '.pdf', [
            'Attachment' => true
        ]);

        exit;
    }
}