<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
  <meta name="description" content="<?= htmlspecialchars($currentPage['description'], ENT_QUOTES, 'UTF-8') ?>">
  <meta name="theme-color" content="#f5b041">
  <title><?= htmlspecialchars($currentPage['title'], ENT_QUOTES, 'UTF-8') ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700;14..32,800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" referrerpolicy="no-referrer">
  <link rel="stylesheet" href="<?= asset_path('/assets/css/global.css') ?>">
  <?php foreach (($currentPage['styles'] ?? []) as $stylesheet): ?>
    <link rel="stylesheet" href="<?= asset_path($stylesheet) ?>">
  <?php endforeach; ?>
</head>
<body data-page="<?= htmlspecialchars($currentPage['key'], ENT_QUOTES, 'UTF-8') ?>">
  <a class="skip-link" href="#main-content">Aller au contenu principal</a>
