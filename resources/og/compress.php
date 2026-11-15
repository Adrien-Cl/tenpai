<?php

/*
| Allège les images de partage : 256 couleurs suffisent à l'œil, et le fichier pèse moitié moins.
| Appelé par generate.mjs ; à la main : php resources/og/compress.php public/og.png public/og-en.png
*/

if (! function_exists('imagecreatefrompng')) {
    fwrite(STDERR, "GD n'est pas installé : images laissées telles quelles.\n");
    exit(0);
}

foreach (array_slice($argv, 1) as $path) {
    $image = imagecreatefrompng($path);

    if ($image === false) {
        fwrite(STDERR, "Image illisible : {$path}\n");
        exit(1);
    }

    imagetruecolortopalette($image, true, 256);

    ob_start();
    imagepng($image, null, 9);
    $png = (string) ob_get_clean();

    $before = filesize($path);

    if (strlen($png) < $before) {
        file_put_contents($path, $png);
    }

    printf("%s : %d Ko → %d Ko\n", $path, $before / 1024, min($before, strlen($png)) / 1024);
}
