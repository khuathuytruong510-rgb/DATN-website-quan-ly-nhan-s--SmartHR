<?php

namespace App\Contracts;

interface DigitalSignatureProvider
{
    public function name(): string;

    /**
     * @param  array{signer_id:int, signer_role:string, contract_id:int, signed_at:string}  $meta
     */
    public function sign(string $documentHash, array $meta): string;

    public function verify(string $documentHash, string $signatureValue, array $meta): bool;
}
