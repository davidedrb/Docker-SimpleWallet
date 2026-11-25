<?php
$errors = $errors ?? [];
$restore = null;
?>

<main class="auth-page">
    <section class="auth-box">
        <div class="auth-brand">
            <span class="auth-brand-mark">SW</span>
            <h1>SimpleWallet</h1>
        </div>

        <?php if ($payment->is_completed === 'Yes'): ?>
            <p class="auth-notice">
                Eliminando la transazione puoi decidere di farti riaccreditare i soldi spesi.
                Vuoi che l'importo venga ripristinato sul conto dopo l'eliminazione?
            </p>

            <?php if (!empty($errors)): ?>
                <p class="form-error form-error-banner"><?= htmlspecialchars($errors) ?></p>
            <?php endif; ?>

            <form class="auth-form" action="<?= URL ?>trash/finalDelete" method="POST" onsubmit="return confirm('Sei sicuro/a? Non potrai più tornare indietro!');">
                <input type="number" id="payment_id" name="payment_id" value="<?= htmlspecialchars($payment->id) ?>" hidden>
                <div class="form-group">
                    <label>Importo speso: <?= htmlspecialchars($payment->amount) ?></label>
                    <div class="segmented">
                        <input type="radio" id="type-no" name="restore" value="FALSE" <?= $restore === 'No' ? 'checked' : '' ?> checked>
                        <label for="type-no" class="segmented-out">No</label>

                        <input type="radio" id="type-yes" name="restore" value="TRUE" <?= $restore === 'Sì' ? 'checked' : '' ?>>
                        <label for="type-yes" class="segmented-in">Sì</label>
                    </div>
                </div>
                <div class="form-actions">
                    <button type="submit" class="button button-danger">Conferma eliminazione</button>
                    <a href="<?= URL ?>trash/index" class="button button-outline">Annulla</a>
                </div>
            </form>
        <?php else: ?>

            <div class="form-actions">
                <button type="submit" class="button button-danger">Conferma eliminazione</button>
                <a href="<?= URL ?>trash/index" class="button button-outline">Annulla</a>
            </div>
        <?php endif; ?>
    </section>
</main>