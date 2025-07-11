<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class VectorSearchService
{
    /**
     * Store a chunk and its embedding in the database.
     */
    public function storeChunk(string $source, string $chunk, array $embedding)
    {
        DB::table('rag_chunks')->insert([
            'source' => $source,
            'chunk' => $chunk,
            'embedding' => json_encode($embedding),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Find the top N most similar chunks to the query embedding.
     * If $selectedFiles is provided, only search within those specific files.
     */
    public function searchSimilar(array $queryEmbedding, int $limit = 3, array $selectedFiles = []): array
    {
        $query = DB::table('rag_chunks');
        
        // If specific files are selected, filter by them
        if (!empty($selectedFiles)) {
            $query->whereIn('source', $selectedFiles);
        }
        
        $chunks = $query->get();
        $results = [];
        foreach ($chunks as $row) {
            $embedding = json_decode($row->embedding, true);
            $sim = $this->cosineSimilarity($queryEmbedding, $embedding);
            $results[] = [
                'chunk' => $row->chunk,
                'source' => $row->source,
                'similarity' => $sim,
            ];
        }
        usort($results, function($a, $b) {
            return $b['similarity'] <=> $a['similarity'];
        });
        return array_slice($results, 0, $limit);
    }

    /**
     * Compute cosine similarity between two vectors.
     */
    private function cosineSimilarity(array $a, array $b): float
    {
        $dot = 0.0;
        $normA = 0.0;
        $normB = 0.0;
        $len = min(count($a), count($b));
        for ($i = 0; $i < $len; $i++) {
            $dot += $a[$i] * $b[$i];
            $normA += $a[$i] * $a[$i];
            $normB += $b[$i] * $b[$i];
        }
        if ($normA == 0 || $normB == 0) return 0.0;
        return $dot / (sqrt($normA) * sqrt($normB));
    }
}
