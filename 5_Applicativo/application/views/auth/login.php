<?php
// $errors e $old arrivano già valorizzati dal controller (Authenticator::login,
// tramite Flash::get) — niente da rileggere qui, la sessione è già stata
// svuotata da Flash::get() a questo punto.
$errors   = $errors ?? [];
$old      = $old ?? [];
$isLogged = $isLogged ?? false;
?>

<main class="auth-page">
    <section class="auth-box">
        <div class="auth-brand">
            <span class="auth-brand-mark">SW</span>
            <h1>SimpleWallet</h1>
        </div>

        <div class="auth-tabs">
            <a href="<?= URL ?>authenticator/login" class="auth-tab active">Accedi</a>
            <a href="<?= URL ?>authenticator/register" class="auth-tab">Registrati</a>
        </div>

        <?php if ($isLogged): ?>
            <p class="auth-notice">
                Hai già una sessione attiva.
                <a href="<?= URL ?>home/index">Vai alla dashboard</a>
            </p>
        <?php endif; ?>

        <?php if (!empty($errors['login'])): ?>
            <p class="form-error form-error-banner"><?= htmlspecialchars($errors['login']) ?></p>
        <?php endif; ?>

        <form class="auth-form" action="<?= URL ?>authenticator/authenticate" method="POST" novalidate>
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
                <input type="password" id="password" name="password" maxlength="72" placeholder="La tua password" required>
            </div>

            <button type="submit" class="button button-primary">Accedi</button>
        </form>
    </section>
</main>