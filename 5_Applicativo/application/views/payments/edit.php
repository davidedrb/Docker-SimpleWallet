<?php
// Variabili attese dal controller (Payment::edit):
// - $payment    : models\Payment da modificare
// - $categories : Collection|array di models\Categories (id, name, is_active)
// - $methods    : Collection|array di models\Methods (id, name, type, is_active)
// - $errors     : array  errori di validazione (Flash::get('errors', []))
// - $old        : array  valori reinseriti dopo un errore (Flash::get('old', []))
// Il form invia i dati a payment/update/<id> (POST):
// type, amount, category_id, payment_method_id, transaction_date, is_completed, description
$user       = $user ?? ($_SESSION['user'] ?? null);
$categories = $categories ?? [];
$methods    = $methods ?? [];
$errors     = $errors ?? [];
$old        = $old ?? [];

// Valori correnti (i dati "old" hanno la precedenza dopo un errore di validazione)
$type        = $old['type'] ?? $payment->type ?? 'Uscita';
$is_completed = $old['is_completed'] ?? $payment->is_completed ?? 'Yes';
$category_id  = $old['category_id'] ?? $payment->category_id ?? '';
$method_id    = $old['payment_method_id'] ?? $payment->payment_method_id ?? '';

// Normalizzo eventuali valori booleani (1/0) nei valori dell'ENUM ('Yes'/'No')
if (in_array($is_completed, [1, '1', true], true)) {
    $isCompleted = 'Yes';
} elseif (in_array($is_completed, [0, '0', false], true)) {
    $isCompleted = 'No';
}

// la data si mostra e si scrive come yyyy-MM-dd HH:mm
$rawDate   = $old['transaction_date'] ?? $payment->transaction_date ?? '';
$dateValue = $rawDate ? date('Y-m-d H:i', strtotime((string) $rawDate)) : '';
?>

<div class="app-layout">
    <?php require_once __DIR__ . '/../shared/navbar.php'; ?>
    <main class="main-content">
        <header class="dashboard-header">
            <div class="welcome-box">
                <h1>Modifica Pagamento</h1>
            </div>
        </header>

        <?php if (!empty($errors['generic'])): ?>
            <p class="form-error form-error-banner"><?= htmlspecialchars($errors['generic']) ?></p>
        <?php endif; ?>

        <section class="form-card">
            <form class="form-body" action="<?= URL ?>payment/update" method="POST">
                <input type="hidden" name="payment_id" value="<?= $payment->id ?>">
                <div class="form-group">
                    <label>Tipo di movimento</label>
                    <div class="segmented">
                        <input type="radio" id="type-uscita" name="type" value="Uscita" <?= $type === 'Uscita' ? 'checked' : '' ?>>
                        <label for="type-uscita" class="segmented-out">Uscita</label>

                        <input type="radio" id="type-entrata" name="type" value="Entrata" <?= $type === 'Entrata' ? 'checked' : '' ?>>
                        <label for="type-entrata" class="segmented-in">Entrata</label>
                    </div>
                    <?php if (!empty($errors['type'])): ?>
                        <p class="form-error"><?= htmlspecialchars($errors['type']) ?></p>
                    <?php endif; ?>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="amount">Importo (CHF)</label>
                        <input type="text" id="amount" name="amount" maxlength="12" inputmode="decimal"
                               value="<?= htmlspecialchars($old['amount'] ?? $payment->amount ?? '') ?>"
                               placeholder="0.00" required>
                        <?php if (!empty($errors['amount'])): ?>
                            <p class="form-error"><?= htmlspecialchars($errors['amount']) ?></p>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="transaction_date">Data e ora</label>
                        <input type="datetime-local" id="transaction_date" name="transaction_date"
                               autocomplete="off" value="<?= htmlspecialchars($dateValue) ?>">
                        <p class="form-hint">Formato yyyy-MM-dd HH:mm (l'ora è facoltativa).</p>
                        <?php if (!empty($errors['transaction_date'])): ?>
                            <p class="form-error"><?= htmlspecialchars($errors['transaction_date']) ?></p>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="category_id">Categoria</label>
                        <select id="category_id" name="category_id" required>
                            <?php foreach ($categories as $category): ?>
                                <?php
                                // Le categorie disattivate restano visibili solo se sono quella già assegnata
                                $isCurrent = (string) $category_id === (string) $category->id;
                                if (isset($category->is_active) && !$category->is_active && !$isCurrent) continue;
                                ?>
                                <option value="<?= $category->id ?>" <?= $isCurrent ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($category->name ?? '') ?><?= isset($category->is_active) && !$category->is_active ? ' (disattivata)' : '' ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (!empty($errors['category_id'])): ?>
                            <p class="form-error"><?= htmlspecialchars($errors['category_id']) ?></p>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="payment_method_id">Metodo di pagamento</label>
                        <select id="payment_method_id" name="payment_method_id" required>
                            <?php foreach ($methods as $method): ?>
                                <?php
                                $isCurrent = (string) $method_id === (string) $method->id;
                                if (isset($method->is_active) && !$method->is_active && !$isCurrent) continue;
                                $methodLabel = !empty($method->name) ? $method->name . ' (' . $method->type . ')' : ($method->type ?? '');
                                ?>
                                <option value="<?= $method->id ?>" <?= $isCurrent ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($methodLabel) ?><?= isset($method->is_active) && !$method->is_active ? ' (disattivo)' : '' ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (!empty($errors['payment_method_id'])): ?>
                            <p class="form-error"><?= htmlspecialchars($errors['payment_method_id']) ?></p>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="form-group">
                    <label for="is_completed">Stato del pagamento</label>
                    <select id="is_completed" name="is_completed">
                        <option value="Yes" <?= $is_completed === 'Yes' ? 'selected' : '' ?>>Effettuato</option>
                        <option value="No" <?= $is_completed === 'No' ? 'selected' : '' ?>>Non ancora effettuato</option>
                    </select>
                    <?php if (!empty($errors['is_completed'])): ?>
                        <p class="form-error"><?= htmlspecialchars($errors['is_completed']) ?></p>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="description">Descrizione <span class="label-optional">(facoltativa)</span></label>
                    <textarea id="description" name="description" maxlength="1000" rows="3"
                              placeholder="Es. Spesa settimanale, stipendio di ottobre..."><?= htmlspecialchars($old['description'] ?? $payment->description ?? '') ?></textarea>
                    <?php if (!empty($errors['description'])): ?>
                        <p class="form-error"><?= htmlspecialchars($errors['description']) ?></p>
                    <?php endif; ?>
                </div>

                <div class="form-actions">
                    <button type="submit" class="button button-primary button-inline">Salva modifiche</button>
                    <a href="<?= URL ?>payment/index" class="button button-outline">Annulla</a>
                </div>
            </form>
        </section>
    </main>
</div>