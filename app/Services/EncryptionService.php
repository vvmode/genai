<?php

namespace App\Services;

use Exception;

class EncryptionService
{
    private string $cipher = 'AES-256-CBC';
    
    /**
     * Generate a random encryption key
     * 
     * @return string Base64 encoded encryption key
     */
    public function generateKey(): string
    {
        return base64_encode(random_bytes(32));
    }

    /**
     * Encrypt data with AES-256-CBC
     * 
     * @param string $data Data to encrypt
     * @param string $key Base64 encoded encryption key
     * @return array ['encrypted' => string, 'iv' => string]
     * @throws Exception
     */
    public function encrypt(string $data, string $key): array
    {
        $key = base64_decode($key);
        
        if (strlen($key) !== 32) {
            throw new Exception('Invalid encryption key length. Must be 32 bytes.');
        }

        $iv = random_bytes(openssl_cipher_iv_length($this->cipher));
        
        $encrypted = openssl_encrypt(
            $data,
            $this->cipher,
            $key,
            OPENSSL_RAW_DATA,
            $iv
        );

        if ($encrypted === false) {
            throw new Exception('Encryption failed: ' . openssl_error_string());
        }

        return [
            'encrypted' => base64_encode($encrypted),
            'iv' => base64_encode($iv)
        ];
    }

    /**
     * Decrypt data with AES-256-CBC
     * 
     * @param string $encryptedData Base64 encoded encrypted data
     * @param string $key Base64 encoded encryption key
     * @param string $iv Base64 encoded initialization vector
     * @return string Decrypted data
     * @throws Exception
     */
    public function decrypt(string $encryptedData, string $key, string $iv): string
    {
        $key = base64_decode($key);
        $encryptedData = base64_decode($encryptedData);
        $iv = base64_decode($iv);

        if (strlen($key) !== 32) {
            throw new Exception('Invalid encryption key length. Must be 32 bytes.');
        }

        $decrypted = openssl_decrypt(
            $encryptedData,
            $this->cipher,
            $key,
            OPENSSL_RAW_DATA,
            $iv
        );

        if ($decrypted === false) {
            throw new Exception('Decryption failed: ' . openssl_error_string());
        }

        return $decrypted;
    }

    /**
     * Encrypt PDF base64 string
     * 
     * @param string $pdfBase64 Base64 encoded PDF
     * @param string|null $key Optional encryption key (generates if null)
     * @return array ['encrypted' => string, 'iv' => string, 'key' => string]
     * @throws Exception
     */
    public function encryptPdf(string $pdfBase64, ?string $key = null): array
    {
        if ($key === null) {
            $key = $this->generateKey();
        }

        $result = $this->encrypt($pdfBase64, $key);
        $result['key'] = $key;

        return $result;
    }

    /**
     * Decrypt PDF base64 string
     * 
     * @param string $encryptedData Base64 encoded encrypted PDF
     * @param string $key Base64 encoded encryption key
     * @param string $iv Base64 encoded initialization vector
     * @return string Base64 encoded PDF
     * @throws Exception
     */
    public function decryptPdf(string $encryptedData, string $key, string $iv): string
    {
        return $this->decrypt($encryptedData, $key, $iv);
    }

    /**
     * Create encrypted package with metadata
     * 
     * @param string $data Data to encrypt
     * @param string $key Encryption key
     * @return string JSON string with encrypted data and IV
     * @throws Exception
     */
    public function createEncryptedPackage(string $data, string $key): string
    {
        $encrypted = $this->encrypt($data, $key);
        
        return json_encode([
            'encrypted_data' => $encrypted['encrypted'],
            'iv' => $encrypted['iv'],
            'algorithm' => $this->cipher,
            'encrypted_at' => now()->toIso8601String()
        ]);
    }

    /**
     * Unpack encrypted package
     * 
     * @param string $package JSON string with encrypted data
     * @param string $key Encryption key
     * @return string Decrypted data
     * @throws Exception
     */
    public function unpackEncryptedPackage(string $package, string $key): string
    {
        $data = json_decode($package, true);
        
        if (!isset($data['encrypted_data']) || !isset($data['iv'])) {
            throw new Exception('Invalid encrypted package format');
        }

        return $this->decrypt($data['encrypted_data'], $key, $data['iv']);
    }
}
