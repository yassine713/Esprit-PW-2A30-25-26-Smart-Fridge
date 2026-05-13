<?php
$data = trim($_GET['data'] ?? '');

if ($data === '') {
    http_response_code(400);
    exit;
}

$autoloadPath = 'C:/xampp/phpMyAdmin/vendor/autoload.php';

if (!is_readable($autoloadPath)) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'QR generator not available.';
    exit;
}

require_once $autoloadPath;

use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

$renderer = new ImageRenderer(
    new RendererStyle(220),
    new SvgImageBackEnd()
);
$writer = new Writer($renderer);

header('Content-Type: image/svg+xml; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
echo $writer->writeString($data);
