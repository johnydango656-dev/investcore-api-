<?php
declare(strict_types=1);

namespace App\Config;

class Auth
{
    private static function getSecret(): string
    {
        return getenv('JWT_SECRET') ?: 'default_investment_secret_change_in_production_key_32chars';
    }

    public static function generateToken(array $payload, int $expirySeconds = 86400): string
    {
        $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
        $time = time();
        $payload['iat'] = $time;
        $payload['exp'] = $time + $expirySeconds;

        $base64Header = self::base64UrlEncode((string) $header);
        $base64Payload = self::base64UrlEncode((string) json_encode($payload));

        $signature = hash_hmac('sha256', "{$base64Header}.{$base64Payload}", self::getSecret(), true);
        $base64Signature = self::base64UrlEncode($signature);

        return "{$base64Header}.{$base64Payload}.{$base64Signature}";
    }

    public static function validateToken(string $token): ?array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return null;
        }

        [$base64Header, $base64Payload, $base64Signature] = $parts;

        $expectedSignature = hash_hmac('sha256', "{$base64Header}.{$base64Payload}", self::getSecret(), true);
        $providedSignature = self::base64UrlDecode($base64Signature);

        if (!hash_equals($expectedSignature, $providedSignature)) {
            return null;
        }

        $payload = json_decode(self::base64UrlDecode($base64Payload), true);
        if (!is_array($payload)) {
            return null;
        }

        if (isset($payload['exp']) && $payload['exp'] < time()) {
            return null;
        }

        return $payload;
    }

    public static function getBearerToken(): ?string
    {
        $headers = null;
        if (isset($_SERVER['Authorization'])) {
            $headers = trim((string) $_SERVER['Authorization']);
        } elseif (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $headers = trim((string) $_SERVER['HTTP_AUTHORIZATION']);
        } elseif (function_exists('apache_request_headers')) {
            $requestHeaders = apache_request_headers();
            if (isset($requestHeaders['Authorization'])) {
                $headers = trim((string) $requestHeaders['Authorization']);
            }
        }

        if (!empty($headers) && preg_match('/Bearer\s(\S+)/i', $headers, $matches)) {
            return $matches[1];
        }

        return null;
    }

    public static function requireAuth(): array
    {
        $token = self::getBearerToken();
        if (!$token) {
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'error' => 'Unauthorized: Missing Bearer token in Authorization header',
            ]);
            exit;
        }

        $payload = self::validateToken($token);
        if (!$payload) {
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'error' => 'Unauthorized: Invalid or expired token',
            ]);
            exit;
        }

        return $payload;
    }

    private static function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private static function base64UrlDecode(string $data): string
    {
        return base64_decode(strtr($data, '-_', '+/'));
    }
}