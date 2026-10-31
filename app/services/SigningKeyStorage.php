<?php
namespace App\Services;

final class SigningKeyStorage
{
    private static function directory(): string
    {
        return dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'private_keys';
    }

    private static function path(string $keyId): string
    {
        if (!preg_match('/\A[a-f0-9]{1,32}\z/i', $keyId)) {
            throw new \InvalidArgumentException('Invalid signing key ID.');
        }

        return self::directory() . DIRECTORY_SEPARATOR . strtolower($keyId) . '.pem';
    }

    public static function store(string $keyId, string $encryptedPrivateKeyPem): void
    {
        if ($encryptedPrivateKeyPem === '') {
            throw new \InvalidArgumentException('The encrypted private key is empty.');
        }

        $directory = self::directory();
        if (is_link($directory) || (!is_dir($directory) && !mkdir($directory, 0700, true) && !is_dir($directory))) {
            throw new \RuntimeException('Unable to create the private-key storage directory.');
        }

        $path = self::path($keyId);
        if (is_link($path)) {
            throw new \RuntimeException('The private-key file path is not safe.');
        }
        if (is_file($path)) {
            $existing = file_get_contents($path);
            if (is_string($existing) && hash_equals($existing, $encryptedPrivateKeyPem)) {
                return;
            }
            throw new \RuntimeException('A different private-key file already exists for this key.');
        }

        $temporaryPath = tempnam($directory, '.signing-key-');
        if ($temporaryPath === false) {
            throw new \RuntimeException('Unable to create a private-key file.');
        }
        @chmod($temporaryPath, 0600);
        $written = file_put_contents($temporaryPath, $encryptedPrivateKeyPem, LOCK_EX);
        if ($written !== strlen($encryptedPrivateKeyPem) || !rename($temporaryPath, $path)) {
            @unlink($temporaryPath);
            throw new \RuntimeException('Unable to save the private key to storage.');
        }
        @chmod($path, 0600);
    }

    public static function read(string $keyId): ?string
    {
        $path = self::path($keyId);
        if (!is_file($path) || is_link($path)) {
            return null;
        }

        $privateKeyPem = file_get_contents($path);
        return is_string($privateKeyPem) && $privateKeyPem !== '' ? $privateKeyPem : null;
    }

    public static function delete(string $keyId): bool
    {
        $path = self::path($keyId);
        return !file_exists($path) || (!is_link($path) && unlink($path));
    }
}