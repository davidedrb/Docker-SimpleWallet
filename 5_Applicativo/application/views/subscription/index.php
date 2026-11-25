<?php
// Variabili attese dal controller (Subscriptions::index):
// - $subscriptions : Collection|array di models\Subscriptions
//                    (id, name, price, firs_payment_date, frequency_months, is_active)
// - $success       : string|null  messaggio di conferma (Flash::get('success'))
// - $errors        : array        eventuali errori generici (Flash::get('errors', []))
$subscriptions = $subscriptions ?? [];
$success       = $_SESSION['success'] ?? null;
$errors        = $errors ?? [];

// Etichette leggibili per la frequenza espressa in mesi
$frequencyLabels = [
    1  => 'Mensile',
    3  => 'Trimestrale',
    6  => 'Semestrale',
    12 => 'Annuale',
]; ?>

<div class="app-layout">
    <?php require_once __DIR__ . '/../shared/navbar.php'; ?>
    <main class="main-content">
        <header class="dashboard-header">
            <div class="welcome-box">
                <h1>Abbonamenti</h1>
            </div>
        </header>

        <?php if ($success): ?>
            <p class="auth-notice"><?= htmlspecialchars($success) ?></p>
        <?php endif; ?>

        <?php if (!empty($errors['generic'])): ?>
            <p class="form-error form-error-banner"><?= htmlspecialchars($errors['generic']) ?></p>
        <?php endif; ?>

        <section class="transactions-section">
            <div class="section-header">
                <h2>I tuoi abbonamenti</h2>
                <a href="<?= URL ?>subscription/create" class="button button-primary button-inline">Nuovo Abbonamento</a>
            </div>

            <div class="transactions-table-container">
                <table class="transactions-table">
                    <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Prezzo</th>
                        <th>Frequenza</th>
                        <th>Primo pagamento</th>
                        <th class="col-actions">Azioni</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($subscriptions)): ?>
                        <tr>
                            <td colspan="6" class="empty-state">Nessun abbonamento registrato, aggiungine uno nuovo.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($subscriptions as $subscription): ?>
                            <?php if ($subscription->is_active): ?>
                                <tr>
                                    <td><?= htmlspecialchars($subscription->name ?? '') ?></td>
                                    <td class="col-amount-left">CHF <?= number_format((float) ($subscription->price ?? 0), 2, '.', '.') ?></td>
                                    <td><?= htmlspecialchars($frequencyLabels[$subscription->frequency_months ?? 0] ?? ($subscription->frequency_months . ' mesi')) ?></td>
                                    <td><?= htmlspecialchars(!empty($subscription->first_payment_date) ? date('Y-m-d', strtotime((string) $subscription->first_payment_date)) : '') ?></td>
                                    <td class="col-actions">
                                        <div class="actions-cell">
                                            <form action="<?= URL ?>subscription/edit/" method="POST">
                                                <input type="number" id="id_subscription" name="id_subscription" value="<?= $subscription->id ?>" hidden="hidden">
                                                <button type="submit" class="button button-sm button-outline">Modifica</button>
                                            </form>
                                            <form action="<?= URL ?>subscription/delete/" method="POST"
                                                  onsubmit="return confirm('Eliminare definitivamente questo abbonamento?');">
                                                <input type="number" id="id_subscription" name="id_subscription" value="<?= $subscription->id ?>" hidden="hidden">
                                                <button type="submit" class="button button-sm button-danger">Elimina</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</div>