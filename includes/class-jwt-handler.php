<?php
/**
 * JWT Handler for Zoom API
 * @since 1.5.0
 */
class FLI_JWT_Handler {
    /**
     * Generate JWT token
     * @param string $key API Key
     * @param string $secret API Secret
     * @return string JWT token
     */
    public static function generate($key, $secret) {
        $header = [
            'typ' => 'JWT',
            'alg' => 'HS256'
        ];
        
        $payload = [
            'iss' => $key,
            'exp' => time() + 3600
        ];

        $header_encoded = self::base64url_encode(json_encode($header));
        $payload_encoded = self::base64url_encode(json_encode($payload));
        
        $signature = hash_hmac('sha256', 
            $header_encoded . "." . $payload_encoded, 
            $secret, 
            true
        );
        
        $signature_encoded = self::base64url_encode($signature);
        
        return $header_encoded . "." . $payload_encoded . "." . $signature_encoded;
    }

    /**
     * Base64URL encode
     * @param string $data Data to encode
     * @return string Encoded data
     */
    private static function base64url_encode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
} 