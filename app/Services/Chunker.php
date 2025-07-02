<?php

namespace App\Services;

class Chunker
{
    /**
     * Split text into chunks of a given size (in characters), with optional overlap.
     */
    public function chunkText(string $text, int $chunkSize = 1000, int $overlap = 200): array
    {
        $chunks = [];
        $text = preg_replace('/\s+/', ' ', $text); // Clean up whitespace
        $len = mb_strlen($text);
        $start = 0;
        while ($start < $len) {
            $chunk = mb_substr($text, $start, $chunkSize);
            $chunks[] = $chunk;
            $start += ($chunkSize - $overlap);
        }
        return $chunks;
    }
}
