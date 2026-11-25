<div class="app-layout">

    <?php require_once __DIR__ . '/../shared/navbar.php'; ?>

    <main class="main-content">
        <header class="dashboard-header">

            <div class="welcome-box">
                <h1>Bentornato, <span class="username"><?= htmlspecialchars($user['first_name']) ?></span></h1>
            </div>

            <div class="dashboard-info">
                <section class="info-box">
                    <h2>Limite di Spesa Mensile</h2>
                    <p class="info-value-neutral">
                        CHF <?= number_format((float) $user['spending_limit'], 2, ',', '.') ?>
                    </p>
                </section>

                <section class="info-box">
                    <h2>Spese Mensili</h2>
                    <p class="info-value<?= $user['monthly_expenses'] > $user['spending_limit'] ? ' info-value-alert' : '' ?>">
                        CHF <?= number_format((float) $user['monthly_expenses'], 2, ',', '.') ?>
                    </p>
                </section>

                <section class="info-box">
                    <h2>Saldo Attuale</h2>
                    <p class="info-value<?= $user['balance'] < 0 ? ' info-value-negative' : '' ?>">
                        CHF <?= number_format((float) $user['balance'], 2, ',', '.') ?>
                    </p>
                </section>
            </div>
        </header>

        <?php if ($success): ?>
            <p class="auth-notice"><?= htmlspecialchars($success) ?></p>
        <?php endif; ?>

        <section class="transactions-section">
            <div class="section-header">
                <h2>Ultime Transazioni</h2>
                <a href="<?= URL ?>payment/index" class="link-quiet">Vedi storico completo</a>
            </div>

            <div class="transactions-table-container">
                <table class="transactions-table">
                    <thead>
                    <tr>
                        <th>Data</th>
                        <th>Descrizione</th>
                        <th>Categoria</th>
                        <th>Metodo</th>
                        <th>Tipo</th>
                        <th class="col-amount">Importo</th>
                    </tr>
                    </thead>

                    <tbody>
                    <?php if (empty($payments)): ?>
                        <tr>
                            <td colspan="6" class="empty-state">Nessuna transazione disponibile.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($payments as $payment): ?>
                            <tr>
                                <td><?= htmlspecialchars(!empty($payment->transaction_date) ? date('Y-m-d', strtotime((string) $payment->transaction_date)) : '') ?></td>
                                <td><?= htmlspecialchars($payment->description ?? '') ?></td>
                                <td><?= htmlspecialchars($payment->category->name ?? '—') ?></td>
                                <td><?= htmlspecialchars($payment->payment_method->type ?? '—') ?></td>
                                <td><?= htmlspecialchars($payment->type ?? '-') ?></td>
                                <?php if ($payment->type === 'Uscita'): ?>
                                    <td class="col-amount amount-negative"><?= htmlspecialchars('-' . $payment->amount) ?></td>
                                <?php elseif($payment->type === 'Entrata'): ?>
                                    <td class="col-amount amount-positive"><?= htmlspecialchars('+' . $payment->amount) ?></td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</div>