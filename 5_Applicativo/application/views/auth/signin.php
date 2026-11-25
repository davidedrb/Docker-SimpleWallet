<?php
// $errors e $old arrivano già valorizzati dal controller (Authenticator::register,
// tramite Flash::get) — niente da rileggere qui, la sessione è già stata
// svuotata da Flash::get() a questo punto.
$errors = $errors ?? [];
$old    = $old ?? [];
?>

<main class="auth-page">

    <section class="auth-box">
        <div class="auth-brand">
            <span class="auth-brand-mark">SW</span>
            <h1>SimpleWallet</h1>
        </div>

        <div class="auth-tabs">
            <a href="<?= URL ?>authenticator/login" class="auth-tab">Accedi</a>
            <a href="<?= URL ?>authenticator/register" class="auth-tab active">Registrati</a>
        </div>

        <form class="auth-form" action="<?= URL ?>user/store" method="POST" novalidate>
            <div class="form-row">
                <div class="form-group">
                    <label for="first_name">Nome</label>
                    <input type="text" id="first_name" name="first_name" maxlength="100"
                           value="<?= htmlspecialchars($old['first_name'] ?? '') ?>"
                           placeholder="Il tuo nome" required>
                    <?php if (!empty($errors['first_name'])): ?>
                        <p class="form-error"><?= htmlspecialchars($errors['first_name']) ?></p>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="last_name">Cognome</label>
                    <input type="text" id="last_name" name="last_name" maxlength="100"
                           value="<?= htmlspecialchars($old['last_name'] ?? '') ?>"
                           placeholder="Il tuo cognome" required>
                    <?php if (!empty($errors['last_name'])): ?>
                        <p class="form-error"><?= htmlspecialchars($errors['last_name']) ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" maxlength="100"
                       value="<?= htmlspecialchars($old['email'] ?? '') ?>"
                       placeholder="nome.cognome@example.com" required>
                <?php if (!empty($errors['email'])): ?>
                    <p class="form-error"><?= htmlspecialchars($errors['email']) ?></p>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" maxlength="72" placeholder="Almeno 8 caratteri" required>
                <?php if (!empty($errors['password'])): ?>
                    <p class="form-error"><?= htmlspecialchars($errors['password']) ?></p>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="confirm_password">Conferma Password</label>
                <input type="password" id="confirm_password" name="confirm_password" maxlength="72" placeholder="Ripeti la password" required>
                <?php if (!empty($errors['conf_pwd'])): ?>
                    <p class="form-error"><?= htmlspecialchars($errors['conf_pwd']) ?></p>
                <?php endif; ?>
            </div>

            <button type="submit" class="button button-primary">Crea account</button>
        </form>
    </section>
</main>