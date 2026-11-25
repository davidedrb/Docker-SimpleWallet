<?php
// Variabili attese dal controller (Categories::index):
// - $categories : Collection|array di models\Categories (id, name, is_active)
// - $success    : string|null  messaggio di conferma (Flash::get('success'))
// - $errors     : array        eventuali errori generici (Flash::get('errors', []))
$categories = $categories ?? [];
$success    = $_SESSION['success'] ?? null;
$errors     = $errors ?? [];
?>

<div class="app-layout">
    <?php require_once __DIR__ . '/../shared/navbar.php'; ?>
    <main class="main-content">
        <header class="dashboard-header">
            <div class="welcome-box">
                <h1>Categorie</h1>
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
                <h2>Le tue categorie</h2>
                <a href="<?= URL ?>category/create" class="button button-primary button-inline">Nuova Categoria</a>
            </div>

            <div class="transactions-table-container">
                <table class="transactions-table">
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th class="col-actions">Azioni</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($categories)): ?>
                            <tr>
                                <td colspan="3" class="empty-state">Nessuna categoria creata, aggiungine una nuova.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($categories as $category): ?>
                                <?php if($category->is_active): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($category->name ?? '') ?></td>
                                        <td class="col-actions">
                                            <div class="actions-cell">
                                                <form action="<?= URL ?>category/edit/<?= $category->id ?>" method="post">
                                                    <input type="number" id="id_category" name="id_category" value="<?= $category->id ?>" hidden="hidden">
                                                    <button type="submit" class="button button-sm button-outline">Modifica</button>
                                                </form>

                                                <form action="<?= URL ?>category/delete/<?= $category->id ?>" method="POST"
                                                      onsubmit="return confirm('Eliminare definitivamente questa categoria?');">
                                                    <input type="number" id="id_category" name="id_category" value="<?= $category->id ?>" hidden="hidden">
                                                    <button type="submit" class="button button-sm button-danger">Elimina</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endif ?>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</div>