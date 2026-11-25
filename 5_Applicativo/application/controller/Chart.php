<?php

namespace controller;

use libs\Auth;
use models\Payments;
use models\Users;
class Chart
{

    public function __construct()
    {
        Auth::requireLogin();
    }

    public function index()
    {
        $user = Users::find($_SESSION['user']->id);
        // BUG FIX: se l'utente non esiste più $user è null e $user->id dava errore, il controllo seguente non serviva a niente
        $userId = $user->id ?? null;
        if (!$userId) {
            header('Location: ' . URL . 'authenticator/login');
            exit;
        }

        // Grafico a torta: totale speso per tipo di metodo di pagamento
        $usage = Payments::getPaymentMethodUsage($userId);
        $pieLabels = $usage->keys()->all();
        $pieValues = $usage->values()->map(fn($total) => (float) $total)->all();

        // Grafico lineare: spesa accumulata giorno per giorno nel mese corrente (si ferma a oggi)
        $dailyTotals = Payments::getMonthlyExpensesByDay($userId);
        $monthPrefix = date('Y-m-');
        $daysInMonth = (int) date('t');
        $today = (int) date('j');
        $running = 0;
        $lineLabels = [];
        $lineValues = [];
        for ($day = 1; $day <= $daysInMonth; $day++) {
            $running += (float) ($dailyTotals[$day] ?? 0);
            $lineLabels[] = $monthPrefix . sprintf('%02d', $day);      // yyyy-MM-dd
            $lineValues[] = $day <= $today ? round($running, 2) : null;
        }
        $spendingLimit = (float) $user['spending_limit'];

        $withCharts = true;     // header.php carica Chart.js solo nelle pagine che lo chiedono

        $pageTitle = 'Grafici';

        require 'application/views/shared/header.php';
        require 'application/views/charts/index.php';
        require 'application/views/shared/footer.php';
    }
}