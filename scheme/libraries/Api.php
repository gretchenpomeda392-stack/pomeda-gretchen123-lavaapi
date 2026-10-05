<?php

defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

/**
 * ------------------------------------------------------------------
 * LavaLust - an opensource lightweight PHP MVC Framework
 * ------------------------------------------------------------------
 */

class Api
{
    /**
     * Minimum JWT secret length
     */
    private const MIN_SECRET_LENGTH = 32;

    /**
     * LavaLust Super Object
     */
    private $_lava;

    /**
     * Refresh Token Table
     */
    protected $refresh_token_table;

    /**
     * Users Table
     */
    protected $users_table = 'users';

    /**
     * Verify User On Each Request
     */
    protected $verify_user = true;

    /**
     * Access Token Expiration
     */
    protected $payload_token_expiration = 900;

    /**
     * Refresh Token Expiration
     */
    protected $refresh_token_expiration = 604800;

    /**
     * CORS
     */
    protected $allow_origin;

    /**
     * JWT Secret
     */
    private $jwt_secret;

    /**
     * Refresh Token Secret
     */
    private $refresh_token_key;

    /**
     * JWT Issuer
     */
    protected $jwt_issuer;

    /**
     * JWT Audience
     */
    protected $jwt_audience;

    /**
     * Rate Limiting
     */
    protected $rate_limit_enabled;

    protected $rate_limit_requests = 60;

    protected $rate_limit_seconds = 60;


    /**
     * Constructor
     */
    public function __construct()
    {
        $this->_lava = lava_instance();

        $this->_lava->call->library('cache');

        $this->_lava->config->load('api');

        if (!config_item('api_helper_enabled')) {
            show_error('Api Helper is disabled or set up incorrectly.');
        }

        /*
        |--------------------------------------------------------------------------
        | Load configuration
        |--------------------------------------------------------------------------
        */

        $this->refresh_token_table =
            config_item('refresh_token_table')
            ?? 'refresh_tokens';

        $this->users_table =
            config_item('users_table')
            ?? 'users';

        $this->verify_user =
            (bool) (
                config_item('jwt_verify_user')
                ?? true
            );

        $this->payload_token_expiration =
            (int) (
                config_item('payload_token_expiration')
                ?? 900
            );

        $this->refresh_token_expiration =
            (int) (
                config_item('refresh_token_expiration')
                ?? 604800
            );

        $this->jwt_secret =
            config_item('jwt_secret');

        $this->refresh_token_key =
            config_item('refresh_token_key');

        $this->allow_origin =
            config_item('allow_origin');

        $this->jwt_issuer =
            config_item('jwt_issuer')
            ?? '';

        $this->jwt_audience =
            config_item('jwt_audience')
            ?? '';

        /*
        |--------------------------------------------------------------------------
        | Rate Limit
        |--------------------------------------------------------------------------
        */

        $this->rate_limit_enabled =
            (bool) (
                config_item('rate_limit_enabled')
                ?? true
            );

        $this->rate_limit_requests =
            (int) (
                config_item('rate_limit_requests')
                ?? 60
            );

        $this->rate_limit_seconds =
            (int) (
                config_item('rate_limit_seconds')
                ?? 60
            );

        /*
        |--------------------------------------------------------------------------
        | Validate secrets
        |--------------------------------------------------------------------------
        */

        $this->assert_secret_is_safe(
            $this->jwt_secret,
            'jwt_secret'
        );

        $this->assert_secret_is_safe(
            $this->refresh_token_key,
            'refresh_token_key'
        );

        if (
            hash_equals(
                (string) $this->jwt_secret,
                (string) $this->refresh_token_key
            )
        ) {
            show_error(
                'jwt_secret and refresh_token_key must be different values.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | CORS
        |--------------------------------------------------------------------------
        */

        handle_cors();
    }


    /**
     * Validate secret
     */
    private function assert_secret_is_safe($secret, $name)
    {
        $secret = (string) $secret;

        if (
            $secret === ''
            || strlen($secret) < self::MIN_SECRET_LENGTH
        ) {
            show_error(
                "{$name} is missing or too short. " .
                "Use at least " .
                self::MIN_SECRET_LENGTH .
                " random characters."
            );
        }

        if (
            count(
                array_unique(
                    str_split($secret)
                )
            ) < 10
        ) {
            show_error(
                "{$name} has too little entropy. " .
                "Use a random value."
            );
        }
    }


    /**
     * Get API body
     */
    public function body()
    {
        $contentType =
            $_SERVER['CONTENT_TYPE']
            ?? '';

        if (
            stripos(
                $contentType,
                'application/json'
            ) !== false
        ) {
            $input = json_decode(
                file_get_contents('php://input'),
                true
            );

            return is_array($input)
                ? $this->sanitize_input($input)
                : [];
        }

        if (!empty($_POST)) {
            return $this->sanitize_input($_POST);
        }

        parse_str(
            file_get_contents('php://input'),
            $formData
        );

        return $this->sanitize_input(
            $formData ?? []
        );
    }


    /**
     * Get query parameters
     */
    public function get_query_params()
    {
        return $this->sanitize_input($_GET);
    }


    /**
     * Sanitize input
     */
    private function sanitize_input($data)
    {
        if (!is_array($data)) {
            return [];
        }

        array_walk_recursive(
            $data,
            function (&$value) {

                if (is_string($value)) {
                    $value = trim(
                        htmlspecialchars(
                            $value,
                            ENT_QUOTES,
                            'UTF-8'
                        )
                    );
                }
            }
        );

        return $data;
    }


    /**
     * Require HTTP method
     */
    public function require_method(string $method)
    {
        $requestMethod =
            $_SERVER['REQUEST_METHOD']
            ?? '';

        if (
            strtoupper($requestMethod)
            !== strtoupper($method)
        ) {
            $this->respond_error(
                'Method Not Allowed',
                405
            );
        }
    }


    /**
     * Rate limiting
     */
    public function rate_limit(
        $key = null,
        $requests = null,
        $seconds = null
    ) {
        if (!$this->rate_limit_enabled) {
            return;
        }

        $requests =
            $requests
            ?? $this->rate_limit_requests;

        $seconds =
            $seconds
            ?? $this->rate_limit_seconds;

        if ($key === null) {

            $ip =
                $_SERVER['REMOTE_ADDR']
                ?? 'unknown';

            $raw_key =
                'rate_limit:' . $ip;

        } else {

            $raw_key =
                'rate_limit:' . $key;
        }

        /*
        |--------------------------------------------------------------------------
        | Make cache key safe
        |--------------------------------------------------------------------------
        */

        $safe_key =
            str_replace(
                [
                    ':',
                    '/',
                    '\\',
                    '*',
                    '?',
                    '"',
                    '<',
                    '>',
                    '|'
                ],
                '_',
                $raw_key
            );

        $safe_key =
            preg_replace(
                '/[^a-zA-Z0-9_\-]/',
                '_',
                $safe_key
            );

        $cache =
            $this->_lava->cache;

        $current =
            $cache->get($safe_key);

        $window_start =
            $cache->get(
                $safe_key . '_start'
            );

        $current =
            is_numeric($current)
            ? (int) $current
            : 0;

        $window_start =
            is_numeric($window_start)
            ? (int) $window_start
            : 0;

        $now = time();

        if (
            $window_start === 0
            || ($now - $window_start) >= $seconds
        ) {

            $cache->write(
                1,
                $safe_key,
                $seconds
            );

            $cache->write(
                $now,
                $safe_key . '_start',
                $seconds
            );

            $remaining =
                $requests - 1;

            $window_start =
                $now;

        } else {

            if ($current >= $requests) {

                $reset_time =
                    $window_start + $seconds;

                $this->respond_rate_limit_exceeded(
                    $requests,
                    $current,
                    $reset_time
                );
            }

            $cache->write(
                $current + 1,
                $safe_key,
                $seconds
            );

            $remaining =
                $requests - ($current + 1);
        }

        header(
            "X-RateLimit-Limit: {$requests}"
        );

        header(
            "X-RateLimit-Remaining: {$remaining}"
        );

        header(
            "X-RateLimit-Reset: " .
            ($window_start + $seconds)
        );
    }


    /**
     * Rate limit response
     */
    private function respond_rate_limit_exceeded(
        $limit,
        $used,
        $reset_time
    ) {
        $retry_after =
            max(
                0,
                $reset_time - time()
            );

        header(
            "Retry-After: {$retry_after}"
        );

        $this->respond(
            [
                'error' =>
                    'Too many requests. Please try again later.',

                'limit' =>
                    $limit,

                'used' =>
                    $used,

                'remaining' =>
                    0,

                'reset_at' =>
                    date(
                        'c',
                        $reset_time
                    ),

                'retry_after' =>
                    $retry_after
            ],
            429
        );
    }


    /**
     * API response
     */
    public function respond(
        $data,
        $code = 200
    ) {
        http_response_code($code);

        header(
            'Content-Type: application/json; charset=utf-8'
        );

        echo json_encode(
            $data,
            JSON_UNESCAPED_SLASHES
            | JSON_UNESCAPED_UNICODE
        );

        exit;
    }


    /**
     * API error response
     */
    public function respond_error(
        $message,
        $code = 400
    ) {
        $this->respond(
            [
                'error' => $message,
                'status' => $code
            ],
            $code
        );
    }


    /**
     * Base64 URL encode
     */
    private function base64UrlEncode($data)
    {
        return rtrim(
            strtr(
                base64_encode($data),
                '+/',
                '-_'
            ),
            '='
        );
    }


    /**
     * Base64 URL decode
     */
    private function base64UrlDecode($data)
    {
        $pad =
            strlen($data) % 4;

        if ($pad) {
            $data .= str_repeat(
                '=',
                4 - $pad
            );
        }

        return base64_decode(
            strtr(
                $data,
                '-_',
                '+/'
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | JWT
    |--------------------------------------------------------------------------
    */


    /**
     * Encode JWT
     */
    public function encode_jwt($payload)
    {
        $header = [
            'alg' => 'HS256',
            'typ' => 'JWT'
        ];

        $headerEnc =
            $this->base64UrlEncode(
                json_encode($header)
            );

        $now = time();

        $payload = array_merge(
            [
                'iat' =>
                    $now,

                'exp' =>
                    $now +
                    $this->payload_token_expiration,

                'iss' =>
                    $this->jwt_issuer,

                'aud' =>
                    $this->jwt_audience,

                'jti' =>
                    bin2hex(
                        random_bytes(16)
                    )
            ],
            $payload
        );

        $payloadEnc =
            $this->base64UrlEncode(
                json_encode($payload)
            );

        $signature =
            hash_hmac(
                'sha256',
                "{$headerEnc}.{$payloadEnc}",
                $this->jwt_secret,
                true
            );

        $sigEnc =
            $this->base64UrlEncode(
                $signature
            );

        return "{$headerEnc}.{$payloadEnc}.{$sigEnc}";
    }


    /**
     * Decode JWT
     */
    public function decode_jwt($token)
    {
        if (
            !is_string($token)
            || trim($token) === ''
        ) {
            return null;
        }

        $parts =
            explode(
                '.',
                $token
            );

        if (count($parts) !== 3) {
            return null;
        }

        [
            $headerEnc,
            $payloadEnc,
            $sigEnc
        ] = $parts;

        $headerJson =
            $this->base64UrlDecode(
                $headerEnc
            );

        $payloadJson =
            $this->base64UrlDecode(
                $payloadEnc
            );

        if (
            $headerJson === false
            || $payloadJson === false
        ) {
            return null;
        }

        $header =
            json_decode(
                $headerJson,
                true
            );

        $payload =
            json_decode(
                $payloadJson,
                true
            );

        if (!is_array($header)) {
            return null;
        }

        if (!is_array($payload)) {
            return null;
        }

        if (
            ($header['alg'] ?? '')
            !== 'HS256'
        ) {
            return null;
        }

        $validSig =
            hash_hmac(
                'sha256',
                "{$headerEnc}.{$payloadEnc}",
                $this->jwt_secret,
                true
            );

        $validSigEncoded =
            $this->base64UrlEncode(
                $validSig
            );

        if (
            !hash_equals(
                $validSigEncoded,
                $sigEnc
            )
        ) {
            return null;
        }

        return $payload;
    }


    /**
     * Validate JWT
     */
    public function validate_jwt(
        $token,
        $expected_type = 'access'
    ) {
        if (
            !is_string($token)
            || trim($token) === ''
        ) {
            return null;
        }

        $payload =
            $this->decode_jwt($token);

        if (!is_array($payload)) {
            return null;
        }

        if (
            !isset(
                $payload['sub'],
                $payload['exp'],
                $payload['iat']
            )
        ) {
            return null;
        }

        $now = time();

        if (
            (int) $payload['exp']
            < $now
        ) {
            return null;
        }

        if (
            (int) $payload['iat']
            > $now
        ) {
            return null;
        }

        if (
            ($payload['iss'] ?? '')
            !== $this->jwt_issuer
        ) {
            return null;
        }

        if (
            ($payload['aud'] ?? '')
            !== $this->jwt_audience
        ) {
            return null;
        }

        if (
            ($payload['type'] ?? 'access')
            !== $expected_type
        ) {
            return null;
        }

        return $payload;
    }


    /**
     * Get Bearer Token
     *
     * FIXED:
     * Proper regex for Authorization header.
     */
    public function get_bearer_token()
{
    $header =
        $_SERVER['HTTP_AUTHORIZATION']
        ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION']
        ?? '';

    if (
        empty($header)
        && function_exists(
            'apache_request_headers'
        )
    ) {
        $headers =
            apache_request_headers();

        $header =
            $headers['Authorization']
            ?? $headers['authorization']
            ?? '';
    }

    if (
        !is_string($header)
        || trim($header) === ''
    ) {
        return null;
    }

    if (
        preg_match(
            '/Bearer\s+(\S+)/i',
            $header,
            $matches
        )
    ) {
        return $matches[1];
    }

    return null;
}

    /**
     * Role scopes
     */
    protected function scopes_for_role($role)
    {
        $role_scopes = [
            'admin' => [
                'read',
                'write',
                'delete'
            ],

            'editor' => [
                'read',
                'write'
            ],

            'user' => [
                'read'
            ]
        ];

        return
            $role_scopes[$role]
            ?? ['read'];
    }


    /**
     * Require JWT authentication
     *
     * FIXED:
     * Never accesses array values when payload is null.
     */
    public function require_jwt()
    {
        /*
        |--------------------------------------------------------------------------
        | Get Bearer Token
        |--------------------------------------------------------------------------
        */

        $token =
            $this->get_bearer_token();

        if (
            empty($token)
        ) {
            $this->respond_error(
                'Unauthorized. Bearer token is required.',
                401
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Validate Access Token
        |--------------------------------------------------------------------------
        */

        $payload =
            $this->validate_jwt(
                $token,
                'access'
            );

        if (
            !is_array($payload)
        ) {
            $this->respond_error(
                'Unauthorized. Invalid or expired token.',
                401
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Check Subject
        |--------------------------------------------------------------------------
        */

        if (
            !isset($payload['sub'])
        ) {
            $this->respond_error(
                'Unauthorized. Invalid token payload.',
                401
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Verify User
        |--------------------------------------------------------------------------
        */

        if (
            $this->verify_user
        ) {

            $stmt =
                $this->_lava->db->raw(
                    "SELECT id, role
                     FROM {$this->users_table}
                     WHERE id = ?
                     LIMIT 1",
                    [
                        $payload['sub']
                    ]
                );

            $user =
                $stmt->fetch(
                    PDO::FETCH_ASSOC
                );

            if (
                !is_array($user)
            ) {
                $this->respond_error(
                    'Unauthorized. User not found.',
                    401
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Server-side role and scopes
            |--------------------------------------------------------------------------
            */

            $payload['role'] =
                $user['role'];

            $payload['scopes'] =
                $this->scopes_for_role(
                    $user['role']
                );
        }

        return $payload;
    }


    /*
    |--------------------------------------------------------------------------
    | TOKEN SYSTEM
    |--------------------------------------------------------------------------
    */


    /**
     * Issue Access + Refresh Tokens
     */
    public function issue_tokens(
        $user_data
    ) {
        if (
            !is_array($user_data)
            || !isset($user_data['id'])
        ) {
            $this->respond_error(
                'Unable to issue tokens. Invalid user data.',
                500
            );
        }

        $user_id =
            $user_data['id'];

        $now = time();

        $scopes =
            $user_data['scopes']
            ?? ['read'];

        /*
        |--------------------------------------------------------------------------
        | Access Token
        |--------------------------------------------------------------------------
        */

        $access_payload = [
            'sub' =>
                $user_id,

            'type' =>
                'access',

            'role' =>
                $user_data['role']
                ?? 'user',

            'scopes' =>
                $scopes
        ];

        /*
        |--------------------------------------------------------------------------
        | Refresh Token
        |--------------------------------------------------------------------------
        */

        $refresh_payload = [
            'sub' =>
                $user_id,

            'type' =>
                'refresh',

            'jti' =>
                bin2hex(
                    random_bytes(16)
                )
        ];

        $access_token =
            $this->encode_jwt(
                $access_payload
            );

        $refresh_token =
            $this->encode_jwt(
                $refresh_payload
            );

        /*
        |--------------------------------------------------------------------------
        | Hash Refresh Token Before Database Storage
        |--------------------------------------------------------------------------
        */

        $hashed_refresh =
            hash_hmac(
                'sha256',
                (string) $refresh_token,
                $this->refresh_token_key
            );

        $this->cleanup_expired_refresh_tokens();

        $expires_at =
            date(
                'Y-m-d H:i:s',
                $now +
                $this->refresh_token_expiration
            );

        /*
        |--------------------------------------------------------------------------
        | Store Refresh Token
        |--------------------------------------------------------------------------
        */

        $this->_lava->db->raw(
            "INSERT INTO {$this->refresh_token_table}
            (
                user_id,
                token,
                expires_at,
                jti
            )
            VALUES (?, ?, ?, ?)",
            [
                $user_id,
                $hashed_refresh,
                $expires_at,
                $refresh_payload['jti']
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | Return Tokens
        |--------------------------------------------------------------------------
        */

        return [
            'access_token' =>
                $access_token,

            'refresh_token' =>
                $refresh_token,

            'expires_in' =>
                $this->payload_token_expiration,

            'token_type' =>
                'Bearer'
        ];
    }


    /**
     * Refresh Access Token
     */
    public function refresh_access_token(
        $refresh_token
    ) {
        if (
            empty($refresh_token)
        ) {
            $this->respond_error(
                'Refresh token is required.',
                400
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Validate Refresh JWT
        |--------------------------------------------------------------------------
        */

        $payload =
            $this->validate_jwt(
                $refresh_token,
                'refresh'
            );

        if (
            !is_array($payload)
        ) {
            $this->respond_error(
                'Invalid refresh token.',
                403
            );
        }

        if (
            !isset($payload['sub'])
        ) {
            $this->respond_error(
                'Invalid refresh token payload.',
                403
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Hash Refresh Token
        |--------------------------------------------------------------------------
        */

        $hashed =
            hash_hmac(
                'sha256',
                $refresh_token,
                $this->refresh_token_key
            );

        /*
        |--------------------------------------------------------------------------
        | Check Database
        |--------------------------------------------------------------------------
        */

        $stmt =
            $this->_lava->db->raw(
                "SELECT *
                 FROM {$this->refresh_token_table}
                 WHERE token = ?
                 AND expires_at > NOW()
                 LIMIT 1",
                [
                    $hashed
                ]
            );

        $found =
            $stmt->fetch(
                PDO::FETCH_ASSOC
            );

        if (
            !is_array($found)
        ) {
            $this->respond_error(
                'Refresh token expired or revoked.',
                403
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Find User
        |--------------------------------------------------------------------------
        */

        $user_stmt =
            $this->_lava->db->raw(
                "SELECT id, role
                 FROM {$this->users_table}
                 WHERE id = ?
                 LIMIT 1",
                [
                    $payload['sub']
                ]
            );

        $user =
            $user_stmt->fetch(
                PDO::FETCH_ASSOC
            );

        if (
            !is_array($user)
        ) {
            $this->respond_error(
                'User not found.',
                403
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Revoke Old Refresh Token
        |--------------------------------------------------------------------------
        */

        $this->revoke_refresh_token(
            $refresh_token
        );

        /*
        |--------------------------------------------------------------------------
        | Issue New Tokens
        |--------------------------------------------------------------------------
        */

        $new_tokens =
            $this->issue_tokens(
                [
                    'id' =>
                        $user['id'],

                    'role' =>
                        $user['role'],

                    'scopes' =>
                        $this->scopes_for_role(
                            $user['role']
                        )
                ]
            );

        $this->respond(
            [
                'message' =>
                    'Tokens refreshed successfully',

                'tokens' =>
                    $new_tokens
            ]
        );
    }


    /**
     * Revoke Refresh Token
     */
    public function revoke_refresh_token(
        $refresh_token
    ) {
        if (
            empty($refresh_token)
        ) {
            return;
        }

        $hashed =
            hash_hmac(
                'sha256',
                $refresh_token,
                $this->refresh_token_key
            );

        $this->_lava->db->raw(
            "DELETE
             FROM {$this->refresh_token_table}
             WHERE token = ?",
            [
                $hashed
            ]
        );
    }


    /**
     * Cleanup expired refresh tokens
     */
    public function cleanup_expired_refresh_tokens(
        $user_id = null
    ): void {
        $sql =
            "DELETE
             FROM {$this->refresh_token_table}
             WHERE expires_at < NOW()";

        $params = [];

        if (
            $user_id !== null
        ) {
            $sql .=
                " AND user_id = ?";

            $params[] =
                $user_id;
        }

        $this->_lava->db->raw(
            $sql,
            $params
        );
    }


    /*
    |--------------------------------------------------------------------------
    | BASIC AUTH
    |--------------------------------------------------------------------------
    */


    /**
     * Check Basic Authentication
     */
    public function check_basic_auth(
        $valid_user,
        $valid_pass
    ) {
        $user =
            $_SERVER['PHP_AUTH_USER']
            ?? '';

        $pass =
            $_SERVER['PHP_AUTH_PW']
            ?? '';

        return
            hash_equals(
                $user,
                $valid_user
            )
            &&
            hash_equals(
                $pass,
                $valid_pass
            );
    }


    /**
     * Require Basic Authentication
     */
    public function require_basic_auth(
        $valid_user,
        $valid_pass
    ) {
        if (
            !$this->check_basic_auth(
                $valid_user,
                $valid_pass
            )
        ) {
            header(
                'WWW-Authenticate: Basic realm="API"'
            );

            $this->respond_error(
                'Unauthorized',
                401
            );
        }
    }
}