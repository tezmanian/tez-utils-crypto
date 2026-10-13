# tez-utils-crypto

[![CI](https://github.com/tezmanian/tez-utils-crypto/actions/workflows/php.yml/badge.svg)](https://github.com/tezmanian/tez-utils-crypto/actions/workflows/php.yml)
[![Packagist Version](https://img.shields.io/packagist/v/tezmanian/utils-crypto)](https://packagist.org/packages/tezmanian/utils-crypto)
[![PHP](https://img.shields.io/packagist/php-v/tezmanian/utils-crypto)](https://packagist.org/packages/tezmanian/utils-crypto)
[![License: MIT](https://img.shields.io/packagist/l/tezmanian/utils-crypto)](LICENSE)

Authenticated AES-GCM encryption for PHP — supports AES-128/192/256-GCM with random nonces, tag verification, and a strict enum-based cipher allowlist. PHP 8.3+, requires `ext-openssl`.

## Installation

```bash
composer require tezmanian/utils-crypto
```

Package: [`tezmanian/utils-crypto`](https://packagist.org/packages/tezmanian/utils-crypto) · Namespace: `Tezmanian\Utils\Crypto`

## Components

| Class | Purpose |
|-------|---------|
| `AesEncryptionService` | Encrypts and decrypts strings using authenticated AES-GCM |
| `AesCipher` | Enum allowlist of supported cipher variants with their parameters |

## Encrypted format

```
base64( nonce[12 bytes] || ciphertext || tag[16 bytes] )
```

A fresh random nonce is generated on every `encrypt()` call — identical plaintexts produce different ciphertexts. The GCM authentication tag is verified on `decrypt()`, so tampered data is always rejected.

---

## Quick start

```php
use Tezmanian\Utils\Crypto\AesEncryptionService;
use Tezmanian\Utils\Crypto\AesCipher;

// Generate a 64-character hex key (32 bytes = AES-256)
$hexKey  = bin2hex(random_bytes(32));
$service = new AesEncryptionService($hexKey);

$encrypted = $service->encrypt('super-secret-value');
$plaintext = $service->decrypt($encrypted);
```

---

## AesEncryptionService

### Constructor

```php
new AesEncryptionService(string $hexKey, AesCipher $cipher = AesCipher::Aes256Gcm)
```

| Parameter | Description |
|-----------|-------------|
| `$hexKey` | Hex-encoded key — length must match the chosen cipher (see table below) |
| `$cipher` | Cipher variant — defaults to `AesCipher::Aes256Gcm` |

Throws `InvalidArgumentException` when the key length does not match the cipher.

### Methods

```php
$service->encrypt(string $plaintext): string   // returns base64-encoded ciphertext
$service->decrypt(string $encoded): string     // returns original plaintext
$service->getCipher(): AesCipher               // returns the active cipher variant
```

`decrypt()` throws `RuntimeException` on invalid base64, too-short input, or authentication tag mismatch.

---

## AesCipher

Enum acting as an explicit allowlist — only ciphers defined here may be used.

| Case | OpenSSL string | Key length | Hex key chars |
|------|---------------|------------|---------------|
| `AesCipher::Aes128Gcm` | `aes-128-gcm` | 16 bytes / 128 bit | 32 |
| `AesCipher::Aes192Gcm` | `aes-192-gcm` | 24 bytes / 192 bit | 48 |
| `AesCipher::Aes256Gcm` | `aes-256-gcm` | 32 bytes / 256 bit | **64** (recommended) |

All variants use a 12-byte nonce (NIST-recommended) and a 16-byte authentication tag.

```php
AesCipher::Aes256Gcm->requiredHexKeyLength(); // 64
AesCipher::Aes256Gcm->nonceLength();          // 12
AesCipher::Aes256Gcm->tagLength();            // 16
AesCipher::Aes256Gcm->label();                // 'AES-256-GCM (32 bytes / 256 bits)'

AesCipher::values();                          // ['aes-128-gcm', 'aes-192-gcm', 'aes-256-gcm']
AesCipher::hasValue('aes-256-gcm');           // true
AesCipher::find('aes-256-gcm');               // AesCipher::Aes256Gcm
```

---

## Selecting a cipher variant

```php
// AES-256-GCM (default, recommended)
$key256  = bin2hex(random_bytes(32)); // 64 hex chars
$service = new AesEncryptionService($key256);

// AES-192-GCM
$key192  = bin2hex(random_bytes(24)); // 48 hex chars
$service = new AesEncryptionService($key192, AesCipher::Aes192Gcm);

// AES-128-GCM
$key128  = bin2hex(random_bytes(16)); // 32 hex chars
$service = new AesEncryptionService($key128, AesCipher::Aes128Gcm);
```

---

## Error handling

```php
// Wrong key length → InvalidArgumentException at construction time
new AesEncryptionService('tooshort', AesCipher::Aes256Gcm);

// Tampered ciphertext → RuntimeException
$service->decrypt(base64_encode(str_repeat('x', 64)));

// Invalid base64 → RuntimeException
$service->decrypt('not-base64!!!');
```

---

## Requirements

- PHP 8.3+
- `ext-openssl`
- [`tezmanian/utils-enum`](https://packagist.org/packages/tezmanian/utils-enum) ^1.0

## Development

```bash
make build && make install
make ci   # audit, PHPStan, code style, tests
```

## License

MIT — see [LICENSE](LICENSE).

Author: René Halberstadt
