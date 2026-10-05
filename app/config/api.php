<?php

defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');


/*
|--------------------------------------------------------------------------
| API HELPER
|--------------------------------------------------------------------------
*/

$config['api_helper_enabled'] = TRUE;


/*
|--------------------------------------------------------------------------
| ACCESS TOKEN EXPIRATION
|--------------------------------------------------------------------------
| 15 minutes
*/

$config['payload_token_expiration'] = 900;


/*
|--------------------------------------------------------------------------
| REFRESH TOKEN EXPIRATION
|--------------------------------------------------------------------------
| 7 days
*/

$config['refresh_token_expiration'] = 604800;


/*
|--------------------------------------------------------------------------
| JWT SECRET
|--------------------------------------------------------------------------
| IMPORTANT:
| Must be at least 32 characters.
|
| For Render, set JWT_SECRET in Environment Variables.
*/

$config['jwt_secret'] = getenv('JWT_SECRET');

if (empty($config['jwt_secret'])) {
    $config['jwt_secret'] =
        'LavaLustJWTSecretKeyForLabExercise2026Secure123!';
}


/*
|--------------------------------------------------------------------------
| REFRESH TOKEN KEY
|--------------------------------------------------------------------------
| IMPORTANT:
| Must be at least 32 characters.
|
| For Render, set REFRESH_TOKEN_KEY in Environment Variables.
*/

$config['refresh_token_key'] = getenv('REFRESH_TOKEN_KEY');

if (empty($config['refresh_token_key'])) {
    $config['refresh_token_key'] =
        'LavaLustRefreshTokenKeyForLabExercise2026Secure123!';
}


/*
|--------------------------------------------------------------------------
| JWT VERIFY USER
|--------------------------------------------------------------------------
*/

$config['jwt_verify_user'] = TRUE;


/*
|--------------------------------------------------------------------------
| USERS TABLE
|--------------------------------------------------------------------------
*/

$config['users_table'] = 'users';


/*
|--------------------------------------------------------------------------
| CORS
|--------------------------------------------------------------------------
*/

$config['allow_origin'] = [
    'http://localhost:3000',
    'http://localhost:5173',
    'http://localhost:5174',
    'http://localhost:5175',
    'http://localhost:5182',

    'http://127.0.0.1:3000',
    'http://127.0.0.1:5173',
    'http://127.0.0.1:5174',
    'http://127.0.0.1:5175',
    'http://127.0.0.1:5182',

    'https://pomeda-gretchen123-lavaapi.vercel.app'
];


/*
|--------------------------------------------------------------------------
| REFRESH TOKEN TABLE
|--------------------------------------------------------------------------
*/

$config['refresh_token_table'] = 'refresh_tokens';


/*
|--------------------------------------------------------------------------
| JWT ISSUER
|--------------------------------------------------------------------------
*/

$config['jwt_issuer'] =
    'https://pomeda-gretchen123-lavaapi.onrender.com';


/*
|--------------------------------------------------------------------------
| JWT AUDIENCE
|--------------------------------------------------------------------------
*/

$config['jwt_audience'] =
    'https://pomeda-gretchen123-lavaapi.vercel.app';


/*
|--------------------------------------------------------------------------
| RATE LIMITING
|--------------------------------------------------------------------------
*/

$config['rate_limit_enabled'] = TRUE;


/*
|--------------------------------------------------------------------------
| RATE LIMIT REQUESTS
|--------------------------------------------------------------------------
*/

$config['rate_limit_requests'] = 60;


/*
|--------------------------------------------------------------------------
| RATE LIMIT SECONDS
|--------------------------------------------------------------------------
*/

$config['rate_limit_seconds'] = 60;