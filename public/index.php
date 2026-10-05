<?php

// --------------------------------------------------
// CORS CONFIGURATION
// --------------------------------------------------

$allowed_origins = [
    'http://localhost:5182',
    'http://127.0.0.1:5182',
    'https://pomeda-gretchen123-lavaapi.vercel.app'
];

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

if (in_array($origin, $allowed_origins, true)) {
    header("Access-Control-Allow-Origin: $origin");
    header("Access-Control-Allow-Credentials: true");
}

header("Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept");
header("Access-Control-Max-Age: 86400");

// Handle preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

define('PREVENT_DIRECT_ACCESS', TRUE);