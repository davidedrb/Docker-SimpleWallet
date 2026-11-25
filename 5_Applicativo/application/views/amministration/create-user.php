
<?php
// Variabili attese dal controller (Administrator::create):
// - $errors : array eventuali errori di validazione
// - $old    : array valori reinseriti dopo un errore

$errors = $errors ?? [];
$old    = $old ?? [];
?>

<div class="app-layout">
    <?php require_once __DIR__ . '/../shared/navbar.php'; ?>

    <main class="main-content">
        <header class="dashboard-header">
            <div class="welcome-box">
                <h1>Crea Utente</h1>
            </div>
        </header>

        <?php if (!empty($errors['generic'])): ?>
            <p class="form-error form-error-banner">
                <?= htmlspecialchars($errors['generic']) ?>
            </p>
        <?php endif; ?>

        <div class="account-grid">
            <section class="account-card">
                <h2>Informazioni personali</h2>

                <form action="<?= URL ?>administrator/store" method="POST" novalidate>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="first_name">Nome</label>
                            <input type="text" id="first_name" name="first_name" maxlength="100" value="<?= htmlspecialchars($old['first_name'] ?? '') ?>" placeholder="Nome" required>

                            <?php if (!empty($errors['first_name'])): ?>
                                <p class="form-error">
                                    <?= htmlspecialchars($errors['first_name']) ?>
                                </p>
                            <?php endif; ?>
                        </div>

                        <div class="form-group">
                            <label for="last_name">Cognome</label>
                            <input type="text" id="last_name" name="last_name" maxlength="100" value="<?= htmlspecialchars($old['last_name'] ?? '') ?>" placeholder="Cognome" required>

                            <?php if (!empty($errors['last_name'])): ?>
                                <p class="form-error">
                                    <?= htmlspecialchars($errors['last_name']) ?>
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email" maxlength="100" value="<?= htmlspecialchars($old['email'] ?? '') ?>" placeholder="nome.cognome@example.com" required>

                            <?php if (!empty($errors['email'])): ?>
                                <p class="form-error">
                                    <?= htmlspecialchars($errors['email']) ?>
                                </p>
                            <?php endif; ?>
                        </div>

                        <div class="form-group">
                            <label for="role">Ruolo</label>
                            <select id="role" name="role" required>
                                <option value="user"
                                    <?= ($old['role'] ?? 'user') === 'user' ? 'selected' : '' ?>>
                                    User
                                </option>

                                <option value="admin"
                                    <?= ($old['role'] ?? 'user') === 'admin' ? 'selected' : '' ?>>
                                    Admin
                                </option>
                            </select>

                            <?php if (!empty($errors['role'])): ?>
                                <p class="form-error">
                                    <?= htmlspecialchars($errors['role']) ?>
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" maxlength="72" placeholder="Almeno 8 caratteri" required>
                        <?php if (!empty($errors['password'])): ?>
                            <p class="form-error"><?= htmlspecialchars($errors['password']) ?></p>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">Conferma password</label>
                        <input type="password" id="confirm_password" name="confirm_password" maxlength="72" placeholder="Ripeti la password" required>
                        <?php if (!empty($errors['conf_pwd'])): ?>
                            <p class="form-error"><?= htmlspecialchars($errors['conf_pwd']) ?></p>
                        <?php endif; ?>
                    </div>

                    <div class="user-actions">
                        <button type="submit" class="button button-primary">Crea utente</button>
                        <a href="<?= URL ?>administrator/archive" class="button button-outline">Annulla</a>
                    </div>
                </form>
            </section>
        </div>
    </main>
</div>
