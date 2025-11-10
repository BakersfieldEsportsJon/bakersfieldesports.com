<?php
namespace Security;

class Encryption
{
    private $key;
    private $cipher;

    public function __construct(string $key, string $cipher = 'aes-256-cbc')
    {
        if (!in_array($cipher, openssl_get_cipher_methods())) {
            throw new \InvalidArgumentException('Unsupported cipher method');
        }

        // Ensure key is properly formatted for OpenSSL
        $this->key = hash('sha256', $key, true);
        $this->cipher = $cipher;
    }

    public function encrypt(string $data): string
    {
        $ivLength = openssl_cipher_iv_length($this->cipher);
        $iv = random_bytes($ivLength);
        
        $encrypted = openssl_encrypt(
            $data,
            $this->cipher,
            $this->key,
            OPENSSL_RAW_DATA,
            $iv
        );

        return base64_encode($iv . $encrypted);
    }

    public function decrypt(string $data): string
    {
        $data = base64_decode($data);
        $ivLength = openssl_cipher_iv_length($this->cipher);
        $iv = substr($data, 0, $ivLength);
        $encrypted = substr($data, $ivLength);

        return openssl_decrypt(
            $encrypted,
            $this->cipher,
            $this->key,
            OPENSSL_RAW_DATA,
            $iv
        );
    }

    public function generateKey(): string
    {
        return base64_encode(random_bytes(32));
    }
}
