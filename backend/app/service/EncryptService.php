<?php
declare(strict_types=1);

namespace app\service;

/**
 * 加密服务
 */
class EncryptService
{
    protected string $key;
    protected string $cipher = 'AES-256-GCM';

    public function __construct()
    {
        $this->key = config('app.encryption_key', 'default_encryption_key_32chars');
    }

    /**
     * 加密数据
     */
    public function encrypt(string $data): string
    {
        $iv = random_bytes(12);
        $tag = '';
        $encrypted = openssl_encrypt($data, $this->cipher, $this->key, OPENSSL_RAW_DATA, $iv, $tag);

        return base64_encode($iv . $tag . $encrypted);
    }

    /**
     * 解密数据
     */
    public function decrypt(string $encryptedData): string
    {
        $data = base64_decode($encryptedData);
        $iv = substr($data, 0, 12);
        $tag = substr($data, 12, 16);
        $encrypted = substr($data, 28);

        $decrypted = openssl_decrypt($encrypted, $this->cipher, $this->key, OPENSSL_RAW_DATA, $iv, $tag);

        return $decrypted ?: '';
    }

    /**
     * 加密JSON数据
     */
    public function encryptJson(array $data): string
    {
        return $this->encrypt(json_encode($data, JSON_UNESCAPED_UNICODE));
    }

    /**
     * 解密JSON数据
     */
    public function decryptJson(string $encryptedData): array
    {
        $json = $this->decrypt($encryptedData);
        return json_decode($json, true) ?: [];
    }
}
