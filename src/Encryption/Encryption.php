<?php

namespace Rose\Encryption;

use Exception;
use Rose\Exceptions\Encryption\EncryptionFailureException;
use RuntimeException;

class Encryption
{
    private string $encryptionKey;
    private string $cipherAlgorithm;

    /**
     * The supported cipher algorithms and their properties.
     *
     * @var array
     */
    public static $supportedCiphers = [
        'aes-128-cbc' => ['size' => 16, 'aead' => false],
        'aes-256-cbc' => ['size' => 32, 'aead' => false],
        'aes-128-gcm' => ['size' => 16, 'aead' => true],
        'aes-256-gcm' => ['size' => 32, 'aead' => true],
    ];

    /**
     * @param string $encryptionKey   Secret key (binary string).
     * @param string $cipherAlgorithm Default: 'aes-256-gcm' or 'chacha20-poly1305'
     */
    public function __construct(
        string $encryptionKey,
        string $cipherAlgorithm = 'aes-256-gcm'
    ) {
        if (! static::supported($encryptionKey, $cipherAlgorithm)) {
            $ciphers = implode(', ', array_keys(self::$supportedCiphers));

            throw new RuntimeException("Unsupported cipher or incorrect key length. Supported ciphers are: {$ciphers}.");
        }

        $this->encryptionKey = $encryptionKey;
        $this->cipherAlgorithm = $cipherAlgorithm;
    }

    /**
     * Determine if the given key and cipher combination is valid.
     *
     * @param  string $key
     * @param  string $cipher
     * @return bool
     */
    public static function supported($key, $cipher)
    {
        if (! isset(self::$supportedCiphers[strtolower($cipher)])) {
            return false;
        }


        return mb_strlen($key, '8bit') === self::$supportedCiphers[strtolower($cipher)]['size'];
    }

    /**
     * Encrypts data with IV and authentication tag.
     */
    public function encrypt(string $plaintext): string
    {
        try {
            $iv = random_bytes(openssl_cipher_iv_length($this->cipherAlgorithm));
            $tag = '';

            $ciphertext = openssl_encrypt(
                $plaintext,
                $this->cipherAlgorithm,
                $this->encryptionKey,
                OPENSSL_RAW_DATA,
                $iv,
                $tag
            );

            if ($ciphertext === false) {
                throw new EncryptionFailureException('Encryption failed: ' . openssl_error_string());
            }

            // Pack IV + tag + ciphertext into a single string
            return base64_encode($iv . $tag . $ciphertext);
        } catch (Exception $e) {
            throw new EncryptionFailureException("Encryption error: {$e->getMessage()}");
        }
    }

    /**
     * Decrypts data and validates authentication tag.
     */
    public function decrypt(string $encryptedData): string
    {
        try {
            $data = base64_decode($encryptedData);
            $ivLength = openssl_cipher_iv_length($this->cipherAlgorithm);
            $iv = substr($data, 0, $ivLength);
            $tag = substr($data, $ivLength, 16); // GCM tag is 16 bytes
            $ciphertext = substr($data, $ivLength + 16);

            $plaintext = openssl_decrypt(
                $ciphertext,
                $this->cipherAlgorithm,
                $this->encryptionKey,
                OPENSSL_RAW_DATA,
                $iv,
                $tag
            );

            if ($plaintext === false) {
                throw new EncryptionFailureException('Decryption failed: ' . openssl_error_string());
            }

            return $plaintext;
        } catch (Exception $e) {
            throw new EncryptionFailureException("Decryption error: {$e->getMessage()}");
        }
    }

    /**
     * Generates a secure encryption key (store this securely!).
     */
    public static function generateKey(int $length = 32): string
    {
        return random_bytes($length); // 32 bytes = 256 bits for AES-256
    }
}
