<?php
// Variabili attese dal controller (Methods::edit):
// - $method : models\Methods da modificare (id, name, type, is_active)
// - $errors : array  errori di validazione (Flash::get('errors', []))
// - $old    : array  valori reinseriti dopo un errore (Flash::get('old', []))
// - $typeSuggestions : array  tipi già usati dall'utente (es. Cash, Card), suggerimenti per il campo "tipo"
// Il form invia i dati a methods/update/<id> (POST): name, type, is_active
$user   = $user ?? ($_SESSION['user'] ?? null);
$errors = $errors ?? [];
$old    = $old ?? [];

$isActive = $old['is_active'] ?? $method->is_active ?? 0;
?>

<div class="app-layout">
    <?php require_once __DIR__ . '/../shared/navbar.php'; ?>
    <main class="main-content">
        <header class="dashboard-header">
            <div class="welcome-box">
                <h1>Modifica Metodo di Pagamento</h1>
            </div>
        </header>

        <?php if (!empty($errors['generic'])): ?>
            <p class="form-error form-error-banner"><?= htmlspecialchars($errors['generic']) ?></p>
        <?php endif; ?>

        <section class="form-card">
            <div class="section-header">
                <h2>Dati del metodo di pagamento</h2>
            </div>

            <form class="form-body" action="<?= URL ?>method/update/<?= htmlspecialchars($method->id) ?>" method="POST" novalidate>
                <input type="number" name="PaymentMethodsId" id="PaymentMethodsId" value="<?= htmlspecialchars($method->id)?>" hidden="hidden">

                <div class="form-row">
                    <div class="form-group">
                        <label for="name">Nome <span class="label-optional"></span></label>
                        <input type="text" id="name" name="name" maxlength="100"
                               value="<?= htmlspecialchars($old['name'] ?? $method->name ?? '') ?>"
                               placeholder="Es. Carta Visa personale">
                        <?php if (!empty($errors['name'])): ?>
                            <p class="form-error"><?= htmlspecialchars($errors['name']) ?></p>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="type">Tipo</label>
                        <input type="text" id="type" name="type" maxlength="100" list="type-suggestions"
                               value="<?= htmlspecialchars($old['type'] ?? $method->type ?? '') ?>"
                               placeholder="Es. Card" required>
                        <datalist id="type-suggestions">
                            <?php foreach ($typeSuggestions as $suggestion): ?>
                                <option value="<?= htmlspecialchars($suggestion) ?>"></option>
                            <?php endforeach; ?>
                        </datalist>
                        <?php if (!empty($errors['type'])): ?>
                            <p class="form-error"><?= htmlspecialchars($errors['type']) ?></p>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="button button-primary button-inline">Salva modifiche</button>
                    <a href="<?= URL ?>method/index" class="button button-outline">Annulla</a>
                </div>
            </form>
        </section>
    </main>
</div>