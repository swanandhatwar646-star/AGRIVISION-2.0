<?php
function t($key, $default = '') {
    global $translations;
    
    if (isset($translations[$key])) {
        return $translations[$key];
    }
    
    return $default ?: $key;
}

function load_translations($lang = 'en') {
    global $translations;
    
    $translations = [];
    
    $lang_file = __DIR__ . "/../languages/{$lang}.php";
    
    if (file_exists($lang_file)) {
        $translations = include $lang_file;
    } else {
        $translations = include __DIR__ . "/../languages/en.php";
    }
    
    return $translations;
}
?>
