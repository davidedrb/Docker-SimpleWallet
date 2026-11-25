<?php
// Variabili attese dal controller (Categories::create):
// - $errors : array  errori di validazione (Flash::get('errors', []))
// - $old    : array  valori reinseriti dopo un errore (Flash::get('old', []))
// Il form invia i dati a categories/store (POST): name, is_active
$user   = $user ?? ($_SESSION['user'] ?? null);
$errors = $errors ?? [];
$old    = $old ?? [];

// Di default una nuova categoria nasce attiva
$isActive = $old['is_active'] ?? 1;
?>

<div class="app-layout">
    <?php require_once __DIR__ . '/../shared/navbar.php'; ?>
    <main class="main-content">
        <header class="dashboard-header">
            <div class="welcome-box">
                <h1>Nuova Categoria</h1>
            </div>
        </header>

        <?php if (!empty($errors['generic'])): ?>
            <p class="form-error form-error-banner"><?= htmlspecialchars($errors['generic']) ?></p>
        <?php endif; ?>

        <section class="form-card">
            <div class="section-header">
                <h2>Dati della categoria</h2>
            </div>

            <form class="form-body" action="<?= URL ?>category/store" method="POST" novalidate>
                <div class="form-group">
                    <label for="name">Nome</label>
                    <input type="text" id="name" name="name" maxlength="100"
                           value="<?= htmlspecialchars($old['name'] ?? '') ?>"
                           placeholder="Es. Alimentari, Trasporti, Svago" required>
                    <?php if (!empty($errors['name'])): ?>
                        <p class="form-error"><?= htmlspecialchars($errors['name']) ?></p>
                    <?php endif; ?>
                    <?php if (!empty($errors['duplicated_name'])): ?>
                        <p class="form-error"><?= htmlspecialchars($errors['duplicated_name']) ?></p>
                    <?php endif; ?>
                </div>

                <div class="form-actions">
                    <button type="submit" class="button button-primary button-inline">Crea categoria</button>
                    <a href="<?= URL ?>category/index" class="button button-outline">Annulla</a>
                </div>
            </form>
        </section>
    </main>
</div>