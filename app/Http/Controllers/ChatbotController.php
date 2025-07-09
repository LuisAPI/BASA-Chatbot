<?php

namespace App\Http\Controllers;

use App\Services\Chunker;
use App\Services\EmbeddingService;
use App\Services\VectorSearchService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Jobs\ProcessFileForRAG;

class ChatbotController extends Controller
{
    public function index()
    {
        return view('chatbot');
    }

    public function ask(Request $request)
    {
        $userMessage = $request->input('message');

        if ($request->has('phpinfo')) {
            ob_start();
            phpinfo();
            $phpinfo = ob_get_clean();
            return response($phpinfo);
        }

        if (empty($userMessage)) {
            return response()->json(['error' => 'Message cannot be empty.'], 400);
        }
        if (strlen($userMessage) > 500) {
            return response()->json(['error' => 'Message is too long. Maximum length is 500 characters.'], 400);
        }

        // Check if the user message contains a URL (with or without a question)
        $url = null;
        $question = null;
        if (preg_match('/(https?:\/\/[^\s]+)/i', $userMessage, $matches)) {
            $url = $matches[1];
            $question = trim(str_replace($url, '', $userMessage));
            if (empty($question)) {
                $question = 'Summarize the following webpage.';
            }
            $webResult = $this->fetchAndParseWebpage($url);
            if (isset($webResult['error'])) {
                return response()->json(['reply' => $webResult['error']]);
            }
            $userMessage = $question . " Title: {$webResult['title']}. Content: {$webResult['content']}";
        }

        // Implement RAG: Search for relevant file content
        $relevantContent = $this->searchRelevantFileContent($userMessage);
        
        $botReply = $this->sendToLLM($userMessage, null, $relevantContent);
        
        return response()->json([
            'reply' => $botReply,
            'rag_used' => !empty($relevantContent),
            'rag_chunks_found' => count($relevantContent),
            'rag_files' => !empty($relevantContent) ? array_unique(array_column($relevantContent, 'source')) : []
        ]);
    }

    public function upload(Request $request)
    {
        Log::info('TEST LOG: upload() called');
        if (!$request->hasFile('file')) {
            return response()->json(['reply' => 'No file uploaded.'], 400);
        }
        $file = $request->file('file');
        $fileName = $file->getClientOriginalName();
        $storagePath = $file->storeAs('uploads', uniqid() . '_' . $fileName, 'local');
        if (!$storagePath) {
            return response()->json(['reply' => 'Failed to save uploaded file.'], 500);
        }
        // Dispatch background job for parsing, chunking, embedding
        ProcessFileForRAG::dispatch($storagePath, $fileName);
        return response()->json(['reply' => 'File is being processed. You will be notified when it is ready.', 'file' => $fileName]);
    }

    /**
     * Get the system prompt for the LLM.
     */
    private function getSystemPrompt(): string
    {
        return <<<EOT
You are a helpful assistant for employees of the Department of Economy, Planning and Development (DEPDev), an executive department of the national government of the Republic of the Philippines.

The DEPDev, formerly known as the National Economic and Development Authority (NEDA), is the country’s premier socioeconomic planning body, highly regarded as the authority in macroeconomic forecasting and policy analysis and research. It provides high-level advice to policymakers in Congress and the Executive Branch.
        
DEPDev's key responsibilities include:
a. Coordination of activities such as the formulation of policies, plans, and programs to efficiently set the broad parameters for national and sub-national (area-wide, regional, and local development);
b. Review, evaluation, and monitoring of infrastructure projects identified under the Comprehensive and Integrated Infrastructure Program consistent with the government’s thrust of increasing investment spending for the growing demand on quality infrastructure facilities; and
c. Undertaking of short-term policy reviews to provide critical analyses of development issues and policy alternatives to decision-makers.

Vision:
DEPDev envisions a country where public and private sectors perform their respective roles efficiently, such that people have equal access to opportunities, resulting in inclusive development and zero poverty.

Mission:
DEPDev’s mission is to formulate continuing, coordinated, and fully-integrated socioeconomic policies, plans, and programs to enable and empower every Filipino to enjoy a matatag, maginhawa, at panatag na buhay.

Core Values
DEPDev’s core values are Integrity, Professionalism, and Excellence.

Key Officials
The key officials of DEPDev include the Secretary, Undersecretaries, Assistant Secretaries, and other senior officials who lead various divisions and programs within the agency. They are responsible for overseeing the implementation of policies and programs that align with the agency's mission and vision.

The Secretary of the Department of Economy, Planning and Development (DEPDev) is the head of the agency, responsible for providing overall leadership and direction. The Undersecretaries and Assistant Secretaries assist the Secretary in managing the agency's various divisions and programs.

From its founding in 2025 to the end of the inaugural term in 2028, the Secretary is the Honorable Arsenio M. Balisacan, formerly the Director-General of the National Economic and Development Authority (NEDA) and a member of the Cabinet of the Philippines.

Your role is to assist users by providing accurate and relevant information about the agency's functions, services, and government structure. You should:
- Provide clear and concise answers to user inquiries.
- Offer information about the agency's programs, services, and initiatives.
- Explain the agency's role within the national government.
- Clarify the agency's functions and responsibilities.
- Guide users on how to access services or information.
- Answer questions about the agency's structure, including its divisions and key personnel.
- Provide information about the agency's policies and procedures.

You should not:
- Provide personal opinions or unverified information.
- Engage in discussions unrelated to the agency's functions or government structure.
- Offer legal or financial advice.
- Discuss sensitive or confidential information.

Always answer user questions about the agency's functions and government structure in well-written, properly capitalized, and clearly formatted paragraphs. Use professional language and correct grammar.
EOT;
    }

    /**
     * Send a prompt to the LLM and return the response.
     * If streaming is enabled, handle streamed responses.
     */
    private function sendToLLM(string $prompt, ?string $systemPrompt = null, ?array $relevantContent = null): string
    {
        $systemPrompt = $systemPrompt ?? $this->getSystemPrompt();
        
        // If we have relevant content from uploaded files, prioritize it
        if ($relevantContent && !empty($relevantContent)) {
            $fileContext = $this->buildFileContext($relevantContent);
            $systemPrompt = $fileContext . "\n\n" . $systemPrompt;
        }
        
        $model = env('LLM_MODEL', 'tinyllama');
        $stream = filter_var(env('LLM_STREAM', false), FILTER_VALIDATE_BOOLEAN);
        if (!$stream) {
            $response = \Illuminate\Support\Facades\Http::timeout(60)->post('http://127.0.0.1:11434/api/generate', [
                'model' => $model,
                'system' => $systemPrompt,
                'prompt' => $prompt,
                'stream' => false
            ]);
            $data = $response->json();
            $botReply = $data['response'] ?? 'No response from model.';
        } else {
            // Streaming mode: collect tokens as they arrive
            $botReply = '';
            $client = new \GuzzleHttp\Client(['timeout' => 65]);
            $res = $client->post('http://127.0.0.1:11434/api/generate', [
                'json' => [
                    'model' => $model,
                    'system' => $systemPrompt,
                    'prompt' => $prompt,
                    'stream' => true
                ],
                'stream' => true
            ]);
            $body = $res->getBody();
            while (!$body->eof()) {
                $line = trim($body->read(4096));
                if ($line) {
                    // Each line is a JSON object with a 'response' key
                    $json = json_decode($line, true);
                    if (isset($json['response'])) {
                        $botReply .= $json['response'];
                    }
                }
            }
        }
        $botReply = preg_replace('/^\s*Assistant:\s*/i', '', $botReply);
        $botReply = preg_replace_callback('/([.!?]\s+|^)([a-z])/', function ($matches) {
            return $matches[1] . strtoupper($matches[2]);
        }, $botReply);
        $botReply = preg_replace("/(\r?\n){2,}/", "\n\n", $botReply);
        return trim($botReply);
    }

    /**
     * Stream LLM response to the frontend as tokens arrive.
     */
    public function streamLLM(Request $request)
    {
        $prompt = $request->input('message');
        $systemPrompt = $this->getSystemPrompt();
        $model = env('LLM_MODEL', 'tinyllama');
        
        // Implement RAG: Search for relevant file content
        $relevantContent = $this->searchRelevantFileContent($prompt);
        
        // If we have relevant content from uploaded files, prioritize it
        if ($relevantContent && !empty($relevantContent)) {
            $fileContext = $this->buildFileContext($relevantContent);
            $systemPrompt = $fileContext . "\n\n" . $systemPrompt;
        }
        
        try {
            return response()->stream(function () use ($prompt, $systemPrompt, $model) {
                $client = new \GuzzleHttp\Client(['timeout' => 65]);
                $res = $client->post('http://127.0.0.1:11434/api/generate', [
                    'json' => [
                        'model' => $model,
                        'system' => $systemPrompt,
                        'prompt' => $prompt,
                        'stream' => true
                    ],
                    'stream' => true
                ]);
                $body = $res->getBody();
                $buffer = '';
                while (!$body->eof()) {
                    $buffer .= $body->read(4096);
                    while (($pos = strpos($buffer, "\n")) !== false) {
                        $line = trim(substr($buffer, 0, $pos));
                        $buffer = substr($buffer, $pos + 1);
                        if ($line) {
                            $json = json_decode($line, true);
                            if (isset($json['response'])) {
                                echo $json['response'];
                                ob_flush();
                                flush();
                            }
                        }
                    }
                }
            }, 200, [
                'Content-Type' => 'text/event-stream',
                'Cache-Control' => 'no-cache',
                'X-Accel-Buffering' => 'no'
            ]);
        } catch (\Exception $e) {
            // If streaming fails, fall back to non-streaming mode
            \Illuminate\Support\Facades\Log::error('Streaming failed, falling back to non-streaming: ' . $e->getMessage());
            $response = $this->sendToLLM($prompt, $systemPrompt);
            return response()->json(['reply' => $response]);
        }
    }

    /**
     * Endpoint to let frontend know if streaming is enabled.
     */
    public function streamingEnabled()
    {
        return response()->json(['streaming' => filter_var(env('LLM_STREAM', false), FILTER_VALIDATE_BOOLEAN)]);
    }

    public function processingStatus(Request $request)
    {
        $files = $request->input('files', []);
        $statuses = [];
        
        \Illuminate\Support\Facades\Log::info('Processing status check for files:', $files);
        
        foreach ($files as $fileName) {
            // Check if the file has been processed by looking at the rag_chunks table
            $chunkCount = \Illuminate\Support\Facades\DB::table('rag_chunks')
                ->where('source', $fileName)
                ->count();
                
            \Illuminate\Support\Facades\Log::info("File {$fileName}: {$chunkCount} chunks found");
                
            if ($chunkCount > 0) {
                // File has been processed successfully
                $statuses[] = [
                    'fileName' => $fileName,
                    'status' => 'completed',
                    'timestamp' => now()
                ];
            } else {
                // Check if there's a failed job for this file
                $failedJob = \Illuminate\Support\Facades\DB::table('jobs')
                    ->where('payload', 'like', '%"' . $fileName . '"%')
                    ->where('failed_at', 'is not', null)
                    ->first();
                    
                if ($failedJob) {
                    $statuses[] = [
                        'fileName' => $fileName,
                        'status' => 'failed',
                        'error' => 'Job processing failed',
                        'timestamp' => $failedJob->failed_at
                    ];
                } else {
                    // Check if there's a pending job for this file
                    $pendingJob = \Illuminate\Support\Facades\DB::table('jobs')
                        ->where('payload', 'like', '%"' . $fileName . '"%')
                        ->where('failed_at', null)
                        ->first();
                        
                    if ($pendingJob) {
                        $statuses[] = [
                            'fileName' => $fileName,
                            'status' => 'processing',
                            'timestamp' => $pendingJob->created_at
                        ];
                    }
                }
            }
        }
        
        \Illuminate\Support\Facades\Log::info('Returning statuses:', $statuses);
        return response()->json($statuses);
    }

    /**
     * Get information about available files for RAG.
     */
    public function getRagInfo()
    {
        $totalChunks = \Illuminate\Support\Facades\DB::table('rag_chunks')->count();
        $files = \Illuminate\Support\Facades\DB::table('rag_chunks')
            ->select('source')
            ->distinct()
            ->pluck('source')
            ->toArray();
            
        return response()->json([
            'total_chunks' => $totalChunks,
            'available_files' => $files,
            'rag_enabled' => $totalChunks > 0
        ]);
    }

    /**
     * Show the file gallery page.
     */
    public function fileGallery()
    {
        $files = \Illuminate\Support\Facades\DB::table('rag_chunks')
            ->select('source', \Illuminate\Support\Facades\DB::raw('COUNT(*) as chunk_count'))
            ->groupBy('source')
            ->orderBy('source')
            ->get();

        $totalChunks = \Illuminate\Support\Facades\DB::table('rag_chunks')->count();
        
        return view('file-gallery', compact('files', 'totalChunks'));
    }

    /**
     * Get detailed information about a specific file's chunks.
     */
    public function getFileChunks(Request $request)
    {
        $fileName = $request->input('file');
        
        $chunks = \Illuminate\Support\Facades\DB::table('rag_chunks')
            ->where('source', $fileName)
            ->select('id', 'chunk', 'created_at')
            ->orderBy('id')
            ->get();
            
        return response()->json([
            'file' => $fileName,
            'chunks' => $chunks,
            'total_chunks' => $chunks->count()
        ]);
    }

    /**
     * Debug endpoint to test RAG search functionality.
     */
    public function debugRagSearch(Request $request)
    {
        $query = $request->input('query', '');
        
        if (empty($query)) {
            return response()->json(['error' => 'Query parameter is required'], 400);
        }
        
        try {
            $relevantContent = $this->searchRelevantFileContent($query);
            $fileContext = $this->buildFileContext($relevantContent);
            
            return response()->json([
                'query' => $query,
                'relevant_chunks' => $relevantContent,
                'file_context' => $fileContext,
                'context_length' => strlen($fileContext),
                'chunks_found' => count($relevantContent)
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    }

    /**
     * Fetch and parse a webpage, respecting robots.txt and returning extracted content.
     */
    private function fetchAndParseWebpage(string $url): ?array
    {
        $parsedUrl = parse_url($url);
        $robotsUrl = $parsedUrl['scheme'] . '://' . $parsedUrl['host'] . '/robots.txt';
        try {
            $robotsResponse = \Illuminate\Support\Facades\Http::timeout(5)->get($robotsUrl);
            $robotsTxt = $robotsResponse->successful() ? $robotsResponse->body() : '';
            $isAllowed = true;
            if ($robotsTxt) {
                $lines = preg_split('/\r?\n/', $robotsTxt);
                $userAgent = false;
                foreach ($lines as $line) {
                    $line = trim($line);
                    if (stripos($line, 'User-agent:') === 0) {
                        $userAgent = (stripos($line, 'User-agent: *') === 0);
                    } elseif ($userAgent && stripos($line, 'Disallow:') === 0) {
                        $disallowedPath = trim(substr($line, 9));
                        if ($disallowedPath && strpos($parsedUrl['path'] ?? '/', $disallowedPath) === 0) {
                            $isAllowed = false;
                            break;
                        }
                    } elseif (stripos($line, 'User-agent:') === 0) {
                        $userAgent = false;
                    }
                }
            }
            if (!$isAllowed) {
                return ['error' => 'Sorry, I am not allowed to access this page due to the site\'s robots.txt rules.'];
            }
        } catch (\Exception $e) {
            // If robots.txt fails, proceed (fail open)
        }
        try {
            $webResponse = \Illuminate\Support\Facades\Http::timeout(10)
                ->withHeaders(['User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36'])
                ->get($url);
            $html = $webResponse->body();
            $readability = new \fivefilters\Readability\Readability(new \fivefilters\Readability\Configuration());
            $readability->parse($html);
            $content = $readability->getContent();
            $title = $readability->getTitle();
            $content = strip_tags($content);
            if (strlen($content) > 4000) {
                $content = substr($content, 0, 1000) . '... [truncated]';
            }
            return [
                'title' => $title,
                'content' => $content
            ];
        } catch (\Exception $e) {
            return ['error' => 'Sorry, I could not fetch or process the webpage.'];
        }
    }

    /**
     * Search for relevant content from uploaded files using vector similarity.
     */
    private function searchRelevantFileContent(string $userMessage): array
    {
        try {
            // First check if there are any processed files available
            $totalChunks = \Illuminate\Support\Facades\DB::table('rag_chunks')->count();
            if ($totalChunks === 0) {
                \Illuminate\Support\Facades\Log::info('No processed files available for RAG search');
                return [];
            }
            
            $embeddingService = new \App\Services\EmbeddingService();
            $vectorSearch = new \App\Services\VectorSearchService();
            
            // Generate embedding for user message
            $queryEmbedding = $embeddingService->getEmbedding($userMessage);
            
            if (!$queryEmbedding) {
                \Illuminate\Support\Facades\Log::warning('Failed to generate embedding for user message');
                return [];
            }
            
            // Search for similar chunks
            $similarChunks = $vectorSearch->searchSimilar($queryEmbedding, 5); // Get top 5 most relevant chunks
            
            // Filter out low similarity results (threshold of 0.3)
            $relevantChunks = array_filter($similarChunks, function($chunk) {
                return $chunk['similarity'] > 0.3;
            });
            
            \Illuminate\Support\Facades\Log::info('RAG Search - Query: "' . $userMessage . '", Found: ' . count($relevantChunks) . ' relevant chunks out of ' . $totalChunks . ' total chunks');
            
            if (!empty($relevantChunks)) {
                $fileNames = array_unique(array_column($relevantChunks, 'source'));
                \Illuminate\Support\Facades\Log::info('Relevant files: ' . implode(', ', $fileNames));
            }
            
            return $relevantChunks;
            
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error searching for relevant content: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Build context from relevant file chunks for the LLM prompt.
     */
    private function buildFileContext(array $relevantChunks): string
    {
        if (empty($relevantChunks)) {
            return '';
        }
        
        $context = "IMPORTANT: The following information comes from files uploaded by the user. Use this information as your primary source when answering questions:\n\n";
        
        foreach ($relevantChunks as $index => $chunk) {
            $fileName = $chunk['source'] ?? 'Unknown file';
            $context .= "--- Content from file: {$fileName} (relevance: " . round($chunk['similarity'], 3) . ") ---\n";
            $context .= $chunk['chunk'] . "\n\n";
        }
        
        $context .= "CRITICAL INSTRUCTION: When answering questions, ALWAYS prioritize and use the information above from the user's uploaded files as your primary source. Only fall back to your general knowledge about DEPDev if the question is completely unrelated to the uploaded content. Always cite which file the information comes from when possible.\n\n";
        
        return $context;
    }
}
