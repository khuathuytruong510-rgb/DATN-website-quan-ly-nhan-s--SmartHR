<?php

namespace App\Services;

class FaceRecognitionService
{
    public const DESCRIPTOR_SIZE = 128;

    public function parse(?string $raw): ?array
    {
        if (! is_string($raw) || $raw === '') {
            return null;
        }

        $decoded = json_decode($raw, true);
        if (! is_array($decoded) || count($decoded) !== self::DESCRIPTOR_SIZE) {
            return null;
        }

        $values = array_map('floatval', array_values($decoded));

        foreach ($values as $value) {
            if (! is_finite($value)) {
                return null;
            }
        }

        return $values;
    }

    public function encode(array $embedding): string
    {
        return json_encode(array_values($this->normalize($embedding)), JSON_THROW_ON_ERROR);
    }

    public function normalize(array $embedding): array
    {
        $norm = 0.0;
        foreach ($embedding as $value) {
            $norm += $value * $value;
        }

        $norm = sqrt($norm);
        if ($norm <= 0) {
            return $embedding;
        }

        return array_map(fn (float $value) => $value / $norm, $embedding);
    }

    public function euclideanDistance(array $stored, array $incoming): float
    {
        $sum = 0.0;
        for ($i = 0; $i < self::DESCRIPTOR_SIZE; $i++) {
            $delta = $stored[$i] - $incoming[$i];
            $sum += $delta * $delta;
        }

        return sqrt($sum);
    }

    public function matchScore(?string $storedEmbedding, ?string $incomingEmbedding): ?float
    {
        $stored = $this->parse($storedEmbedding);
        $incoming = $this->parse($incomingEmbedding);

        if (! $stored || ! $incoming) {
            return null;
        }

        return $this->euclideanDistance($this->normalize($stored), $this->normalize($incoming));
    }

    public function matches(?string $storedEmbedding, ?string $incomingEmbedding): bool
    {
        $distance = $this->matchScore($storedEmbedding, $incomingEmbedding);

        if ($distance === null) {
            return false;
        }

        return $distance <= (float) config('attendance.face_match_distance', 0.55);
    }
}
