<?php

declare(strict_types=1);

namespace Tezmanian\Utils\Crypto;

/**
 * Symmetric encryption service using authenticated AES-GCM.
 *
 * The cipher variant is selected via the {@see AesCipher} enum, which acts as
 * an explicit allowlist — only ciphers defined there may be used.
 * Defaults to AES-256-GCM when no cipher is specified.
 *
 * Encrypted format (base64-encoded): nonce[N] || ciphertext || tag[T]
 * where N = AesCipher::nonceLength() and T = AesCipher::tagLength().
 *
 * A fresh random nonce is generated for every encrypt() call, so identical
 * plaintexts produce different ciphertexts. The GCM tag verifies integrity
 * on decrypt() — tampered data is rejected with a RuntimeException.
 */
final class AesEncryptionService
{
    private readonly string $key;
    private readonly AesCipher $cipher;

    /**
     * @param string $hexKey A hex string whose length matches the cipher's key size.
     *                       Use {@see AesCipher::requiredHexKeyLength()} to determine
     *                       the expected length for a given cipher.
     * @param AesCipher $cipher Cipher variant to use. Defaults to {@see AesCipher::Aes256Gcm}.
     *
     * @throws \InvalidArgumentException when the key does not match the cipher's required length
     */
    public function __construct(string $hexKey, AesCipher $cipher = AesCipher::Aes256Gcm)
    {
        $requiredLength = $cipher->requiredHexKeyLength();

        if (!preg_match('/^[0-9a-fA-F]{' . $requiredLength . '}$/', $hexKey)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Encryption key for %s must be a %d-character hex string.',
                    $cipher->label(),
                    $requiredLength,
                ),
            );
        }

        $this->key    = (string) hex2bin($hexKey);
        $this->cipher = $cipher;
    }

    /**
     * Encrypt plaintext and return a base64-encoded ciphertext.
     *
     * @throws \RuntimeException on OpenSSL failure
     */
    public function encrypt(string $plaintext): string
    {
        $nonce = random_bytes($this->cipher->nonceLength());
        $tag   = '';

        $ciphertext = openssl_encrypt(
            $plaintext,
            $this->cipher->value,
            $this->key,
            OPENSSL_RAW_DATA,
            $nonce,
            $tag,
            '',
            $this->cipher->tagLength(),
        );

        if ($ciphertext === false) {
            throw new \RuntimeException(
                sprintf('Encryption failed for cipher %s.', $this->cipher->value),
            );
        }

        return base64_encode($nonce . $ciphertext . $tag);
    }

    /**
     * Decrypt a base64-encoded ciphertext produced by encrypt().
     *
     * @throws \RuntimeException when the input is invalid, too short, or the authentication tag fails
     */
    public function decrypt(string $encoded): string
    {
        $raw = base64_decode($encoded, strict: true);

        if ($raw === false) {
            throw new \RuntimeException('Decryption failed: invalid base64 input.');
        }

        $nonceLength = $this->cipher->nonceLength();
        $tagLength   = $this->cipher->tagLength();
        $minLength   = $nonceLength + $tagLength;

        if (strlen($raw) < $minLength) {
            throw new \RuntimeException('Decryption failed: ciphertext is too short.');
        }

        $nonce      = substr($raw, 0, $nonceLength);
        $tag        = substr($raw, -$tagLength);
        $ciphertext = substr($raw, $nonceLength, -$tagLength);

        $plaintext = openssl_decrypt(
            $ciphertext,
            $this->cipher->value,
            $this->key,
            OPENSSL_RAW_DATA,
            $nonce,
            $tag,
        );

        if ($plaintext === false) {
            throw new \RuntimeException(
                'Decryption failed: authentication tag mismatch (data may be tampered).',
            );
        }

        return $plaintext;
    }

    public function getCipher(): AesCipher
    {
        return $this->cipher;
    }
}
