<?php
// Variabili attese dal controller (Methods::index):
// - $methods : Collection|array di models\Methods (id, name, type, is_active)
// - $success : string|null  messaggio di conferma (Flash::get('success'))
// - $errors  : array        eventuali errori generici (Flash::get('errors', []))
$methods = $methods ?? [];
$success = $success ?? null;
$errors  = $errors ?? [];
?>

<div class="app-layout">
    <?php require_once __DIR__ . '/../shared/navbar.php'; ?>
    <main class="main-content">
        <header class="dashboard-header">
            <div class="welcome-box">
                <h1>Metodi di Pagamento</h1>
            </div>
        </header>

        <?php if ($success): ?>
            <p class="auth-notice"><?= htmlspecialchars($success) ?></p>
        <?php elseif ($errors): ?>
            <p class="auth-notice"><?= htmlspecialchars($errors) ?></p>
        <?php endif; ?>

        <?php if (!empty($errors['generic'])): ?>
            <p class="form-error form-error-banner"><?= htmlspecialchars($errors['generic']) ?></p>
        <?php endif; ?>

        <section class="transactions-section">
            <div class="section-header">
                <h2>I tuoi metodi di pagamento</h2>
                <a href="<?= URL ?>method/create" class="button button-primary button-inline">Nuovo Metodo di Pagamento</a>
            </div>

            <div class="transactions-table-container">
                <table class="transactions-table">
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>Tipo</th>
                            <th class="col-actions">Azioni</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($methods)): ?>
                            <tr>
                                <td colspan="4" class="empty-state">Nessun metodo di pagamento creato, aggiungine uno nuovo.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($methods as $method): ?>
                                <?php if ($method->is_active): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($method->name ?? '') ?></td>
                                        <td><?= htmlspecialchars($method->type ?? '') ?></td>
                                        <td class="col-actions">
                                            <div class="actions-cell">
                                                <form action="<?= URL ?>method/edit/" method="POST">
                                                    <input type="number" name="PaymentMethodsId" id="PaymentMethodsId" value="<?= $method->id ?>" hidden="hidden">
                                                    <button type="submit" class="button button-sm button-outline">Modifica</button>
                                                </form>

                                                <form action="<?= URL ?>method/delete/" method="POST"
                                                      onsubmit="return confirm('Eliminare definitivamente questo metodo di pagamento?');">
                                                    <input type="number" name="PaymentMethodsId" id="PaymentMethodsId" value="<?= $method->id ?>" hidden="hidden">
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