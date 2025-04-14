<?php
/**
 * Language handling functions for the installation wizard
 */

function loadTranslations($lang) {
    $availableLanguages = ['fr', 'en', 'es', 'pt', 'ar', 'zh', 'ru'];
    
    if (!in_array($lang, $availableLanguages)) {
        $lang = 'fr'; // Default to French
    }
    
    $langFile = __DIR__ . '/../languages/' . $lang . '.php';
    
    if (file_exists($langFile)) {
        return include $langFile;
    } else {
        // Fallback to English if language file doesn't exist
        return include __DIR__ . '/../languages/fr.php';
    }
}
