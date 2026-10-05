<?php

$allowed_origins = [
    'http://localhost:5182',
    'http://127.0.0.1:5182',

    // CURRENT VITE PORT
    'http://localhost:5184',
    'http://127.0.0.1:5184',

    // VERCEL
    'https://pomeda-gretchen123-lavaapi.vercel.app'
];

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

if (in_array($origin, $allowed_origins, true)) {
    header('Access-Control-Allow-Origin: ' . $origin);
    header('Access-Control-Allow-Credentials: true');
    header('Vary: Origin');
}

header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');

header(
    'Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept'
);

header('Access-Control-Max-Age: 86400');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

define('PREVENT_DIRECT_ACCESS', TRUE);

$system_path = 'scheme';
$application_folder = 'app';
$public_folder = 'public';

define('ROOT_DIR', dirname(__DIR__) . DIRECTORY_SEPARATOR);
define('SYSTEM_DIR', ROOT_DIR . $system_path . DIRECTORY_SEPARATOR);
define('APP_DIR', ROOT_DIR . $application_folder . DIRECTORY_SEPARATOR);
define('PUBLIC_DIR', $public_folder);

require_once SYSTEM_DIR . 'kernel/LavaLust.php';