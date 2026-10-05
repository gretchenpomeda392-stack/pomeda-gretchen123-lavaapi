<?php

defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');


class AuthController extends Controller
{
    public function __construct()
    {
        parent::__construct();

        // Database
        $this->call->database();

        // API library
        $this->call->library('api');

        // Users model
        $this->call->model('UsersModel');
    }


    /*
    |--------------------------------------------------------------------------
    | REGISTER
    |--------------------------------------------------------------------------
    */

    public function register()
    {
        $this->api->require_method('POST');

        $input = $this->api->body();

        $username = trim(
            $input['username'] ?? ''
        );

        $email = trim(
            $input['email'] ?? ''
        );

        $password =
            $input['password'] ?? '';

        $role = trim(
            $input['role'] ?? 'user'
        );


        // Required fields

        if (
            $username === '' ||
            $email === '' ||
            $password === ''
        ) {
            $this->api->respond_error(
                'Username, email and password are required.',
                400
            );
        }


        // Validate email

        if (
            !filter_var(
                $email,
                FILTER_VALIDATE_EMAIL
            )
        ) {
            $this->api->respond_error(
                'Invalid email address.',
                400
            );
        }


        // Validate password

        if (
            strlen($password) < 6
        ) {
            $this->api->respond_error(
                'Password must be at least 6 characters.',
                400
            );
        }


        // Validate role

        if (
            !in_array(
                $role,
                ['user', 'admin'],
                true
            )
        ) {
            $this->api->respond_error(
                'Invalid role.',
                400
            );
        }


        // Check username

        $stmt = $this->db->raw(
            'SELECT id
             FROM users
             WHERE username = ?
             LIMIT 1',
            [$username]
        );

        $existingUsername =
            $stmt->fetch(
                PDO::FETCH_ASSOC
            );


        if ($existingUsername) {

            $this->api->respond_error(
                'Username already exists.',
                409
            );
        }


        // Check email

        $stmt = $this->db->raw(
            'SELECT id
             FROM users
             WHERE email = ?
             LIMIT 1',
            [$email]
        );

        $existingEmail =
            $stmt->fetch(
                PDO::FETCH_ASSOC
            );


        if ($existingEmail) {

            $this->api->respond_error(
                'Email already exists.',
                409
            );
        }


        // Hash password

        $hashedPassword =
            password_hash(
                $password,
                PASSWORD_BCRYPT
            );


        // Insert user

        $this->db->raw(
            'INSERT INTO users
            (
                username,
                email,
                password,
                role,
                created_at
            )
            VALUES (?, ?, ?, ?, NOW())',
            [
                $username,
                $email,
                $hashedPassword,
                $role
            ]
        );


        // Response

        $this->api->respond(
            [
                'message' =>
                    'Account created successfully.'
            ],
            201
        );
    }


    /*
    |--------------------------------------------------------------------------
    | LOGIN
    |--------------------------------------------------------------------------
    */

public function login()
{
    $this->api->require_method('POST');

    $input = $this->api->body();

    $username = trim($input['username'] ?? '');
    $password = $input['password'] ?? '';

    if ($username === '' || $password === '') {
        $this->api->respond_error(
            'Username and password are required.',
            400
        );
    }

    $stmt = $this->db->raw(
        'SELECT id, username, email, password, role, created_at
         FROM users
         WHERE username = ?
         LIMIT 1',
        [$username]
    );

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        $this->api->respond_error(
            'Invalid username or password.',
            401
        );
    }

    if (!password_verify($password, $user['password'])) {
        $this->api->respond_error(
            'Invalid username or password.',
            401
        );
    }

    // CREATE ACCESS + REFRESH TOKEN
    $tokens = $this->api->issue_tokens([
        'id' => (int) $user['id'],
        'role' => $user['role']
    ]);

    if (
        !is_array($tokens) ||
        empty($tokens['access_token']) ||
        empty($tokens['refresh_token'])
    ) {
        $this->api->respond_error(
            'Token generation failed.',
            500
        );
    }

    $this->api->respond([
        'message' => 'Login successful.',
        'user' => [
            'id' => $user['id'],
            'username' => $user['username'],
            'email' => $user['email'],
            'role' => $user['role']
        ],
        'access_token' => $tokens['access_token'],
        'refresh_token' => $tokens['refresh_token'],
        'expires_in' => $tokens['expires_in'] ?? 900,
        'token_type' => $tokens['token_type'] ?? 'Bearer'
    ]);
}


    /*
    |--------------------------------------------------------------------------
    | LOGOUT
    |--------------------------------------------------------------------------
    */

    public function logout()
    {
        $this->api->require_method('POST');

        $input =
            $this->api->body();


        $refreshToken =
            $input['refresh_token'] ?? '';


        if (
            $refreshToken !== ''
        ) {

            $this->api
                ->revoke_refresh_token(
                    $refreshToken
                );
        }


        $this->api->respond(
            [
                'message' =>
                    'Logout successful.'
            ]
        );
    }


    /*
    |--------------------------------------------------------------------------
    | REFRESH TOKEN
    |--------------------------------------------------------------------------
    */

    public function refresh()
    {
        $this->api->require_method('POST');

        $input =
            $this->api->body();


        $refreshToken =
            $input['refresh_token'] ?? '';


        if (
            $refreshToken === ''
        ) {

            $this->api->respond_error(
                'Refresh token is required.',
                400
            );
        }


        $this->api
            ->refresh_access_token(
                $refreshToken
            );
    }


    /*
    |--------------------------------------------------------------------------
    | PROFILE
    |--------------------------------------------------------------------------
    */

    public function profile()
    {
        $auth =
            $this->api->require_jwt();


        $stmt = $this->db->raw(
            'SELECT
                id,
                username,
                email,
                role,
                created_at
             FROM users
             WHERE id = ?',
            [$auth['sub']]
        );


        $user =
            $stmt->fetch(
                PDO::FETCH_ASSOC
            );


        if (!$user) {

            $this->api->respond_error(
                'User not found.',
                404
            );
        }


        $this->api->respond(
            [
                'user' =>
                    $user
            ]
        );
    }
}