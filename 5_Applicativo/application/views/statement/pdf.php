<?php
$escape = function ($value) {
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
};

$period = $month !== ''
    ? sprintf('%02d/%04d', $month, $year)
    : (string) $year;
?>

<!DOCTYPE html>
<html lang="it">
    <head>
        <meta charset="UTF-8">
        <style>
            <?= file_get_contents(__DIR__ . '/../../../public/css/pdf.css') ?>
        </style>
        <title>Estratto conto</title>
    </head>
    <body class="pdf-body">
        <!-- Intestazione -->
        <header class="pdf-header">
            <h1 class="pdf-title">ESTRATTO CONTO</h1>
            <div class="pdf-user-info">
                <span class="pdf-label">Intestatario:</span>
                <span class="pdf-user-name">
                        <?= $escape($user->first_name . ' ' . $user->last_name) ?>
                    </span>
            </div>
            <div class="pdf-period">
                <span class="pdf-label">Periodo di riferimento:</span>
                <span><?= $escape($period) ?></span>
            </div>
        </header>
        <!-- Tabella delle transazioni -->
        <main class="pdf-content">
            <table class="pdf-table">
                <thead class="pdf-table-header">
                    <tr>
                        <th>Data</th>
                        <th>Categoria</th>
                        <th>Descrizione</th>
                        <th>Metodo</th>
                        <th>Tipo</th>
                        <th>Stato</th>
                        <th class="pdf-text-right">Importo</th>
                        <th class="pdf-text-right">Saldo aggiornato</th>
                    </tr>
                </thead>
                <tbody class="pdf-table-body">
                    <?php if ($transactions->isEmpty()): ?>
                        <tr>
                            <td colspan="8" class="pdf-empty-message">Nessuna transazione presente nel periodo selezionato.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($transactions as $payment): ?>
                            <?php $isIncome = $payment->type === 'Entrata';
                                  $amount = (float) $payment->amount;
                                  $formattedAmount = number_format($amount, 2, '.', "'");
                                  $formattedBalance = number_format($payment->balance_after, 2, '.', "'");
                                  $signedAmount = $isIncome ? '+' . $formattedAmount : '-' . $formattedAmount; ?>
                            <tr class="pdf-table-row">
                                <td class="pdf-date"><?= date('d.m.Y H:i', strtotime($payment->transaction_date)) ?></td>
                                <td class="pdf-category"><?= $escape($payment->category?->name ?? '-') ?></td>
                                <td class="pdf-description"><?= $escape($payment->description ?: '-') ?></td>
                                <td class="pdf-method"><?= $escape($payment->payment_method?->name ?? '-') ?></td>
                                <td class="pdf-type"><?= $escape($payment->type) ?></td>
                                <td class="pdf-status"><?= $payment->is_completed === 'Yes' ? 'Completato' : 'Non completato' ?></td>
                                <td class="pdf-amount <?= $isIncome ? 'pdf-income' : 'pdf-expense' ?>"><?= $signedAmount ?> CHF</td>
                                <td class="pdf-balance"><?= $formattedBalance ?> CHF</td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    <!-- Totale finale -->
                    <tr class="pdf-total-row">
                        <td colspan="6" class="pdf-total-label">TOTALE</td>
                        <td class="pdf-total-amount"><?= number_format($totalAmount, 2, '.', "'") ?> CHF</td>
                        <td class="pdf-total-balance"><?= number_format($finalBalance, 2, '.', "'") ?> CHF</td>
                    </tr>
                </tbody>
            </table>
            <!-- Riepilogo -->
            <section class="pdf-summary">
                <div class="pdf-summary-item">
                    <span class="pdf-summary-label">Saldo iniziale:</span>
                    <span class="pdf-summary-value"><?= number_format($openingBalance, 2, '.', "'") ?> CHF</span>
                </div>
                <div class="pdf-summary-item">
                    <span class="pdf-summary-label">Saldo finale:</span>
                    <span class="pdf-summary-value"><?= number_format($finalBalance, 2, '.', "'") ?> CHF</span>
                </div>
            </section>
        </main>
        <!-- Piè di pagina -->
        <footer class="pdf-footer">
            Estratto conto generato il <?= date('d.m.Y H:i') ?>
        </footer>
    </body>
</html>