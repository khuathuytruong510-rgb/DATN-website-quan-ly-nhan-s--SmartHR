<?php

namespace App\Services;

class FaceRecognitionService
{
    /**
     * Perform a simple match between stored and incoming face embeddings.
     * This is a placeholder and should be replaced with a real recognition service.
     */
    public function matches(?string $storedEmbedding, ?string $incomingEmbedding, ?string $storedImage, ?string $incomingImage): bool
    {
        if ($storedEmbedding && $incomingEmbedding) {
            return hash_equals(trim($storedEmbedding), trim($incomingEmbedding));
        }

        if ($storedImage && $incomingImage) {
            return hash_equals($this->normalizeBase64($storedImage), $this->normalizeBase64($incomingImage));
        }

        return false;
    }

    public function normalizeBase64(string $base64): string
    {
        return preg_replace('/^data:image\/[a-z]+;base64,/', '', trim($base64));
    }
}
