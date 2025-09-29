<?php
session_start();

// Config
$width = 140;
$height = 40;
$length = 5;
$chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; // avoid I, O, 1, 0
$fontFile = __DIR__ . '/../assets/fonts/DejaVuSans.ttf';

// Generate code
$code = '';
for ($i = 0; $i < $length; $i++) {
    $code .= $chars[random_int(0, strlen($chars) - 1)];
}

// Store in session
$_SESSION['captcha'] = $code;
$_SESSION['captcha_time'] = time();

// Create image
$image = imagecreatetruecolor($width, $height);

// Colors
$bg = imagecolorallocate($image, 245, 245, 245);
$fg = imagecolorallocate($image, 37, 37, 37);
$noise1 = imagecolorallocate($image, 220, 220, 220);
$noise2 = imagecolorallocate($image, 200, 200, 200);

// Background
imagefilledrectangle($image, 0, 0, $width, $height, $bg);

// Noise
for ($i = 0; $i < 6; $i++) {
    imageline($image, random_int(0,$width), random_int(0,$height), random_int(0,$width), random_int(0,$height), $noise1);
}
for ($i = 0; $i < 100; $i++) {
    imagesetpixel($image, random_int(0,$width), random_int(0,$height), $noise2);
}

// Draw text
if (file_exists($fontFile) && function_exists('imagettftext')) {
    $fontSize = 18;
    $x = 10;
    for ($i = 0; $i < strlen($code); $i++) {
        $angle = random_int(-18, 18);
        $y = random_int($height - 8, $height - 6);
        imagettftext($image, $fontSize, $angle, $x, $y, $fg, $fontFile, $code[$i]);
        $x += $fontSize - 2;
    }
} else {
    // fallback
    $font = 5;
    $x = 10;
    $y = ($height - imagefontheight($font)) / 2;
    for ($i = 0; $i < strlen($code); $i++) {
        imagestring($image, $font, $x, $y, $code[$i], $fg);
        $x += imagefontwidth($font) + 6;
    }
}

// Output
header('Content-Type: image/png');
header('Cache-Control: no-cache, must-revalidate');
imagepng($image);
imagedestroy($image);
exit;
