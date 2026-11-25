<?php
if (!isset($activeSection)) {
    $urlSegments = explode('/', rtrim($_GET['url'] ?? '', '/'));
    $activeSection = $urlSegments[0] ?: 'home';

    if ($activeSection === 'administrator') {
        $action = $urlSegments[1] ?? 'stats';

        $activeSection = match ($action) {
            'show', 'update', 'create', 'edit' => (($_POST['user_id'] ?? $_GET['user_id'] ?? null) == $_SESSION['user']->id) ? 'user' : 'archive',
            default => $action
        };
    }
}
// icon = nome del file in public/img (senza .png)

if ($_SESSION['user']['role'] === 'admin') {
    $navLinks = [
        'stats'         => ['label' => 'Statistiche',         'route' => 'administrator/stats',   'icon' => 'stats'],
        'archive'       => ['label' => 'Archivio',       'route' => 'administrator/archive', 'icon' => 'archive'],
        'log'           => ['label' => 'Registro attività',    'route' => 'administrator/logs', 'icon' => 'log'],
        'user'          => ['label' => 'Account',       'route' => 'administrator/edit/','icon' => 'user'],
    ];
} else {
    $navLinks = [
        'home'          => ['label' => 'Home',                  'route' => 'home/index',         'icon' => 'home'],
        'payment'       => ['label' => 'Storico',               'route' => 'payment/index',      'icon' => 'history'],
        'statement'     => ['label' => 'Estratto Conto',       'route' => 'statement/index',    'icon' => 'statement'],
        'chart'         => ['label' => 'Grafici',               'route' => 'chart/index',        'icon' => 'charts'],
        'category'      => ['label' => 'Categorie',             'route' => 'category/index',     'icon' => 'categories'],
        'method'        => ['label' => 'Metodi di Pagamento',   'route' => 'method/index',       'icon' => 'payment-methods'],
        'subscription'  => ['label' => 'Abbonamenti',           'route' => 'subscription/index', 'icon' => 'subscriptions'],
        'trash'         => ['label' => 'Cestino',               'route' => 'trash/index',        'icon' => 'recycle-bin'],
        'user'          => ['label' => 'Account',               'route' => 'user/edit/',         'icon' => 'user'],
    ];
}
?>
<nav class="navbar">
    <div class="navbar-brand">
        <span class="navbar-brand-mark">SW</span>
        <span class="navbar-brand-name">SimpleWallet</span>
    </div>

    <ul class="navbar-menu">
        <?php foreach ($navLinks as $key => $link): ?>
            <li>
                <a href="<?= URL . $link['route'] ?>"
                   class="navbar-link<?= ($activeSection === $key || $link['route'] === 'administrator/' . $activeSection) ? ' active' : '' ?>">
                    <span class="navbar-icon" style="--icon: url('<?= URL ?>public/img/<?= $link['icon'] ?>.png')" aria-hidden="true"></span>
                    <span class="navbar-label"><?= htmlspecialchars($link['label']) ?></span>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>

    <div class="navbar-footer">
        <a href="<?= URL ?>authenticator/logout" class="navbar-link logout-link">Logout</a>
    </div>
</nav>