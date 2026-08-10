<?php
$files = [
    'app_menu/menu.js',
    'app_menu/get_menu.php'
];
foreach ($files as $f) {
    if (file_exists(__DIR__ . '/../' . $f)) {
        echo "$f modified at: " . date('Y-m-d H:i:s', filemtime(__DIR__ . '/../' . $f)) . "\n";
    }
}
?>
