<?php
// Variabili attese:
// - $errors: errori di validazione
// - $user: utente corrente

$errors = $errors ?? [];
?>

<main class="auth-page">
    <section class="auth-box">
        <div class="auth-brand">
            <span class="auth-brand-mark">SW</span>
            <h1>SimpleWallet</h1>
        </div>

        <p class="auth-notice">
            Stai per eliminare il tuo account e tutti i suoi dati.
            L'operazione non è reversibile.
            Per confermare inserisci la tua password.
        </p>

        <?php if (!empty($errors['generic'])): ?>
            <p class="form-error form-error-banner"><?= htmlspecialchars($errors['generic']) ?></p>
        <?php endif; ?>

        <form class="auth-form" action="<?= URL ?>user/finalDelete" method="POST" onsubmit="return confirm('Sei sicuro/a? Non potrai più tornare indietro!');">
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" maxlength="72" placeholder="La tua password" required>

                <?php if (!empty($errors['password'])): ?>
                    <p class="form-error"><?= htmlspecialchars($errors['password']) ?></p>
                <?php endif; ?>
            </div>

            <div class="form-actions">
                <button type="submit" class="button button-danger">Conferma eliminazione</button>
                <a href="<?= URL ?>user/edit" class="button button-outline">Annulla</a>
            </div>
        </form>
    </section>
</main>