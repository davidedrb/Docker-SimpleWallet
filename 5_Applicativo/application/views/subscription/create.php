<?php
// Variabili attese dal controller (Subscriptions::create):
// - $errors : array  errori di validazione (Flash::get('errors', []))
// - $old    : array  valori reinseriti dopo un errore (Flash::get('old', []))
// Il form invia i dati a subscription/store (POST):
// name, price, first_payment_date, frequency_months, is_active
$user   = $user ?? ($_SESSION['user'] ?? null);
$errors = $errors ?? [];
$old    = $old ?? [];

// Etichette leggibili per la frequenza espressa in mesi (le stesse di subscription/index)
$frequencyLabels = [
    1  => 'Mensile',
    3  => 'Trimestrale',
    6  => 'Semestrale',
    12 => 'Annuale',
];

$frequency = $old['frequency_months'] ?? 1;
$isActive  = $old['is_active'] ?? 1;
?>

<div class="app-layout">
    <?php require_once __DIR__ . '/../shared/navbar.php'; ?>
    <main class="main-content">
        <header class="dashboard-header">
            <div class="welcome-box">
                <h1>Nuovo Abbonamento</h1>
            </div>
        </header>

        <?php if (!empty($errors['generic'])): ?>
            <p class="form-error form-error-banner"><?= htmlspecialchars($errors['generic']) ?></p>
        <?php endif; ?>

        <section class="form-card">
            <div class="section-header">
                <h2>Dati dell'abbonamento</h2>
            </div>

            <form class="form-body" action="<?= URL ?>subscription/store" method="POST" novalidate>
                <div class="form-group">
                    <label for="name">Nome</label>
                    <input type="text" id="name" name="name" maxlength="100"
                           value="<?= htmlspecialchars($old['name'] ?? '') ?>"
                           placeholder="Es. Netflix, Palestra, Spotify" required>
                    <?php if (!empty($errors['name'])): ?>
                        <p class="form-error"><?= htmlspecialchars($errors['name']) ?></p>
                    <?php endif; ?>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="price">Prezzo (CHF)</label>
                        <input type="number" id="price" name="price" maxlength="12" inputmode="decimal"
                               value="<?= htmlspecialchars($old['price'] ?? '') ?>"
                               placeholder="0.00" required>
                        <?php if (!empty($errors['price'])): ?>
                            <p class="form-error"><?= htmlspecialchars($errors['price']) ?></p>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="frequency_months">Frequenza</label>
                        <select id="frequency_months" name="frequency_months" required>
                            <?php foreach ($frequencyLabels as $months => $label): ?>
                                <option value="<?= $months ?>" <?= (string) $frequency === (string) $months ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($label) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (!empty($errors['frequency_months'])): ?>
                            <p class="form-error"><?= htmlspecialchars($errors['frequency_months']) ?></p>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="form-group">
                    <label for="first_payment_date">Data del primo pagamento</label>
                    <input type="text" id="first_payment_date" name="first_payment_date" maxlength="10"
                           placeholder="yyyy-MM-dd" pattern="\d{4}-\d{2}-\d{2}" autocomplete="off"
                           value="<?= htmlspecialchars($old['first_payment_date'] ?? date('Y-m-d')) ?>" required>
                    <p class="form-hint">Formato yyyy-MM-dd (es. 2026-11-09).</p>
                    <?php if (!empty($errors['first_payment_date'])): ?>
                        <p class="form-error"><?= htmlspecialchars($errors['first_payment_date']) ?></p>
                    <?php endif; ?>
                </div>

                <div class="form-actions">
                    <button type="submit" class="button button-primary button-inline">Crea abbonamento</button>
                    <a href="<?= URL ?>subscription/index" class="button button-outline">Annulla</a>
                </div>
            </form>
        </section>
    </main>
</div>