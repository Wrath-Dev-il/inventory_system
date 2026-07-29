<?php
$img = imagecreatetruecolor(200, 150);
$bg = imagecolorallocate($img, 50, 100, 200);
imagefill($img, 0, 0, $bg);
$textColor = imagecolorallocate($img, 255, 255, 255);
imagestring($img, 5, 40, 65, 'Test Product', $textColor);
imagejpeg($img, __DIR__ . '/test-product.jpg', 85);
imagedestroy($img);
echo 'Created: ' . __DIR__ . '/test-product.jpg (' . filesize(__DIR__ . '/test-product.jpg') . ' bytes)';