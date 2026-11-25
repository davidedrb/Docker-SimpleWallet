<?php
$errors = $errors ?? [];
$success = $success ?? null;

$currentYear = (int) date('Y');
$selectedYear = $_GET['year'] ?? (string) $currentYear;
$selectedMonth = $_GET['month'] ?? '';
?>

<div class="app-layout">
    <?php require_once __DIR__ . '/../shared/navbar.php'; ?>

    <main class="main-content">
        <header class="dashboard-header">
            <div class="welcome-box">
                <h1>Estratto conto</h1>
                <p>Genera un documento PDF con tutte le tue transazioni.</p>
            </div>
        </header>

        <?php if ($success): ?>
            <p class="auth-notice">
                <?= htmlspecialchars($success) ?>
            </p>
        <?php endif; ?>

        <?php if (!empty($errors['generic'])): ?>
            <p class="form-error form-error-banner">
                <?= htmlspecialchars($errors['generic']) ?>
            </p>
        <?php endif; ?>

        <div class="section-header">
            <div>
                <h2>Genera estratto conto</h2>
                <p>Seleziona il periodo che desideri esportare.</p>
            </div>
        </div>

        <form action="<?= URL ?>statement/generatePDF"
              method="GET"
              class="filter-bar">

            <div class="filter-field">
                <label for="year">Anno</label>

                <select name="year" id="year" required>
                    <option value="all" <?= ($_GET['year'] ?? '') === 'all' ? 'selected' : '' ?>>
                        Dall'inizio
                    </option>
                    <?php for ($year = $currentYear; $year >= $firstYear; $year--): ?>
                        <option value="<?= $year ?>"
                            <?= $selectedYear === $year ? 'selected' : '' ?>>
                            <?= $year ?>
                        </option>
                    <?php endfor; ?>
                </select>
            </div>

            <div class="filter-field">
                <label for="month">Mese (facoltativo)</label>

                <select name="month" id="month">
                    <option value="">Tutto l'anno</option>

                    <?php
                    $months = [
                        1 => 'Gennaio', 2 => 'Febbraio', 3 => 'Marzo',
                        4 => 'Aprile', 5 => 'Maggio', 6 => 'Giugno',
                        7 => 'Luglio', 8 => 'Agosto', 9 => 'Settembre',
                        10 => 'Ottobre', 11 => 'Novembre', 12 => 'Dicembre'
                    ];
                    ?>

                    <?php foreach ($months as $number => $name): ?>
                        <option value="<?= $number ?>"
                            <?= (string) $selectedMonth === (string) $number ? 'selected' : '' ?>>
                            <?= $name ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="filter-actions">
                <button type="submit" class="button button-primary button-inline">Genera PDF</button>
            </div>
        </form>
    </main>
</div>