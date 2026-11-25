<?php
$pageTitle = $pageTitle ?? 'SimpleWallet';
?>

<!DOCTYPE html>
<html lang="it"<?= ($_COOKIE['theme'] ?? '') === 'dark' ? ' data-theme="dark"' : '' ?>>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> - SimpleWallet</title>
    <link rel="icon" href="<?= URL ?>public/img/favicon.ico" sizes="any">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400..700&family=Karla:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= URL ?>public/css/style.css?v=<?= @filemtime('public/css/style.css') ?: 1 ?>">
    <?php if (!empty($withCharts)): ?>
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.js" defer></script>
    <?php endif; ?>
    <script src="<?= URL ?>public/js/script.js?v=<?= @filemtime('public/js/script.js') ?: 1 ?>" defer></script>
    <?php if (!isset($_COOKIE['theme'])): ?>
        <?php // Nessuna scelta fatta dall'utente: segue il tema del sistema operativo ?>
        <script>if (matchMedia('(prefers-color-scheme: dark)').matches) document.documentElement.setAttribute('data-theme', 'dark');</script>
    <?php endif; ?>
</head>
<body>