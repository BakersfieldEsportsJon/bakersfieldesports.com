<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?? 'Admin' ?> - <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="<?= $base_path ?? '../' ?>css/styles.css">
    <link rel="stylesheet" href="<?= $base_path ?? '../' ?>css/admin.css">
    <?php if (isset($extra_css)): ?>
        <?php foreach ((array)$extra_css as $css): ?>
    <link rel="stylesheet" href="<?= $css ?>">
        <?php endforeach; ?>
    <?php endif; ?>
    <?php if (isset($extra_head_content)) echo $extra_head_content; ?>
</head>
<body<?= isset($body_class) ? ' class="' . htmlspecialchars($body_class) . '"' : '' ?>>
