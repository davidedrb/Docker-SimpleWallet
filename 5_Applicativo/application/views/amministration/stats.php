<div class="app-layout">

    <?php require_once __DIR__ . '/../shared/navbar.php'; ?>

    <main class="main-content">
        <header class="dashboard-header">

            <div class="welcome-box">
                <h1>Bentornato, <span class="username"><?= htmlspecialchars($user['first_name']) ?></span></h1>
            </div>

            <div class="dashboard-info">
                <section class="info-box">
                    <h2>Totale</h2>
                    <p class="info-value-neutral">
                        <?= htmlspecialchars($totalUsers) ?> utenti</p>
                    </p>
                </section>

                <section class="info-box">
                    <h2>Attivi</h2>
                    <p class="info-value">
                        <?= htmlspecialchars($activeUsers) ?> utenti</p>
                </section>

                <section class="info-box">
                    <h2>Disattivati</h2>
                    <p class="info-value info-value-negative">
                        <?= htmlspecialchars($inactiveUsers) ?> utenti</p>
                </section>
            </div>

            <br>
            <hr>

            <div class="welcome-box">
                <h1>Pagamenti</h1>
            </div>

            <div class="dashboard-info">
                <section class="info-box">
                    <h2>Totale</h2>
                    <p class="info-value-neutral">
                        <?= htmlspecialchars($totalPayments) ?> pagamenti</p>
                    </p>
                </section>

                <section class="info-box">
                    <h2>Attivi</h2>
                    <p class="info-value">
                        <?= htmlspecialchars($activePayments) ?> pagamenti</p>
                </section>

                <section class="info-box">
                    <h2>Non Attivi</h2>
                    <p class="info-value info-value-negative">
                        <?= htmlspecialchars($inactivePayments) ?> pagamenti</p>
                </section>
            </div>

            <br>

            <div class="dashboard-info">
                <section class="info-box">
                    <h2>Cestinati</h2>
                    <p class="info-value-neutral">
                        <?= htmlspecialchars($trashedPayments) ?> pagamenti</p>
                    </p>
                </section>

                <section class="info-box">
                    <h2>Completati</h2>
                    <p class="info-value">
                        <?= htmlspecialchars($completedPayments) ?> pagamenti</p>
                </section>

                <section class="info-box">
                    <h2>Da Pagare</h2>
                    <p class="info-value info-value-negative">
                        <?= htmlspecialchars($uncompletedPayments) ?> pagamenti</p>
                </section>
            </div>


        </header>

    </main>
</div>