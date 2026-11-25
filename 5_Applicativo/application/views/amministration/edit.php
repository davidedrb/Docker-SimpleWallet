<?php
// Variabili attese dal controller (User::edit):
// - $user   : models\Users dell'utente loggato (di solito $_SESSION['user'])
// - $errors : array   eventuali errori di validazione (Flash::get('errors', []))
// - $old    : array   valori reinseriti dopo un errore (Flash::get('old', []))
// - $success: string|null messaggio di conferma (Flash::get('success'))
$user    = $user ?? ($_SESSION['user'] ?? null);
$errors  = $errors ?? [];
$old     = $old ?? [];
$success = $success ?? null;
?>

<div class="app-layout">
    <?php require_once __DIR__ . '/../shared/navbar.php'; ?>
    <main class="main-content">
        <header class="dashboard-header">
            <div class="welcome-box">
                <h1>Modifica Utente</h1>
            </div>
        </header>

        <?php if (!empty($errors['generic'])): ?>
            <p class="form-error form-error-banner"><?= htmlspecialchars($errors['generic']) ?></p>
        <?php endif; ?>

        <div class="account-grid">
            <section class="account-card">
                <h2>Informazioni personali</h2>
                <form action="<?= URL ?>administrator/update/" method="POST" novalidate>
                    <input type="hidden" name="user_id" value="<?= $user->id ?>">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="first_name">Nome</label>
                            <input type="text" id="first_name" name="first_name" maxlength="100"
                                   value="<?= htmlspecialchars($old['first_name'] ?? $user->first_name ?? '') ?>"
                                   placeholder="Il tuo nome" required>
                            <?php if (!empty($errors['first_name'])): ?>
                                <p class="form-error"><?= htmlspecialchars($errors['first_name']) ?></p>
                            <?php endif; ?>
                        </div>

                        <div class="form-group">
                            <label for="last_name">Cognome</label>
                            <input type="text" id="last_name" name="last_name" maxlength="100"
                                   value="<?= htmlspecialchars($old['last_name'] ?? $user->last_name ?? '') ?>"
                                   placeholder="Il tuo cognome" required>
                            <?php if (!empty($errors['last_name'])): ?>
                                <p class="form-error"><?= htmlspecialchars($errors['last_name']) ?></p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email" maxlength="100"
                                   value="<?= htmlspecialchars($old['email'] ?? $user->email ?? '') ?>"
                                   placeholder="nome.cognome@example.com" required>
                            <?php if (!empty($errors['email'])): ?>
                                <p class="form-error"><?= htmlspecialchars($errors['email']) ?></p>
                            <?php endif; ?>
                        </div>
                        <div class="form-group">
                            <label for="role">Ruolo</label>
                            <input class="disabled" type="text" id="role" name="role"
                                   value="<?= htmlspecialchars($user->role) ?>"
                                   disabled="disabled" >
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="password">Nuova password</label>
                        <input type="password" id="password" name="password" maxlength="72" placeholder="Lascia vuoto per non modificarla">
                        <?php if (!empty($errors['password'])): ?>
                            <p class="form-error"><?= htmlspecialchars($errors['password']) ?></p>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">Conferma nuova password</label>
                        <input type="password" id="confirm_password" name="confirm_password" maxlength="72" placeholder="Ripeti la nuova password">
                        <?php if (!empty($errors['conf_pwd'])): ?>
                            <p class="form-error"><?= htmlspecialchars($errors['conf_pwd']) ?></p>
                        <?php endif; ?>
                    </div>

                    <?php if ($user->role !== 'admin'): ?>

                        <h2>Impostazioni finanziarie</h2>

                        <div class="form-group">
                            <label for="spending_limit">Limite di spesa mensile (CHF)</label>
                            <input type="text" id="spending_limit" name="spending_limit" maxlength="12"
                                   value="<?= htmlspecialchars($old['spending_limit'] ?? $user->spending_limit ?? '0') ?>"
                                   placeholder="0.00">
                            <p class="form-hint">Quando il saldo supera questo limite viene segnalato in Home.</p>
                            <?php if (!empty($errors['spending_limit'])): ?>
                                <p class="form-error"><?= htmlspecialchars($errors['spending_limit']) ?></p>
                            <?php endif; ?>
                        </div>

                        <div class="form-group">
                            <label for="balance">Saldo attuale (CHF)</label>
                            <input type="text" id="balance" name="balance" maxlength="12"
                                   value="<?= htmlspecialchars($old['balance'] ?? $user->balance ?? '0') ?>"
                                   placeholder="0.00">
                            <?php if (!empty($errors['balance'])): ?>
                                <p class="form-error"><?= htmlspecialchars($errors['balance']) ?></p>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($_SESSION['user']->id) && (int) $_SESSION['user']->id === (int) $user->id): ?>
                        <h2>Impostazioni personali</h2>
                        <div class="form-group">
                            <label for="dark_mode">Modalità scura</label>
                            <label class="switch">
                                <input type="checkbox" id="dark_mode" role="switch">
                                <span class="switch-slider"></span>
                            </label>
                        </div>
                    <?php endif; ?>

                    <div class="user-actions">
                        <button type="submit" class="button button-primary">Salva impostazioni</button>
                        <a href="<?= URL ?>administrator/archive" class="button button-outline">Annulla</a>
                    </div>
                </form>
            </section>
        </div>
    </main>
</div>