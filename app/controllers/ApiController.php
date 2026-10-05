<?php

defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

class ApiController extends Controller
{
    public function __construct()
    {
        parent::__construct();

        // Load API library
        $this->call->library('api');

        // Load Product model
        $this->call->model('ProductModel');
    }

    
    

    public function register()
    {
        $this->api->require_method('POST');

        $input = $this->api->body();

        $username = trim($input['username'] ?? '');
        $email    = trim($input['email'] ?? '');
        $password = $input['password'] ?? '';

        // Validation
        if ($username === '' || $email === '' || $password === '') {
            $this->api->respond_error(
                'Username, email and password are required.',
                400
            );
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->api->respond_error(
                'Invalid email address.',
                400
            );
        }

        if (strlen($password) < 6) {
            $this->api->respond_error(
                'Password must be at least 6 characters.',
                400
            );
        }

        // Check if username already exists
        $stmt = $this->db->raw(
            'SELECT id FROM users WHERE username = ? LIMIT 1',
            [$username]
        );

        $existingUsername = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existingUsername) {
            $this->api->respond_error(
                'Username already exists.',
                409
            );
        }

        // Check if email already exists
        $stmt = $this->db->raw(
            'SELECT id FROM users WHERE email = ? LIMIT 1',
            [$email]
        );

        $existingEmail = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existingEmail) {
            $this->api->respond_error(
                'Email already exists.',
                409
            );
        }

        // Hash password
        $hashedPassword = password_hash(
            $password,
            PASSWORD_BCRYPT
        );

        // Insert user
        $this->db->raw(
            'INSERT INTO users
            (username, email, password, role, created_at)
            VALUES (?, ?, ?, ?, NOW())',
            [
                $username,
                $email,
                $hashedPassword,
                'user'
            ]
        );

        $this->api->respond([
            'message' => 'Account created successfully.'
        ], 201);
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
            'SELECT * FROM users WHERE username = ? LIMIT 1',
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

        // Generate access and refresh tokens
        $tokens = $this->api->issue_tokens([
            'id'   => $user['id'],
            'role' => $user['role']
        ]);

        $this->api->respond([
            'message' => 'Login successful.',

            'user' => [
                'id'       => $user['id'],
                'username' => $user['username'],
                'email'    => $user['email'],
                'role'     => $user['role']
            ],

            'tokens' => $tokens
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

        $input = $this->api->body();

        $refreshToken = $input['refresh_token'] ?? '';

        if ($refreshToken !== '') {
            $this->api->revoke_refresh_token($refreshToken);
        }

        $this->api->respond([
            'message' => 'Logout successful.'
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | REFRESH TOKEN
    |--------------------------------------------------------------------------
    */

    public function refresh()
    {
        $this->api->require_method('POST');

        $input = $this->api->body();

        $refreshToken = $input['refresh_token'] ?? '';

        if ($refreshToken === '') {
            $this->api->respond_error(
                'Refresh token is required.',
                400
            );
        }

        $this->api->refresh_access_token($refreshToken);
    }


    /*
    |--------------------------------------------------------------------------
    | PROFILE
    |--------------------------------------------------------------------------
    */

    public function profile()
    {
        $auth = $this->api->require_jwt();

        $stmt = $this->db->raw(
            'SELECT id, username, email, role, created_at
             FROM users
             WHERE id = ?',
            [$auth['sub']]
        );

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            $this->api->respond_error(
                'User not found.',
                404
            );
        }

        $this->api->respond([
            'user' => $user
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | GET ALL PRODUCTS
    |--------------------------------------------------------------------------
    */

    public function products()
    {
        $this->api->require_method('GET');

        // Authentication required
        $this->api->require_jwt();

        $products = $this->ProductModel->getAll();

        $this->api->respond([
            'message' => 'Products retrieved successfully.',
            'data'    => $products
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | GET SINGLE PRODUCT
    |--------------------------------------------------------------------------
    */

    public function product($id)
    {
        $this->api->require_method('GET');

        // Authentication required
        $this->api->require_jwt();

        $id = (int) $id;

        $product = $this->ProductModel->getById($id);

        if (!$product) {
            $this->api->respond_error(
                'Product not found.',
                404
            );
        }

        $this->api->respond([
            'message' => 'Product retrieved successfully.',
            'data'    => $product
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | CREATE PRODUCT
    |--------------------------------------------------------------------------
    */

    public function createProduct()
    {
        $this->api->require_method('POST');

        // Authentication required
        $this->api->require_jwt();

        $input = $this->api->body();

        $productName = trim($input['product_name'] ?? '');
        $description = trim($input['description'] ?? '');
        $price       = $input['price'] ?? null;
        $quantity    = $input['quantity'] ?? null;

        // Product name
        if ($productName === '') {
            $this->api->respond_error(
                'Product name is required.',
                400
            );
        }

        // Price
        if ($price === null || $price === '') {
            $this->api->respond_error(
                'Price is required.',
                400
            );
        }

        if (!is_numeric($price)) {
            $this->api->respond_error(
                'Price must be a number.',
                400
            );
        }

        // Quantity
        if ($quantity === null || $quantity === '') {
            $this->api->respond_error(
                'Quantity is required.',
                400
            );
        }

        if (!is_numeric($quantity)) {
            $this->api->respond_error(
                'Quantity must be a number.',
                400
            );
        }

        $data = [
            'product_name' => $productName,
            'description'  => $description,
            'price'        => number_format(
                (float) $price,
                2,
                '.',
                ''
            ),
            'quantity'     => (int) $quantity,
            'created_at'   => date('Y-m-d H:i:s')
        ];

        $id = $this->ProductModel->create($data);

        $this->api->respond([
            'message' => 'Product created successfully.',
            'id'      => $id
        ], 201);
    }


    /*
    |--------------------------------------------------------------------------
    | UPDATE PRODUCT
    |--------------------------------------------------------------------------
    */

    public function updateProduct($id)
    {
        $this->api->require_method('PUT');

        // Authentication required
        $this->api->require_jwt();

        $id = (int) $id;

        // Check product
        $existing = $this->ProductModel->getById($id);

        if (!$existing) {
            $this->api->respond_error(
                'Product not found.',
                404
            );
        }

        $input = $this->api->body();

        $productName = trim($input['product_name'] ?? '');
        $description = trim($input['description'] ?? '');
        $price       = $input['price'] ?? null;
        $quantity    = $input['quantity'] ?? null;

        if ($productName === '') {
            $this->api->respond_error(
                'Product name is required.',
                400
            );
        }

        if ($price === null || $price === '') {
            $this->api->respond_error(
                'Price is required.',
                400
            );
        }

        if (!is_numeric($price)) {
            $this->api->respond_error(
                'Price must be a number.',
                400
            );
        }

        if ($quantity === null || $quantity === '') {
            $this->api->respond_error(
                'Quantity is required.',
                400
            );
        }

        if (!is_numeric($quantity)) {
            $this->api->respond_error(
                'Quantity must be a number.',
                400
            );
        }

        $data = [
            'product_name' => $productName,
            'description'  => $description,
            'price'        => number_format(
                (float) $price,
                2,
                '.',
                ''
            ),
            'quantity'     => (int) $quantity
        ];

        $this->ProductModel->updateProduct(
            $id,
            $data
        );

        $this->api->respond([
            'message' => 'Product updated successfully.'
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | DELETE PRODUCT
    |--------------------------------------------------------------------------
    */

    public function deleteProduct($id)
    {
        $this->api->require_method('DELETE');

        // Authentication required
        $this->api->require_jwt();

        $id = (int) $id;

        // Check product
        $existing = $this->ProductModel->getById($id);

        if (!$existing) {
            $this->api->respond_error(
                'Product not found.',
                404
            );
        }

        $this->ProductModel->deleteProduct($id);

        $this->api->respond([
            'message' => 'Product deleted successfully.'
        ]);
    }
}