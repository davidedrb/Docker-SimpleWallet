<div class="app-layout">

    <?php require_once __DIR__ . '/../shared/navbar.php'; ?>

    <main class="main-content">
        <header class="dashboard-header">

            <div class="welcome-box">
                <h1>Grafici</h1>
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

        <section class="charts-section">
            <div class="linear-chart">
                <h2>Spese del mese rispetto al limite</h2>
                <div class="linear-chart-container">
                    <canvas id="lineChart"
                            data-labels="<?= htmlspecialchars(json_encode($lineLabels), ENT_QUOTES) ?>"
                            data-values="<?= htmlspecialchars(json_encode($lineValues), ENT_QUOTES) ?>"
                            data-limit="<?= htmlspecialchars((string) $spendingLimit, ENT_QUOTES) ?>"></canvas>
                </div>
            </div>

            <div class="pie-chart">
                <h2>Uso metodi di pagamento</h2>
                <?php if (empty($pieLabels)): ?>
                    <p class="empty-state">Non ci sono ancora spese da visualizzare.</p>
                <?php else: ?>
                    <div class="pie-chart-container">
                        <canvas id="pieChart"
                                data-labels="<?= htmlspecialchars(json_encode($pieLabels), ENT_QUOTES) ?>"
                                data-values="<?= htmlspecialchars(json_encode($pieValues), ENT_QUOTES) ?>"></canvas>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </main>
</div>