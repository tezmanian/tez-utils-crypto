<?php

declare(strict_types=1);

namespace Tez\Utils\Crypto;

use Tez\Utils\Enum\BackedEnumTrait;

/**
 * Allowlist of supported AES-GCM cipher variants.
 *
 * Only ciphers defined here may be used with {@see AesEncryptionService}.
 * To add a new cipher, add a case and update the match expressions in
 * requiredHexKeyLength(), nonceLength(), and tagLength().
 *
 * BackedEnumTrait provides: is(), isOneOf(), values(), hasValue(),
 * find(), fromName(), tryFromName(), toArray(), toValueArray(), ordinal(), …
 */
enum AesCipher: string
{
    use BackedEnumTrait;

    /** AES-128-GCM — 32-char hex key (16 bytes / 128 bits). */
    case Aes128Gcm = 'aes-128-gcm';

    /** AES-192-GCM — 48-char hex key (24 bytes / 192 bits). */
    case Aes192Gcm = 'aes-192-gcm';

    /** AES-256-GCM — 64-char hex key (32 bytes / 256 bits). Recommended default. */
    case Aes256Gcm = 'aes-256-gcm';

    /**
     * Required key length in hex characters (2 hex chars = 1 byte).
     *
     * @return positive-int
     */
    public function requiredHexKeyLength(): int
    {
        return match ($this) {
            self::Aes128Gcm => 32,
            self::Aes192Gcm => 48,
            self::Aes256Gcm => 64,
        };
    }

    /**
     * Nonce (IV) length in bytes.
     *
     * 12 bytes (96 bits) is the NIST-recommended GCM nonce size and
     * the only length that avoids an extra GHASH step in OpenSSL.
     *
     * @return positive-int
     */
    public function nonceLength(): int
    {
        return match ($this) {
            self::Aes128Gcm, self::Aes192Gcm, self::Aes256Gcm => 12,
        };
    }

    /**
     * Authentication tag length in bytes.
     *
     * 16 bytes (128 bits) is the maximum GCM tag size and provides
     * the strongest forgery resistance.
     *
     * @return positive-int
     */
    public function tagLength(): int
    {
        return match ($this) {
            self::Aes128Gcm, self::Aes192Gcm, self::Aes256Gcm => 16,
        };
    }

    /**
     * Human-readable label used in exception messages and UIs.
     */
    public function label(): string
    {
        return match ($this) {
            self::Aes128Gcm => 'AES-128-GCM (16 bytes / 128 bits)',
            self::Aes192Gcm => 'AES-192-GCM (24 bytes / 192 bits)',
            self::Aes256Gcm => 'AES-256-GCM (32 bytes / 256 bits)',
        };
    }
}
