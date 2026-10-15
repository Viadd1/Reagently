<!-- Generate a CAPTCHA image containing random letters and numbers. -->
 
<?php

require_once __DIR__ . "/../init.php";

// Characters that can appear in the CAPTCHA.
// 0, O, 1, I are removed because they can easily be confused.
$characters =
    "ABCDEFGHJKLMNPQRSTUVWXYZ23456789";

// Generate a 5-character CAPTCHA
$captcha_code = "";

for ($i = 0; $i < 5; $i++) {

    $random_index =
        random_int(
            0,
            strlen($characters) - 1
        );

    $captcha_code .=
        $characters[$random_index];
}

// Save the correct CAPTCHA in the session
$_SESSION['captcha_code'] =
    $captcha_code;

//Create CAPTCHA image
$width = 180;
$height = 60;

// Create image
$image =
    imagecreatetruecolor(
        $width,
        $height
    );

// Background colour
$background =
    imagecolorallocate(
        $image,
        230,
        230,
        220
    );

// Text colour
$text_color =
    imagecolorallocate(
        $image,
        30,
        30,
        30
    );

// Noise colours
$noise_color =
    imagecolorallocate(
        $image,
        100,
        100,
        90
    );

// Fill background
imagefilledrectangle(
    $image,
    0,
    0,
    $width,
    $height,
    $background
);

//Add noise lines
for ($i = 0; $i < 8; $i++) {

    imageline(
        $image,
        random_int(0, $width),
        random_int(0, $height),
        random_int(0, $width),
        random_int(0, $height),
        $noise_color
    );
}

//Add random dots
for ($i = 0; $i < 80; $i++) {

    imagesetpixel(
        $image,
        random_int(0, $width - 1),
        random_int(0, $height - 1),
        $noise_color
    );
}

//Draw CAPTCHA characters
$x = 20;

for ($i = 0; $i < strlen($captcha_code); $i++) {

    $y =
        random_int(
            18,
            28
        );

    imagestring(
        $image,
        5,
        $x,
        $y,
        $captcha_code[$i],
        $text_color
    );

    $x +=
        random_int(
            25,
            30
        );
}

// Output image. Prevent browser caching
header(
    "Cache-Control: no-store, no-cache, must-revalidate"
);

header(
    "Content-Type: image/png"
);

// Send image to browser
imagepng($image);

// Remove image from memory
imagedestroy($image);

?>