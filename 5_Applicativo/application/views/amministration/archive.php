<div class="app-layout">

    <?php require_once __DIR__ . '/../shared/navbar.php'; ?>

    <main class="main-content">
        <header class="dashboard-header">
            <div class="welcome-box">
                <h1>Archivio</h1>
            </div>
        </header>

        <?php if ($success): ?>
            <p class="auth-notice"><?= htmlspecialchars($success) ?></p>
        <?php endif; ?>

        <section class="transactions-section">
            <div class="section-header">
                <h2>Utenti</h2>
                <a href="<?= URL ?>administrator/create" class="button button-primary button-inline">Crea utente</a>
            </div>
            <form method="GET" action="<?= URL ?>administrator/archive"
                  class="filter-bar">
                <div class="filter-field">
                    <label for="role">Ruolo</label>
                    <select name="role" id="role">
                        <option value="">Tutti</option>
                        <option value="admin" <?= $role === 'admin' ? 'selected' : '' ?>>Amministratore</option>
                        <option value="user" <?= $role === 'user' ? 'selected' : '' ?>>Utente</option>
                    </select>
                </div>

                <div class="filter-field">
                    <label for="is_active">Stato</label>
                    <select name="is_active" id="is_active">
                        <option value="">Tutti</option>
                        <option value="1" <?= $isActive === '1' ? 'selected' : '' ?>>Attivo</option>
                        <option value="0" <?= $isActive === '0' ? 'selected' : '' ?>>Disattivato</option>
                    </select>
                </div>

                <div class="filter-actions">
                    <button type="submit" class="button button-sm button-primary button-inline">Filtra</button>
                    <a href="<?= URL ?>administrator/archive" class="button button-sm button-outline">Azzera</a>
                </div>
            </form>


            <div class="transactions-table-container">
                <table class="transactions-table">
                    <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Cognome</th>
                        <th>Ruolo</th>
                        <th>Stato</th>
                        <th>Saldo</th>
                        <th>Azioni</th>
                    </tr>
                    </thead>

                    <tbody>
                        <?php if (empty($users)): ?>
                            <tr>
                                <td colspan="6" class="empty-state">Nessun disponibile.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?= htmlspecialchars($user->first_name) ?></td>
                                    <td><?= htmlspecialchars($user->last_name) ?></td>
                                    <td><?= htmlspecialchars($user->role) ?></td>
                                    <?php if($user->is_active): ?>
                                        <td class="user-active">Attivo</td>
                                    <?php elseif(!$user->is_active): ?>
                                        <td class="user-inactive">Disattivato</td>
                                    <?php endif; ?>
                                    <?php if ($user->role === 'user'): ?>
                                        <td class="<?= (float)$user->balance >= 0
                                            ? 'positive-balance'
                                            : 'negative-balance' ?>">
                                            CHF <?= htmlspecialchars($user->balance) ?>
                                        </td>
                                    <?php else: ?>
                                        <td class="info-value-neutral">-</td>
                                    <?php endif; ?>
                                    <td>
                                        <div class="user-actions-cell">
                                            <form action="<?= URL ?>administrator/show/" method="POST">
                                                <input type="number" name="user_id" id="user_id" value="<?= $user->id ?>" hidden>
                                                <button type="submit" class="button button-sm button-outline">Dettagli</button>
                                            </form>
                                            <form action="<?= URL ?>administrator/edit/" method="POST">
                                                <input type="number" name="user_id" id="user_id" value="<?= $user->id ?>" hidden>
                                                <button type="submit" class="button button-sm button-outline">Modifica</button>
                                            </form>
                                            <?php if($user->id !== $_SESSION['user']->id): ?>
                                                <?php if(!$user->is_active): ?>
                                                    <form action="<?= URL ?>administrator/activate/" method="POST"
                                                          onsubmit="return confirm('Attivare l\'utente?');">
                                                        <input type="number" name="user_id" id="user_id" value="<?= $user->id ?>" hidden>
                                                        <button type="submit" class="button button-sm button-danger">Activate</button>
                                                    </form>
                                                <?php elseif ($user->is_active): ?>
                                                    <form action="<?= URL ?>administrator/deactivate/" method="POST"
                                                          onsubmit="return confirm('Disattivare l\'utente?');">
                                                        <input type="number" name="user_id" id="user_id" value="<?= $user->id ?>" hidden>
                                                        <button type="submit" class="button button-sm button-danger">Deactivate</button>
                                                    </form>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <?php if ($totalPages > 1): ?>
                <nav class="pagination"
                     data-total="<?= (int) $totalPages ?>"
                     data-current="<?= (int) $currentPage ?>">
                    <?php for ($page = 1; $page <= $totalPages; $page++): ?>
                        <?php $queryParams['page'] = $page; ?>
                        <a href="<?= URL ?>administrator/archive?<?= http_build_query($queryParams) ?>"
                           class="pagination-link<?= $page === (int) $currentPage ? ' active' : '' ?>"><?= $page ?></a>
                    <?php endfor; ?>
                </nav>
            <?php endif; ?>
        </section>
    </main>
</div>