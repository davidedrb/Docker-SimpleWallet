<?php
// Variabili attese dal controller (Payment::show):
// - $payment : models\Payment con relazioni ->category e ->payment_method già caricate
// - $errors  : array  eventuali errori generici (Flash::get('errors', []))
// - $success : string|null messaggio di conferma (Flash::get('success'))
$user    = $user ?? ($_SESSION['user'] ?? null);
$errors  = $errors ?? [];
$success = $success ?? null;

$isOutgoing  = ($payment->type ?? '') === 'Uscita';
$isCompleted = in_array($payment->is_completed ?? null, ['Yes', 'yes', 1, '1', true], true);

// Date nel formato yyyy-MM-dd HH:mm, importo leggibile
$formatDate = function ($value) {
    return !empty($value) ? date('Y-m-d H:i', strtotime((string) $value)) : '—';
};
$amount = number_format((float) ($payment->amount ?? 0), 2, ',', '.');

$methodLabel = !empty($payment->payment_method->name)
    ? $payment->payment_method->name . ' (' . $payment->payment_method->type . ')'
    : ($payment->payment_method->type ?? '—');
?>

<div class="app-layout">
    <?php require_once __DIR__ . '/../shared/navbar.php'; ?>
    <main class="main-content">
        <header class="dashboard-header">
            <div class="welcome-box">
                <h1>Dettaglio Pagamento</h1>
            </div>
        </header>

        <?php if ($success): ?>
            <p class="auth-notice"><?= htmlspecialchars($success) ?></p>
        <?php endif; ?>

        <?php if (!empty($errors['generic'])): ?>
            <p class="form-error form-error-banner"><?= htmlspecialchars($errors['generic']) ?></p>
        <?php endif; ?>

        <section class="detail-card">

            <div class="detail-hero <?= $isOutgoing ? 'detail-hero-out' : 'detail-hero-in' ?>">
                <span class="detail-hero-type"><?= htmlspecialchars($payment->type ?? '-') ?></span>
                <p class="detail-hero-amount"><?= $isOutgoing ? '-' : '+' ?> CHF <?= $amount ?></p>
                <span class="badge <?= $isCompleted ? 'badge-yes' : 'badge-no' ?>">
                    <?= $isCompleted ? 'Effettuato' : 'Non ancora effettuato' ?>
                </span>
            </div>

            <dl class="detail-grid">
                <div class="detail-item">
                    <dt>Data transazione</dt>
                    <dd><?= htmlspecialchars($formatDate($payment->transaction_date ?? null)) ?></dd>
                </div>

                <div class="detail-item">
                    <dt>Categoria</dt>
                    <dd><?= htmlspecialchars($payment->category->name ?? '—') ?></dd>
                </div>

                <div class="detail-item">
                    <dt>Metodo di pagamento</dt>
                    <dd><?= htmlspecialchars($methodLabel) ?></dd>
                </div>

                <div class="detail-item">
                    <dt>Registrato il</dt>
                    <dd><?= htmlspecialchars($formatDate($payment->created_at ?? null)) ?></dd>
                </div>

                <div class="detail-item detail-item-wide">
                    <dt>Descrizione</dt>
                    <dd><?= !empty($payment->description) ? nl2br(htmlspecialchars($payment->description)) : '—' ?></dd>
                </div>

                <div class="detail-item">
                    <dt>Ultima modifica</dt>
                    <dd><?= htmlspecialchars($formatDate($payment->updated_at ?? null)) ?></dd>
                </div>
            </dl>

            <div class="detail-actions">
                <a href="<?= URL ?>trash/index" class="button button-outline">Torna al cestino</a>
                <form action="<?= URL ?>trash/delete/" method="POST"
                      onsubmit="return confirm('Eliminare definitivamente questo pagamento?');">
                    <input type="number" name="payment_id" id="payment_id" value="<?= htmlspecialchars($payment->id)?>" hidden="hidden">
                    <button type="submit" class="button button-danger">Elimina</button>
                </form>
            </div>
        </section>
    </main>
</div>