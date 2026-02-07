<?php

namespace App\Services;

use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;

class DocumentHashService
{
    /**
     * Generate SHA-256 hash of a file
     *
     * @param string|UploadedFile $file File path or UploadedFile instance
     * @return string Hash in hex format
     */
    public static function hashFile($file): string
    {
        try {
            if ($file instanceof UploadedFile) {
                $filePath = $file->getRealPath();
            } else {
                $filePath = $file;
            }

            if (!file_exists($filePath)) {
                throw new Exception("File not found: {$filePath}");
            }

            $hash = hash_file('sha256', $filePath);

            if ($hash === false) {
                throw new Exception("Failed to generate hash for file");
            }

            return $hash;

        } catch (Exception $e) {
            Log::error('Failed to hash file', [
                'error' => $e->getMessage(),
                'file' => $file instanceof UploadedFile ? $file->getClientOriginalName() : $file,
            ]);
            throw $e;
        }
    }

    /**
     * Generate SHA-256 hash of string content
     *
     * @param string $content Content to hash
     * @return string Hash in hex format
     */
    public static function hashString(string $content): string
    {
        return hash('sha256', $content);
    }

    /**
     * Verify if a file matches a given hash
     *
     * @param string|UploadedFile $file File to verify
     * @param string $expectedHash Expected hash
     * @return bool True if matches
     */
    public static function verifyFileHash($file, string $expectedHash): bool
    {
        try {
            $actualHash = self::hashFile($file);
            $cleanExpectedHash = str_replace('0x', '', $expectedHash);
            
            return hash_equals(strtolower($actualHash), strtolower($cleanExpectedHash));

        } catch (Exception $e) {
            Log::error('Failed to verify file hash', [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Add 0x prefix to hash if not present
     *
     * @param string $hash Hash string
     * @return string Hash with 0x prefix
     */
    public static function addHexPrefix(string $hash): string
    {
        return str_starts_with($hash, '0x') ? $hash : '0x' . $hash;
    }

    /**
     * Remove 0x prefix from hash if present
     *
     * @param string $hash Hash string
     * @return string Hash without 0x prefix
     */
    public static function removeHexPrefix(string $hash): string
    {
        return str_starts_with($hash, '0x') ? substr($hash, 2) : $hash;
    }
}
