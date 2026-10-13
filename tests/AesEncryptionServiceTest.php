<?php

declare(strict_types=1);

namespace Tezmanian\Utils\Tests\Crypto;

use PHPUnit\Framework\TestCase;
use Tezmanian\Utils\Crypto\AesCipher;
use Tezmanian\Utils\Crypto\AesEncryptionService;

final class AesEncryptionServiceTest extends TestCase
{
    /** 64 hex chars — valid AES-256 key */
    private const KEY_256 = 'a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4e5f6a1b2';

    /** 48 hex chars — valid AES-192 key */
    private const KEY_192 = 'a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4e5f6';

    /** 32 hex chars — valid AES-128 key */
    private const KEY_128 = 'a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4';

    private AesEncryptionService $service256;
    private AesEncryptionService $service192;
    private AesEncryptionService $service128;

    protected function setUp(): void
    {
        $this->service256 = new AesEncryptionService(self::KEY_256);
        $this->service192 = new AesEncryptionService(self::KEY_192, AesCipher::Aes192Gcm);
        $this->service128 = new AesEncryptionService(self::KEY_128, AesCipher::Aes128Gcm);
    }

    // -------------------------------------------------------------------------
    // AesCipher enum — BackedEnumTrait helpers
    // -------------------------------------------------------------------------

    public function testValuesReturnsAllOpenSslCipherStrings(): void
    {
        $this->assertSame(
            ['aes-128-gcm', 'aes-192-gcm', 'aes-256-gcm'],
            AesCipher::values(),
        );
    }

    public function testHasValueReturnsTrueForKnownCipher(): void
    {
        $this->assertTrue(AesCipher::hasValue('aes-256-gcm'));
        $this->assertTrue(AesCipher::hasValue('aes-128-gcm'));
    }

    public function testHasValueReturnsFalseForUnknownCipher(): void
    {
        $this->assertFalse(AesCipher::hasValue('aes-512-gcm'));
        $this->assertFalse(AesCipher::hasValue('des-ede3-cbc'));
    }

    public function testFindByCipherString(): void
    {
        $this->assertSame(AesCipher::Aes256Gcm, AesCipher::find('aes-256-gcm'));
        $this->assertNull(AesCipher::find('unknown'));
    }

    public function testFromNameReturnsCase(): void
    {
        $this->assertSame(AesCipher::Aes256Gcm, AesCipher::fromName('Aes256Gcm'));
    }

    public function testIsOneOf(): void
    {
        $this->assertTrue(AesCipher::Aes256Gcm->isOneOf([AesCipher::Aes128Gcm, AesCipher::Aes256Gcm]));
        $this->assertFalse(AesCipher::Aes192Gcm->isOneOf([AesCipher::Aes128Gcm, AesCipher::Aes256Gcm]));
    }

    // -------------------------------------------------------------------------
    // AesCipher — crypto parameters
    // -------------------------------------------------------------------------

    public function testNonceLengthIs12ForAllVariants(): void
    {
        foreach (AesCipher::cases() as $cipher) {
            $this->assertSame(12, $cipher->nonceLength(), $cipher->label());
        }
    }

    public function testTagLengthIs16ForAllVariants(): void
    {
        foreach (AesCipher::cases() as $cipher) {
            $this->assertSame(16, $cipher->tagLength(), $cipher->label());
        }
    }

    public function testRequiredHexKeyLengths(): void
    {
        $this->assertSame(32, AesCipher::Aes128Gcm->requiredHexKeyLength());
        $this->assertSame(48, AesCipher::Aes192Gcm->requiredHexKeyLength());
        $this->assertSame(64, AesCipher::Aes256Gcm->requiredHexKeyLength());
    }

    // -------------------------------------------------------------------------
    // Default cipher (AES-256-GCM)
    // -------------------------------------------------------------------------

    public function testDefaultCipherIsAes256Gcm(): void
    {
        $this->assertSame(AesCipher::Aes256Gcm, $this->service256->getCipher());
    }

    public function testEncryptAndDecryptRoundTripAes256(): void
    {
        $plaintext = 'super-secret-client-secret-value';
        $this->assertSame($plaintext, $this->service256->decrypt($this->service256->encrypt($plaintext)));
    }

    public function testEncryptProducesDifferentOutputEachTime(): void
    {
        $this->assertNotSame(
            $this->service256->encrypt('same-value'),
            $this->service256->encrypt('same-value'),
        );
    }

    public function testEncryptedOutputIsBase64(): void
    {
        $this->assertNotFalse(base64_decode($this->service256->encrypt('test'), true));
    }

    public function testEncryptEmptyString(): void
    {
        $this->assertSame('', $this->service256->decrypt($this->service256->encrypt('')));
    }

    public function testEncryptLargePayload(): void
    {
        $payload = str_repeat('a', 10_000);
        $this->assertSame($payload, $this->service256->decrypt($this->service256->encrypt($payload)));
    }

    // -------------------------------------------------------------------------
    // AES-192-GCM
    // -------------------------------------------------------------------------

    public function testCipherIsAes192GcmWhenConfigured(): void
    {
        $this->assertSame(AesCipher::Aes192Gcm, $this->service192->getCipher());
    }

    public function testEncryptAndDecryptRoundTripAes192(): void
    {
        $plaintext = 'secret-192';
        $this->assertSame($plaintext, $this->service192->decrypt($this->service192->encrypt($plaintext)));
    }

    // -------------------------------------------------------------------------
    // AES-128-GCM
    // -------------------------------------------------------------------------

    public function testCipherIsAes128GcmWhenConfigured(): void
    {
        $this->assertSame(AesCipher::Aes128Gcm, $this->service128->getCipher());
    }

    public function testEncryptAndDecryptRoundTripAes128(): void
    {
        $plaintext = 'another-secret';
        $this->assertSame($plaintext, $this->service128->decrypt($this->service128->encrypt($plaintext)));
    }

    // -------------------------------------------------------------------------
    // Key-length validation per cipher
    // -------------------------------------------------------------------------

    public function testConstructorThrowsWhenKeyTooShortForAes256(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new AesEncryptionService('tooshort');
    }

    public function testConstructorThrowsWhenNonHexKeyGiven(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new AesEncryptionService(str_repeat('zz', 32)); // 64 chars but not hex
    }

    public function testConstructorThrowsWhen256BitKeyUsedForAes128(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new AesEncryptionService(self::KEY_256, AesCipher::Aes128Gcm);
    }

    public function testConstructorThrowsWhen128BitKeyUsedForAes256(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new AesEncryptionService(self::KEY_128, AesCipher::Aes256Gcm);
    }

    public function testConstructorThrowsWhen256BitKeyUsedForAes192(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new AesEncryptionService(self::KEY_256, AesCipher::Aes192Gcm);
    }

    // -------------------------------------------------------------------------
    // Tamper / error cases
    // -------------------------------------------------------------------------

    public function testDecryptThrowsOnTamperedData(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->service256->decrypt(base64_encode(str_repeat('x', 64)));
    }

    public function testDecryptThrowsOnInvalidBase64(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->service256->decrypt('not-base64!!!');
    }

    public function testDecryptThrowsOnTooShortInput(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->service256->decrypt(base64_encode(str_repeat('x', 10)));
    }
}
