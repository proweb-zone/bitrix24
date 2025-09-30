<?php
// clear_theme_cache.php
define('NOT_CHECK_PERMISSIONS', true);
require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');

// Очистка кеша тем
$themeCachePath = $_SERVER['DOCUMENT_ROOT'].'/bitrix/themes/.default/';

if (is_dir($themeCachePath)) {
    // Удаляем содержимое директории тем
    $files = glob($themeCachePath . '*');
    foreach ($files as $file) {
        if (is_file($file)) {
            unlink($file);
        }
    }
    echo "Кеш тем очищен<br>";
} else {
    // Создаем директорию если не существует
    mkdir($themeCachePath, 0755, true);
    echo "Директория тем создана<br>";
}

// Принудительная перегенерация CSS
if (CModule::IncludeModule("main")) {
    // Очистка кеша компонентов
    CBitrixComponent::clearComponentCache();

    // Очистка managed cache
    $GLOBALS['CACHE_MANAGER']->CleanAll();

    // Очистка статического кеша
    Bitrix\Main\Data\Cache::createInstance()->clean();

    echo "Кеш перегенерации запущен<br>";
}

echo "Выполнено! Обновите страницу.";
?>
