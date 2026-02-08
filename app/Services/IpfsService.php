<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class IpfsService
{
    private string $pinataApiKey;
    private string $pinataSecretKey;
    private string $pinataBaseUrl = 'https://api.pinata.cloud';
    private string $pinataGateway = 'https://gateway.pinata.cloud/ipfs';

    public function __construct()
    {
        $this->pinataApiKey = config('services.pinata.api_key', env('PINATA_API_KEY'));
        $this->pinataSecretKey = config('services.pinata.secret_key', env('PINATA_SECRET_KEY'));
    }

    /**
     * Upload encrypted PDF to IPFS via Pinata
     * 
     * @param string $encryptedData Base64 encoded encrypted data
     * @param array $metadata Optional metadata for the pin
     * @return array ['ipfsHash' => string, 'pinSize' => int, 'timestamp' => string]
     * @throws Exception
     */
    public function uploadToIpfs(string $encryptedData, array $metadata = []): array
    {
        try {
            // Create JSON object to upload
            $uploadData = [
                'encrypted_pdf' => $encryptedData,
                'metadata' => $metadata,
                'uploaded_at' => now()->toIso8601String()
            ];

            $response = Http::withHeaders([
                'pinata_api_key' => $this->pinataApiKey,
                'pinata_secret_api_key' => $this->pinataSecretKey,
            ])->attach(
                'file',
                json_encode($uploadData),
                'encrypted_document.json'
            )->post($this->pinataBaseUrl . '/pinning/pinFileToIPFS');

            if (!$response->successful()) {
                Log::error('IPFS upload failed', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                throw new Exception('Failed to upload to IPFS: ' . $response->body());
            }

            $result = $response->json();

            return [
                'ipfs_hash' => $result['IpfsHash'],
                'pin_size' => $result['PinSize'],
                'timestamp' => $result['Timestamp'],
                'gateway_url' => $this->pinataGateway . '/' . $result['IpfsHash']
            ];

        } catch (Exception $e) {
            Log::error('IPFS upload exception', ['error' => $e->getMessage()]);
            throw new Exception('IPFS upload failed: ' . $e->getMessage());
        }
    }

    /**
     * Retrieve data from IPFS
     * 
     * @param string $ipfsHash IPFS hash (CID)
     * @return array Decrypted JSON data
     * @throws Exception
     */
    public function retrieveFromIpfs(string $ipfsHash): array
    {
        try {
            $url = $this->pinataGateway . '/' . $ipfsHash;

            $response = Http::timeout(30)->get($url);

            if (!$response->successful()) {
                throw new Exception('Failed to retrieve from IPFS: ' . $response->status());
            }

            $data = $response->json();

            if (!isset($data['encrypted_pdf'])) {
                throw new Exception('Invalid IPFS data format');
            }

            return $data;

        } catch (Exception $e) {
            Log::error('IPFS retrieval failed', [
                'ipfs_hash' => $ipfsHash,
                'error' => $e->getMessage()
            ]);
            throw new Exception('IPFS retrieval failed: ' . $e->getMessage());
        }
    }

    /**
     * Pin existing IPFS hash (ensure it stays available)
     * 
     * @param string $ipfsHash IPFS hash to pin
     * @return array Pin status
     * @throws Exception
     */
    public function pinByHash(string $ipfsHash): array
    {
        try {
            $response = Http::withHeaders([
                'pinata_api_key' => $this->pinataApiKey,
                'pinata_secret_api_key' => $this->pinataSecretKey,
            ])->post($this->pinataBaseUrl . '/pinning/pinByHash', [
                'hashToPin' => $ipfsHash
            ]);

            if (!$response->successful()) {
                throw new Exception('Failed to pin hash: ' . $response->body());
            }

            return $response->json();

        } catch (Exception $e) {
            Log::error('IPFS pinning failed', [
                'ipfs_hash' => $ipfsHash,
                'error' => $e->getMessage()
            ]);
            throw new Exception('IPFS pinning failed: ' . $e->getMessage());
        }
    }

    /**
     * Unpin IPFS hash (remove from Pinata)
     * 
     * @param string $ipfsHash IPFS hash to unpin
     * @return bool Success status
     * @throws Exception
     */
    public function unpinHash(string $ipfsHash): bool
    {
        try {
            $response = Http::withHeaders([
                'pinata_api_key' => $this->pinataApiKey,
                'pinata_secret_api_key' => $this->pinataSecretKey,
            ])->delete($this->pinataBaseUrl . '/pinning/unpin/' . $ipfsHash);

            return $response->successful();

        } catch (Exception $e) {
            Log::error('IPFS unpinning failed', [
                'ipfs_hash' => $ipfsHash,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Test IPFS connection
     * 
     * @return bool Connection status
     */
    public function testConnection(): bool
    {
        try {
            $response = Http::withHeaders([
                'pinata_api_key' => $this->pinataApiKey,
                'pinata_secret_api_key' => $this->pinataSecretKey,
            ])->get($this->pinataBaseUrl . '/data/testAuthentication');

            return $response->successful();

        } catch (Exception $e) {
            Log::error('IPFS connection test failed', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Get pinned files list
     * 
     * @param int $limit Number of results to return
     * @return array List of pinned files
     */
    public function listPinnedFiles(int $limit = 10): array
    {
        try {
            $response = Http::withHeaders([
                'pinata_api_key' => $this->pinataApiKey,
                'pinata_secret_api_key' => $this->pinataSecretKey,
            ])->get($this->pinataBaseUrl . '/data/pinList', [
                'pageLimit' => $limit,
                'status' => 'pinned'
            ]);

            if ($response->successful()) {
                return $response->json();
            }

            return [];

        } catch (Exception $e) {
            Log::error('Failed to list pinned files', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Check if IPFS service is configured
     * 
     * @return bool
     */
    public function isConfigured(): bool
    {
        return !empty($this->pinataApiKey) && !empty($this->pinataSecretKey);
    }

    /**
     * Get IPFS gateway URL for a hash
     * 
     * @param string $ipfsHash
     * @return string
     */
    public function getGatewayUrl(string $ipfsHash): string
    {
        return $this->pinataGateway . '/' . $ipfsHash;
    }
}
