<?php
// Variabili attese dal controller (Administrator::logs):
// - $logs         : array di models\Logs della pagina corrente
// - $filters      : array con i valori dei filtri attualmente applicati
//                   ['user_name' => .., 'date_min' => .., 'date_max' => ..]
// - $filterErrors : array messaggi per intervalli non validi (data minima > massima), vuoto se tutto ok
// - $currentPage  : int  pagina corrente (default 1)
// - $totalPages   : int  numero totale di pagine (15 log per pagina)
$logs         = $logs ?? [];
$filters      = $filters ?? [];
$filterErrors = $filterErrors ?? [];
$currentPage  = $currentPage ?? 1;
$totalPages   = $totalPages ?? 1;

// Parametri di query da riproporre nei link di paginazione, senza 'url' (gestito dal rewrite)
$queryParams = $_GET;
unset($queryParams['url']);

?>

<div class="app-layout">

    <?php require_once __DIR__ . '/../shared/navbar.php'; ?>

    <main class="main-content">
        <header class="dashboard-header">
            <div class="welcome-box">
                <h1>Registro attività</h1>
            </div>
        </header>

    <div class="dashboard-info"></div>
    <section class="transactions-section">
        <div class="section-header">
            <h2>Tutte le attività</h2>
        </div>

        <form class="filter-bar" action="<?= URL ?>administrator/logs" method="GET">
            <div class="filter-field">
                <label for="filter-user">Utente</label>
                <input type="text" id="filter-user" name="user_name" maxlength="100"
                       placeholder="Nome utente" autocomplete="off"
                       value="<?= htmlspecialchars($filters['user_name'] ?? '') ?>">
            </div>

            <div class="filter-field filter-range filter-range-date">
                <label for="filter-date-min">Data</label>
                <div class="filter-range-inputs">
                    <input type="text" id="filter-date-min" name="date_min" maxlength="10"
                           placeholder="yyyy-MM-dd" pattern="\d{4}-\d{2}-\d{2}" autocomplete="off"
                           aria-label="Data minima (inclusa)" title="Da questo giorno in poi (giorno incluso)"
                           value="<?= htmlspecialchars($filters['date_min'] ?? '') ?>">
                    <span class="filter-range-sep" aria-hidden="true">-</span>
                    <input type="text" id="filter-date-max" name="date_max" maxlength="10"
                           placeholder="yyyy-MM-dd" pattern="\d{4}-\d{2}-\d{2}" autocomplete="off"
                           aria-label="Data massima (inclusa)" title="Fino a questo giorno (giorno incluso)"
                           value="<?= htmlspecialchars($filters['date_max'] ?? '') ?>">
                </div>
            </div>

            <div class="filter-actions">
                <button type="submit" class="button button-primary button-inline button-sm">Filtra</button>
                <a href="<?= URL ?>administrator/logs" class="button button-outline button-sm">Azzera</a>
            </div>
        </form>

        <?php if (!empty($filterErrors)): ?>
            <div class="filter-errors">
                <?php foreach ($filterErrors as $message): ?>
                    <p class="form-error form-error-banner"><?= htmlspecialchars($message) ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="transactions-table-container">
            <table class="transactions-table">
                <thead>
                <tr>
                    <th>Data</th>
                    <th>Utente</th>
                    <th>Attività</th>
                </tr>
                </thead>

                <tbody>
                <?php if (empty($logs)): ?>
                    <tr>
                        <td colspan="3" class="empty-state">Nessuna attività trovata.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($logs as $log): ?>
                        <tr>
                            <td><?= htmlspecialchars(date('Y-m-d H:i', strtotime($log->created_at))) ?></td>
                            <td><?= htmlspecialchars($log->user_name) ?> (ID <?= (int) $log->user_id ?>)</td>
                            <td><?= htmlspecialchars($log->action) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($totalPages > 1): ?>
            <nav class="pagination" data-total="<?= (int) $totalPages ?>" data-current="<?= (int) $currentPage ?>">
                <?php for ($page = 1; $page <= $totalPages; $page++): ?>
                    <?php $queryParams['page'] = $page; ?>
                    <a href="<?= URL ?>administrator/logs?<?= http_build_query($queryParams) ?>"
                       class="pagination-link<?= $page === (int) $currentPage ? ' active' : '' ?>">
                        <?= $page ?>
                    </a>
                <?php endfor; ?>
            </nav>
        <?php endif; ?>
    </section>
    </main>
</div>