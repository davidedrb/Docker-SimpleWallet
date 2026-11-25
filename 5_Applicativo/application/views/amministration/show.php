<?php
// Variabili attese dal controller (Administrator::show):
// - $user    : models\Users dell'utente selezionato
// - $errors  : array di eventuali errori
// - $success : string|null messaggio di conferma

$user    = $user ?? ($_SESSION['user'] ?? null);
$errors  = $errors ?? [];
$success = $success ?? null;

$isActive = in_array($user->is_active ?? null, [1, '1', true], true);

$formatDate = function ($value) {
    return !empty($value)
        ? date('Y-m-d H:i', strtotime((string) $value))
        : '—';
};
$balance = number_format((float) ($user->balance ?? 0), 2, ',', '.');
$spendingLimit = number_format((float) ($user->spending_limit ?? 0), 2, ',', '.');

$isPositive = (float) ($user->balance ?? 0) >= 0;
?>
<div class="app-layout">
    <?php require_once __DIR__ . '/../shared/navbar.php'; ?>
    <main class="main-content">
        <header class="dashboard-header">
            <div class="welcome-box">
                <h1>Dettaglio Utente</h1>
            </div>
        </header>
        <?php if ($success): ?>
            <p class="auth-notice">
                <?= htmlspecialchars($success) ?>
            </p>
        <?php endif; ?>
        <?php if (!empty($errors['generic'])): ?>
            <p class="form-error form-error-banner">
                <?= htmlspecialchars($errors['generic']) ?>
            </p>
        <?php endif; ?>
        <section class="detail-card">
            <div class="detail-hero <?= $isActive ? 'detail-hero-in' : 'detail-hero-out' ?>">
                <span class="detail-hero-type">
                    <?= htmlspecialchars($user->role ?? '—') ?>
                </span>
                <p class="detail-hero-amount">
                    <?= htmlspecialchars(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) ?>
                </p>
                <span class="badge <?= $isActive ? 'badge-yes' : 'badge-no' ?>">
                    <?= $isActive ? 'Account attivo' : 'Account disattivato' ?>
                </span>
            </div>
            <dl class="detail-grid">
                <div class="detail-item">
                    <dt>Nome</dt>
                    <dd><?= htmlspecialchars($user->first_name ?? '—') ?></dd>
                </div>
                <div class="detail-item">
                    <dt>Cognome</dt>
                    <dd><?= htmlspecialchars($user->last_name ?? '—') ?></dd>
                </div>
                <div class="detail-item">
                    <dt>Email</dt>
                    <dd><?= htmlspecialchars($user->email ?? '—') ?></dd>
                </div>
                <div class="detail-item">
                    <dt>Ruolo</dt>
                    <dd><?= htmlspecialchars($user->role ?? '—') ?></dd>
                </div>
                <?php if ($user->role !== 'admin'): ?>
                    <div class="detail-item">
                        <dt>Saldo attuale</dt>
                        <dd class="<?= $isPositive ? 'positive-balance' : 'negative-balance' ?>">
                            CHF <?= $balance ?>
                        </dd>
                    </div>
                    <div class="detail-item">
                        <dt>Limite di spesa mensile</dt>
                        <dd>CHF <?= $spendingLimit ?></dd>
                    </div>
                <?php endif; ?>
                <div class="detail-item">
                    <dt>Registrato il</dt>
                    <dd><?= htmlspecialchars($formatDate($user->created_at ?? null)) ?></dd>
                </div>
                <div class="detail-item">
                    <dt>Ultima modifica</dt>
                    <dd><?= htmlspecialchars($formatDate($user->updated_at ?? null)) ?></dd>
                </div>
            </dl>
            <div class="detail-actions">
                <form action="<?= URL ?>administrator/edit/" method="POST">
                    <input type="hidden" name="user_id"
                           value="<?= htmlspecialchars((string) $user->id) ?>">
                    <button type="submit" class="button button-primary button-inline">Modifica</button>
                </form>
                <a href="<?= URL ?>administrator/archive" class="button button-outline">Torna all'archivio</a>
            </div>
        </section>
    </main>
</div>