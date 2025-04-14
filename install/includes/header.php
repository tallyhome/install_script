<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $translations['installation_title']; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="icon" href="assets/img/favicon.ico" type="image/x-icon">
    <!-- Charger jQuery en premier -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <div class="container">
        <div class="language-switcher text-center mt-3">
            <a href="?lang=fr" class="<?php echo $lang == 'fr' ? 'active' : ''; ?>">Français</a>
            <a href="?lang=en" class="<?php echo $lang == 'en' ? 'active' : ''; ?>">English</a>
            <a href="?lang=es" class="<?php echo $lang == 'es' ? 'active' : ''; ?>">Español</a>
            <a href="?lang=pt" class="<?php echo $lang == 'pt' ? 'active' : ''; ?>">Português</a>
            <a href="?lang=ar" class="<?php echo $lang == 'ar' ? 'active' : ''; ?>">العربية</a>
            <a href="?lang=zh" class="<?php echo $lang == 'zh' ? 'active' : ''; ?>">中文</a>
            <a href="?lang=ru" class="<?php echo $lang == 'ru' ? 'active' : ''; ?>">Русский</a>
        </div>

        <div class="card mt-4 mb-4">
            <div class="card-body">
                <div class="text-center mb-4">
                    <?php if (file_exists(__DIR__ . '/../logo.png')): ?>
                    <div class="d-flex justify-content-center align-items-center mb-3">
                        <img src="logo.png" alt="Logo" class="img-fluid" style="max-height: 100px; max-width: 300px;">
                    </div>
                    <?php endif; ?>
                    <h1 class="card-title"><?php echo $translations['installation_title']; ?></h1>
                </div>

                <div class="step-indicators d-flex justify-content-center mb-4">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                    <div class="step-circle <?php echo $i == $current_step ? 'active' : ($i < $current_step ? 'completed' : ''); ?>">
                        <?php echo $i; ?>
                    </div>
                    <?php endfor; ?>
                </div>
