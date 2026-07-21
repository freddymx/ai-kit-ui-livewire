<?php

declare(strict_types=1);

namespace SolutionForest\AiKit\Ui\Tests\Support;

use SolutionForest\AiKit\Contracts\Embedder;

/** Deterministic offline embedder (hashing vectorizer) — mirrors core's test double. */
final class FakeEmbedder implements Embedder
{
    public const DIMENSIONS = 256;

    public function model(): string
    {
        return 'fake-hash-256';
    }

    public function embed(array $texts): array
    {
        return array_map(fn (string $t): array => $this->vectorize($t), array_values($texts));
    }

    public function embedOne(string $text): array
    {
        return $this->vectorize($text);
    }

    /** @return array<int, float> */
    private function vectorize(string $text): array
    {
        $vector = array_fill(0, self::DIMENSIONS, 0.0);
        $words = preg_split('/[^\p{L}\p{N}]+/u', mb_strtolower($text), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        foreach ($words as $word) {
            $vector[(int) (crc32($word) % self::DIMENSIONS)] += 1.0;
        }

        $magnitude = sqrt(array_sum(array_map(static fn (float $v): float => $v * $v, $vector)));

        return $magnitude > 0.0
            ? array_map(static fn (float $v): float => $v / $magnitude, $vector)
            : $vector;
    }
}
