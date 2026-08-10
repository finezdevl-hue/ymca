<?php
$dir = new RecursiveDirectoryIterator('c:/xampp/htdocs/ymca_new');
$iterator = new RecursiveIteratorIterator($dir);
foreach ($iterator as $file) {
    if ($file->isFile() && pathinfo($file->getFilename(), PATHINFO_EXTENSION) === 'php') {
        $content = file_get_contents($file->getPathname());
        if (strpos($content, 'bill_photo') !== false || strpos($content, 'upload') !== false) {
            if (strpos($content, 'bill') !== false) {
                echo $file->getPathname() . "\n";
            }
        }
    }
}
?>
