<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class Chunker
{
    /**
     * Split text into chunks of a given size (in characters), with optional overlap.
     * Now processes text in smaller pieces (by paragraph or line) to avoid memory exhaustion.
     */
    public function chunkText(string $text, int $chunkSize = 1000, int $overlap = 200): array
    {
        // Log the input size for debugging
        Log::info('Chunker: input text length = ' . mb_strlen($text));

        // Cap the input size to 200,000 characters (about 200KB)
        if (mb_strlen($text) > 200000) {
            $text = mb_substr($text, 0, 200000);
            Log::warning('Chunker: input text truncated to 200,000 characters');
        }

        // Split by paragraph or line to avoid loading everything at once
        $pieces = preg_split('/\r?\n|\r|(?<=\.) /u', $text);
        $chunks = [];
        $current = '';
        foreach ($pieces as $piece) {
            if (mb_strlen($current) + mb_strlen($piece) + 1 > $chunkSize) {
                if ($current !== '') {
                    $chunks[] = trim($current);
                }
                $current = $piece;
            } else {
                $current .= ($current === '' ? '' : ' ') . $piece;
            }
        }
        if ($current !== '') {
            $chunks[] = trim($current);
        }

        // Add overlap if needed
        if ($overlap > 0 && count($chunks) > 1) {
            $withOverlap = [];
            for ($i = 0; $i < count($chunks); $i++) {
                $chunk = $chunks[$i];
                if ($i > 0) {
                    $prev = $chunks[$i - 1];
                    $chunk = mb_substr($prev, -$overlap) . ' ' . $chunk;
                }
                $withOverlap[] = trim($chunk);
            }
            $chunks = $withOverlap;
        }

        return array_filter($chunks);
    }
    
    /**
     * Clean and structure text for better LLM processing
     */
    private function cleanAndStructureText(string $text): string
    {
        // Limit text size to prevent memory issues
        if (mb_strlen($text) > 500000) { // 500KB limit
            $text = mb_substr($text, 0, 500000);
        }
        
        // Basic cleaning only to avoid memory issues
        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim($text);
        
        // Simple fixes that don't use much memory
        $text = $this->simpleTextFixes($text);
        
        return $text;
    }
    
    /**
     * Simple text fixes that don't use much memory
     */
    private function simpleTextFixes(string $text): string
    {
        // Basic spacing fixes
        $text = preg_replace('/([.!?])([A-Z])/', '$1 $2', $text);
        $text = preg_replace('/(\d+)([A-Za-z])/', '$1 $2', $text);
        $text = preg_replace('/([A-Za-z])(\d+)/', '$1 $2', $text);
        
        // Simple structural breaks
        $text = str_replace('National Economic and Development Authority', "\n\nNational Economic and Development Authority", $text);
        $text = str_replace('Completed', "\n• Completed", $text);
        $text = str_replace('Ongoing', "\n• Ongoing", $text);
        $text = str_replace('In the Pipeline', "\n• In the Pipeline", $text);
        
        return $text;
    }
    
    /**
     * Try to break chunks at sentence boundaries
     */
    private function breakAtSentenceBoundary(string $chunk): string
    {
        // Look for sentence endings in the last 100 characters
        $lastPart = mb_substr($chunk, -100);
        $sentenceEnd = mb_strrpos($lastPart, '.');
        
        if ($sentenceEnd !== false) {
            $breakPoint = mb_strlen($chunk) - 100 + $sentenceEnd + 1;
            return mb_substr($chunk, 0, $breakPoint);
        }
        
        // If no sentence ending, look for other natural breaks
        $breakPoint = mb_strrpos($chunk, "\n");
        if ($breakPoint !== false && $breakPoint > mb_strlen($chunk) * 0.7) {
            return mb_substr($chunk, 0, $breakPoint);
        }
        
        return $chunk;
    }
}
