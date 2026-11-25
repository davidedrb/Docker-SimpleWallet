<?php
// Variabili attese dal controller (Payment::index):
// - $payments     : Collection|array di models\Payment della pagina corrente,
//                   con relazioni ->category e ->payment_method già caricate
// - $categories   : Collection|array di models\Categories, per il filtro
// - $methods      : Collection|array di models\Methods, per il filtro
// - $filters      : array con i valori dei filtri attualmente applicati
//                   ['category_id' => .., 'payment_method_id' => .., 'type' => ..,
//                    'transaction_date' => .., 'is_completed' => ..]
// - $currentPage  : int  pagina corrente (default 1)
// - $totalPages   : int  numero totale di pagine (15 transazioni per pagina)
// - $success      : string|null messaggio di conferma (Flash::get('success'))
// - $errors       : array eventuali errori generici (Flash::get('errors', []))
$payments    = $payments ?? [];
$categories  = $categories ?? [];
$methods     = $methods ?? [];
$filters     = $filters ?? [];
$filterErrors = $filterErrors ?? [];
$currentPage = $currentPage ?? 1;
$totalPages  = $totalPages ?? 1;
$success     = $success ?? null;
$errors      = $_SESSION['errors'] ?? '';

// Parametri di query da riproporre nei link di paginazione, senza 'url' (gestito dal rewrite)
$queryParams = $_GET;
unset($queryParams['url']);
?>

<div class="app-layout">
    <?php require_once __DIR__ . '/../shared/navbar.php'; ?>
    <main class="main-content">
        <header class="dashboard-header">
            <div class="welcome-box">
                <h1>Cestino Pagamenti</h1>
            </div>

            <div class="dashboard-info">
                <section class="info-box">
                    <h2>Limite di Spesa Mensile</h2>
                    <p class="info-value-neutral"<?= $user['spending_limit']?> >
                        CHF <?= number_format((float) $user['spending_limit'], 2, ',', '.') ?>
                    </p>
                </section>

                <section class="info-box">
                    <h2>Spese Mensili</h2>
                    <p class="info-value<?= $user['monthly_expenses'] > $user['spending_limit'] ? ' info-value-alert' : '' ?>">
                        CHF <?= number_format((float) $user['monthly_expenses'], 2, ',', '.') ?>
                    </p>
                </section>

                <section class="info-box">
                    <h2>Saldo Attuale</h2>
                    <p class="info-value<?= $user['balance'] < 0 ? ' info-value-negative' : '' ?>">
                        CHF <?= number_format((float) $user['balance'], 2, ',', '.') ?>
                    </p>
                </section>
            </div>
        </header>

        <?php if ($success): ?>
            <p class="auth-notice"><?= htmlspecialchars($success) ?></p>
        <?php endif; ?>

        <?php if (!empty($errors['generic'])): ?>
            <p class="form-error form-error-banner"><?= htmlspecialchars($errors['generic']) ?></p>
        <?php endif; ?>

        <section class="transactions-section">
            <form class="filter-bar" action="<?= URL ?>trash/index" method="GET">
                <div class="filter-field">
                    <label for="filter-category">Categoria</label>
                    <select id="filter-category" name="category_id">
                        <option value="">Tutte</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?= $category->id ?>" <?= (string) ($filters['category_id'] ?? '') === (string) $category->id ? 'selected' : '' ?>>
                                <?= htmlspecialchars($category->name ?? '') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="filter-field">
                    <label for="filter-method">Metodo di pagamento</label>
                    <select id="filter-method" name="payment_method_id">
                        <option value="">Tutti</option>
                        <?php foreach ($methods as $method): ?>
                            <option value="<?= $method->id ?>" <?= (string) ($filters['payment_method_id'] ?? '') === (string) $method->id ? 'selected' : '' ?>>
                                <?= htmlspecialchars($method->name ?? $method->type ?? '') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="filter-field">
                    <label for="filter-type">Tipo</label>
                    <select id="filter-type" name="type">
                        <option value="">Tutti</option>
                        <option value="Entrata" <?= ($filters['type'] ?? '') === 'Entrata' ? 'selected' : '' ?>>Entrata</option>
                        <option value="Uscita" <?= ($filters['type'] ?? '') === 'Uscita' ? 'selected' : '' ?>>Uscita</option>
                    </select>
                </div>

                <div class="filter-field filter-range filter-range-date">
                    <label for="filter-date-min">Data transazione</label>
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

                <div class="filter-field filter-range filter-range-amount">
                    <label for="filter-amount-min">Importo</label>
                    <div class="filter-range-inputs">
                        <input type="text" id="filter-amount-min" name="amount_min" maxlength="11" inputmode="decimal"
                               placeholder="min" pattern="-?\d{1,8}([.,]\d{1,2})?" autocomplete="off"
                               aria-label="Importo minimo (incluso)" title="Importi da questo valore in su (valore incluso). Le uscite sono negative, es. -50"
                               value="<?= htmlspecialchars($filters['amount_min'] ?? '') ?>">
                        <span class="filter-range-sep" aria-hidden="true">-</span>
                        <input type="text" id="filter-amount-max" name="amount_max" maxlength="11" inputmode="decimal"
                               placeholder="max" pattern="-?\d{1,8}([.,]\d{1,2})?" autocomplete="off"
                               aria-label="Importo massimo (incluso)" title="Importi fino a questo valore (valore incluso). Le uscite sono negative, es. -50"
                               value="<?= htmlspecialchars($filters['amount_max'] ?? '') ?>">
                    </div>
                </div>

                <div class="filter-field">
                    <label for="filter-completed">Effettuato</label>
                    <select id="filter-completed" name="is_completed">
                        <option value="">Tutti</option>
                        <option value="Yes" <?= ($filters['is_completed'] ?? '') === 'Yes' ? 'selected' : '' ?>>Sì</option>
                        <option value="No" <?= ($filters['is_completed'] ?? '') === 'No' ? 'selected' : '' ?>>No</option>
                    </select>
                </div>

                <div class="filter-actions">
                    <button type="submit" class="button button-primary button-inline button-sm">Filtra</button>
                    <a href="<?= URL ?>trash/index" class="button button-outline button-sm">Azzera</a>
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
                            <th>Descrizione</th>
                            <th>Categoria</th>
                            <th>Metodo</th>
                            <th>Tipo</th>
                            <th class="col-amount">Importo</th>
                            <th>Effettuato</th>
                            <th class="col-actions">Azioni</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($payments)): ?>
                            <tr>
                                <td colspan="8" class="empty-state">Nessuna transazione trovata con i filtri selezionati.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($payments as $payment): ?>
                                <tr>
                                    <td><?= htmlspecialchars(!empty($payment->transaction_date) ? date('Y-m-d', strtotime((string) $payment->transaction_date)) : '') ?></td>
                                    <td><?= htmlspecialchars($payment->description ?? '') ?></td>
                                    <td><?= htmlspecialchars($payment->category->name ?? '—') ?></td>
                                    <td><?= htmlspecialchars($payment->payment_method->name ?? $payment->payment_method->type ?? '—') ?></td>
                                    <td><?= htmlspecialchars($payment->type ?? '-') ?></td>
                                    <?php if ($payment->type === 'Uscita'): ?>
                                        <td class="col-amount amount-negative">-<?= htmlspecialchars($payment->amount ?? '') ?></td>
                                    <?php else: ?>
                                        <td class="col-amount amount-positive">+<?= htmlspecialchars($payment->amount ?? '') ?></td>
                                    <?php endif; ?>
                                    <td>
                                        <?php $done = in_array($payment->is_completed ?? null, ['Yes', 'yes', 1, '1', true], true); ?>
                                        <span class="badge <?= $done ? 'badge-yes' : 'badge-no' ?>">
                                            <?= $done ? 'Sì' : 'No' ?>
                                        </span>
                                    </td>
                                    <td class="col-actions">
                                        <div class="actions-cell">
                                            <form action="<?= URL ?>trash/show/" method="POST">
                                                <input type="number" name="payment_id" id="payment_id" value="<?= $payment->id ?>" hidden>
                                                <button type="submit" class="button button-sm button-outline">Dettagli</button>
                                            </form>
                                            <form action="<?= URL ?>trash/restore/" method="POST">
                                                <input type="number" name="payment_id" id="payment_id" value="<?= $payment->id ?>" hidden>
                                                <button type="submit" class="button button-sm button-outline">Ripristina</button>
                                            </form>
                                            <form action="<?= URL ?>trash/delete/" method="POST"
                                                  onsubmit="return confirm('Eliminare definitivamente questo pagamento?');">
                                                <input type="number" name="payment_id" id="payment_id" value="<?= $payment->id ?>" hidden>
                                                <button type="submit" class="button button-sm button-danger">Elimina</button>
                                            </form>
                                        </div>
                                    </td>
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
                        <a href="<?= URL ?>trash/index?<?= http_build_query($queryParams) ?>"
                           class="pagination-link<?= $page === (int) $currentPage ? ' active' : '' ?>">
                            <?= $page ?>
                        </a>
                    <?php endfor; ?>
                </nav>
            <?php endif; ?>
        </section>
    </main>
</div>