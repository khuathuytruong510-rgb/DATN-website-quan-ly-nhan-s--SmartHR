<?php

namespace App\Services\ESign;

use App\Contracts\DigitalSignatureProvider;

/**
 * Mô phỏng chữ ký mật mã (HMAC-SHA256). Không dùng chứng thư số CA.
 * Có thể thay bằng driver nhà cung cấp eSign mà không đổi nghiệp vụ SmartHR.
 */
class MockDigitalSignatureProvider implements DigitalSignatureProvider
{
    public function name(): string
    {
        return 'mock';
    }

    public function sign(string $documentHash, array $meta): string
    {
        return hash_hmac('sha256', $this->payload($documentHash, $meta), (string) config('esign.mock_secret'));
    }

    public function verify(string $documentHash, string $signatureValue, array $meta): bool
    {
        if ($documentHash === '' || $signatureValue === '') {
            return false;
        }

        $expected = $this->sign($documentHash, $meta);

        return hash_equals($expected, $signatureValue);
    }

    protected function payload(string $documentHash, array $meta): string
    {
        return implode('|', [
            $documentHash,
            (string) ($meta['signer_id'] ?? ''),
            (string) ($meta['signer_role'] ?? ''),
            (string) ($meta['contract_id'] ?? ''),
        ]);
    }
}
