<?php
header('Content-Type: application/json');
$imageDir = '../../images/events/';
$images = [];

if (is_dir($imageDir)) {
    foreach (scandir($imageDir) as $file) {
        if (preg_match('/\.(jpg|jpeg|png|webp)$/i', $file)) {
            $images[] = $file;
        }
    }
}

echo json_encode($images);
?>
