<?php

namespace App\Http\Controllers;

use App\Services\Chunker;
use App\Services\EmbeddingService;
use App\Services\VectorSearchService;
use App\Services\OllamaConnectionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Jobs\ProcessFileForRAG;

class ChatbotController extends Controller
{
    protected $ollamaService;

    public function __construct()
    {
        $this->ollamaService = new OllamaConnectionService();
    }

    public function index()
    {
        return view('chatbot');
    }

    public function ask(Request $request)
    {
        $userMessage = $request->input('message');
        $selectedFiles = $request->input('selected_files', []); // New: array of selected file names

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
        $webpageContext = '';
        if (preg_match('/(https?:\/\/[\S]+)/i', $userMessage, $matches)) {
            $url = $matches[1];
            $question = trim(str_replace($url, '', $userMessage));
            if (empty($question)) {
                $question = 'Summarize the following webpage.';
            }
            $webResult = $this->fetchAndParseWebpage($url);
            if (isset($webResult['error'])) {
                return response()->json(['reply' => $webResult['error']]);
            }
            // Cache and chunk webpage
            $webpageId = $this->cacheAndChunkWebpage($url, $webResult['title'], $webResult['content']);
            // Vector search for relevant webpage chunks
            $relevantWebChunks = $this->searchRelevantWebpageChunks($question, $webpageId);
            $webpageContext = $this->buildWebpageContext($relevantWebChunks, $webResult['title'], $url);
            $userMessage = $question;
        }

        // Only perform RAG search if files are explicitly selected
        $relevantContent = [];
        if (!empty($selectedFiles)) {
            $relevantContent = $this->searchRelevantFileContent($userMessage, $selectedFiles);
        }

        // Build final context for LLM
        $finalContext = '';
        if (!empty($webpageContext)) {
            $finalContext .= $webpageContext . "\n\n";
        }
        if (!empty($relevantContent)) {
            $finalContext .= $this->buildFileContext(array_slice($relevantContent, 0, 2)) . "\n\n";
        }
        if (!empty($finalContext)) {
            $userMessage = "IMPORTANT: Use ONLY the following content to answer. If the answer is not present, say 'I don't know.'\n\n" . $finalContext . "QUESTION: " . $userMessage;
        }

        $botReply = $this->sendToLLM($userMessage, null, []);

        return response()->json([
            'reply' => $botReply,
            'rag_used' => !empty($relevantContent),
            'rag_chunks_found' => count($relevantContent),
            'rag_files' => !empty($relevantContent) ? array_unique(array_column($relevantContent, 'source')) : [],
            'web_chunks_used' => !empty($webpageContext),
            'debug_enabled' => config('app.debug', false)
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

IMPORTANT: When processing content from PowerPoint presentations, Excel spreadsheets, or other structured documents:

1. **Structure your responses clearly** with proper paragraphs, bullet points, and sections
2. **Organize information logically** by topic, timeline, or department
3. **Use clear headings** to separate different sections
4. **Convert fragmented text into coherent sentences** and paragraphs
5. **Maintain professional formatting** with proper capitalization and punctuation
6. **Group related information together** even if it appears scattered in the source
7. **Add context and explanations** to make technical information more accessible
8. **Use bullet points for lists** and numbered items for sequences
9. **Highlight key dates, milestones, and status indicators** clearly
10. **Summarize complex technical details** in simple, understandable language
EOT;
    }

    /**
     * Send a prompt to the LLM and return the response.
     * If streaming is enabled, handle streamed responses.
     */
    private function sendToLLM(string $prompt, ?string $systemPrompt = null, ?array $relevantContent = null): string
    {
        // Set execution time limit for this request
        set_time_limit(120); // 2 minutes
        
        $systemPrompt = $systemPrompt ?? $this->getSystemPrompt();
        
        // If we have relevant content from uploaded files, prioritize it
        if ($relevantContent && !empty($relevantContent)) {
            // Only use the top 2 most relevant chunks
            $fileContext = $this->buildFileContext(array_slice($relevantContent, 0, 2));
            // Prepend the context and a strong instruction to the user prompt
            $prompt = "IMPORTANT: Use ONLY the following file content to answer. If the answer is not present, say 'I don't know.'\n\n" . $fileContext . "\n\nQUESTION: " . $prompt;
            // Add specific instructions for structured content
            $prompt = "STRUCTURED CONTENT INSTRUCTIONS: The following content may be from PowerPoint presentations, Excel spreadsheets, or other structured documents. Please:\n" .
                     "1. Organize the information into clear, logical sections with proper headings\n" .
                     "2. Use bullet points for lists and numbered items for sequences\n" .
                     "3. Convert fragmented text into coherent, readable sentences and paragraphs\n" .
                     "4. Group related information together by department, module, or timeline\n" .
                     "5. Highlight key dates, milestones, and status indicators clearly\n" .
                     "6. Provide specific details from the content, not generic summaries\n" .
                     "7. Use the exact names, dates, and statuses mentioned in the source\n" .
                     "8. Structure the response with clear sections and subsections\n\n" .
                     $prompt;
        }
        
        $model = env('LLM_MODEL', 'tinyllama');
        $stream = filter_var(env('LLM_STREAM', false), FILTER_VALIDATE_BOOLEAN);
        
        if (!$stream) {
            // Non-streaming mode
            try {
                $endpoint = $this->ollamaService->getActiveEndpoint();
                if (!$endpoint) {
                    return 'Error: Could not connect to any Ollama instance. Please check if Ollama is running.';
                }

                $request = $this->ollamaService->createAuthenticatedRequest();
                $response = $request->timeout(90)->post($endpoint . '/api/generate', [
                    'model' => $model,
                    'system' => $systemPrompt,
                    'prompt' => $prompt,
                    'stream' => false
                ]);
                
                if (!$response->ok()) {
                    \Illuminate\Support\Facades\Log::error('Ollama API error: ' . $response->status() . ' - ' . $response->body());
                    return 'Error: Could not connect to the language model. Please check if Ollama is running.';
                }
                
                $data = $response->json();
                $botReply = $data['response'] ?? 'No response from model.';
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Error calling Ollama API: ' . $e->getMessage());
                return 'Error: Could not connect to the language model. Please check if Ollama is running.';
            }
        } else {
            // Streaming mode: collect tokens as they arrive
            try {
                $endpoint = $this->ollamaService->getActiveEndpoint();
                if (!$endpoint) {
                    return 'Error: Could not connect to any Ollama instance. Please check if Ollama is running.';
                }

                $botReply = '';
                $client = new \GuzzleHttp\Client([
                    'timeout' => 90, // 90 seconds timeout
                    'connect_timeout' => 10, // 10 seconds connection timeout
                    'read_timeout' => 90 // 90 seconds read timeout
                ]);
                
                // Add authentication headers if required
                $headers = ['Content-Type' => 'application/json'];
                if (config('ollama.require_auth') && config('ollama.api_key')) {
                    $headers['Authorization'] = 'Bearer ' . config('ollama.api_key');
                }
                
                $res = $client->post($endpoint . '/api/generate', [
                    'headers' => $headers,
                    'json' => [
                        'model' => $model,
                        'system' => $systemPrompt,
                        'prompt' => $prompt,
                        'stream' => true
                    ],
                    'stream' => true
                ]);
                $body = $res->getBody();
                $startTime = time();
                $maxDuration = 90; // Maximum 90 seconds for the entire response
                
                while (!$body->eof()) {
                    // Check if we've exceeded the maximum duration
                    if (time() - $startTime > $maxDuration) {
                        \Illuminate\Support\Facades\Log::warning('Stream timeout reached, stopping response');
                        break;
                    }
                    
                    $line = trim($body->read(4096));
                    if ($line) {
                        // Each line is a JSON object with a 'response' key
                        $json = json_decode($line, true);
                        if (isset($json['response'])) {
                            $botReply .= $json['response'];
                        }
                        // Check if this is the final response
                        if (isset($json['done']) && $json['done'] === true) {
                            break;
                        }
                    }
                }
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Error in streaming mode: ' . $e->getMessage());
                return 'Error: Could not connect to the language model. Please check if Ollama is running.';
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
        // Set execution time limit for this request
        set_time_limit(120); // 2 minutes
        
        $prompt = $request->input('message');
        $selectedFiles = $request->input('selected_files', []); // New: array of selected file names
        $systemPrompt = $this->getSystemPrompt();
        $model = env('LLM_MODEL', 'tinyllama');
        
        // Only perform RAG search if files are explicitly selected
        $relevantContent = [];
        if (!empty($selectedFiles)) {
            $relevantContent = $this->searchRelevantFileContent($prompt, $selectedFiles);
        }
        
        // If we have relevant content from uploaded files, prioritize it
        if ($relevantContent && !empty($relevantContent)) {
            // Only use the top 2 most relevant chunks
            $fileContext = $this->buildFileContext(array_slice($relevantContent, 0, 2));
            // Prepend the context and a strong instruction to the user prompt
            $prompt = "IMPORTANT: Use ONLY the following file content to answer. If the answer is not present, say 'I don't know.'\n\n" . $fileContext . "\n\nQUESTION: " . $prompt;
            // Add specific instructions for structured content
            $prompt = "STRUCTURED CONTENT INSTRUCTIONS: The following content may be from PowerPoint presentations, Excel spreadsheets, or other structured documents. Please:\n" .
                     "1. Organize the information into clear, logical sections with proper headings\n" .
                     "2. Use bullet points for lists and numbered items for sequences\n" .
                     "3. Convert fragmented text into coherent, readable sentences and paragraphs\n" .
                     "4. Group related information together by department, module, or timeline\n" .
                     "5. Highlight key dates, milestones, and status indicators clearly\n" .
                     "6. Provide specific details from the content, not generic summaries\n" .
                     "7. Use the exact names, dates, and statuses mentioned in the source\n" .
                     "8. Structure the response with clear sections and subsections\n\n" .
                     $prompt;
        }
        
        try {
            $endpoint = $this->ollamaService->getActiveEndpoint();
            if (!$endpoint) {
                return response()->json(['error' => 'Could not connect to any Ollama instance. Please check if Ollama is running.'], 500);
            }

            return response()->stream(function () use ($prompt, $systemPrompt, $model, $endpoint) {
                // Set execution time limit for the stream function
                set_time_limit(120);
                
                $client = new \GuzzleHttp\Client([
                    'timeout' => 90, // 90 seconds timeout
                    'connect_timeout' => 10, // 10 seconds connection timeout
                    'read_timeout' => 90 // 90 seconds read timeout
                ]);
                
                // Add authentication headers if required
                $headers = ['Content-Type' => 'application/json'];
                if (config('ollama.require_auth') && config('ollama.api_key')) {
                    $headers['Authorization'] = 'Bearer ' . config('ollama.api_key');
                }
                
                $res = $client->post($endpoint . '/api/generate', [
                    'headers' => $headers,
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
                $startTime = time();
                $maxDuration = 90; // Maximum 90 seconds for the entire response
                
                while (!$body->eof()) {
                    // Check if we've exceeded the maximum duration
                    if (time() - $startTime > $maxDuration) {
                        \Illuminate\Support\Facades\Log::warning('Stream timeout reached, stopping response');
                        break;
                    }
                    
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
                            // Check if this is the final response
                            if (isset($json['done']) && $json['done'] === true) {
                                return;
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
            \Illuminate\Support\Facades\Log::error('Streaming failed: ' . $e->getMessage());
            
            // Try non-streaming mode as fallback
            try {
                $response = $this->sendToLLM($prompt, $systemPrompt, $relevantContent);
                return response()->json(['reply' => $response]);
            } catch (\Exception $fallbackError) {
                \Illuminate\Support\Facades\Log::error('Fallback also failed: ' . $fallbackError->getMessage());
                return response()->json(['error' => 'Service temporarily unavailable. Please try again.'], 500);
            }
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
     * Get available files for selection in the chat interface.
     */
    public function getAvailableFiles()
    {
        try {
            $files = \Illuminate\Support\Facades\DB::table('rag_chunks')
                ->select('source', \Illuminate\Support\Facades\DB::raw('COUNT(*) as chunk_count'))
                ->groupBy('source')
                ->orderBy('source')
                ->get()
                ->map(function ($file) {
                    return [
                        'name' => $file->source,
                        'chunk_count' => $file->chunk_count,
                        'is_system_document' => $this->isSystemDocument($file->source),
                        'file_type' => $this->getFileType($file->source),
                        'file_size' => $this->getFileSize($file->source)
                    ];
                });
            
            return response()->json([
                'files' => $files,
                'total_files' => $files->count()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'files' => [],
                'total_files' => 0,
                'error' => $e->getMessage()
            ], 500);
        }
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

        // Get preview content for each file (first chunk)
        $filesWithPreviews = $files->map(function ($file) {
            $firstChunk = \Illuminate\Support\Facades\DB::table('rag_chunks')
                ->where('source', $file->source)
                ->select('chunk', 'created_at')
                ->orderBy('id')
                ->first();
            
            $file->preview = $firstChunk ? $this->generatePreview($firstChunk->chunk) : '';
            $file->file_type = $this->getFileType($file->source);
            $file->uploaded_at = $firstChunk ? $firstChunk->created_at : null;
            $file->file_size = $this->getFileSize($file->source);
            
            // Determine if this is a system document or user upload
            $file->is_system_document = $this->isSystemDocument($file->source);
            
            return $file;
        });

        $totalChunks = \Illuminate\Support\Facades\DB::table('rag_chunks')->count();
        
        return view('file-gallery', compact('filesWithPreviews', 'totalChunks'));
    }

    /**
     * Generate a preview from chunk content.
     */
    private function generatePreview(string $chunk): string
    {
        // Clean and truncate the chunk for preview
        $preview = strip_tags($chunk);
        $preview = preg_replace('/\s+/', ' ', $preview); // Replace multiple spaces with single space
        $preview = trim($preview);
        
        // Limit to 200 characters for preview
        if (strlen($preview) > 200) {
            $preview = substr($preview, 0, 200) . '...';
        }
        
        return $preview;
    }

    /**
     * Get file type based on extension.
     */
    private function getFileType(string $filename): string
    {
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        $typeMap = [
            'pdf' => 'PDF Document',
            'doc' => 'Word Document',
            'docx' => 'Word Document',
            'txt' => 'Text File',
            'rtf' => 'Rich Text File',
            'ppt' => 'PowerPoint Presentation',
            'pptx' => 'PowerPoint Presentation',
            'xls' => 'Excel Spreadsheet',
            'xlsx' => 'Excel Spreadsheet',
            'csv' => 'CSV File',
            'html' => 'HTML File',
            'htm' => 'HTML File',
            'md' => 'Markdown File',
            'json' => 'JSON File',
            'xml' => 'XML File'
        ];
        
        return $typeMap[$extension] ?? 'Document';
    }

    /**
     * Get file size information (estimated from chunks).
     */
    private function getFileSize(string $filename): string
    {
        $totalSize = \Illuminate\Support\Facades\DB::table('rag_chunks')
            ->where('source', $filename)
            ->sum(\Illuminate\Support\Facades\DB::raw('LENGTH(chunk)'));
        
        if ($totalSize < 1024) {
            return $totalSize . ' B';
        } elseif ($totalSize < 1024 * 1024) {
            return round($totalSize / 1024, 1) . ' KB';
        } else {
            return round($totalSize / (1024 * 1024), 1) . ' MB';
        }
    }

    /**
     * Check if a file is a system document (from public/documents).
     */
    private function isSystemDocument(string $filename): bool
    {
        // Check if the file exists in the public/documents directory or any subdirectory
        $documentsPath = public_path('documents');
        
        // Search recursively for the file
        return $this->fileExistsInDirectory($documentsPath, $filename);
    }

    /**
     * Recursively search for a file in a directory and its subdirectories.
     */
    private function fileExistsInDirectory(string $directory, string $filename): bool
    {
        $items = \Illuminate\Support\Facades\File::files($directory);
        
        foreach ($items as $item) {
            if ($item->getFilename() === $filename) {
                return true;
            }
        }
        
        // Check subdirectories
        $subdirectories = \Illuminate\Support\Facades\File::directories($directory);
        foreach ($subdirectories as $subdirectory) {
            if ($this->fileExistsInDirectory($subdirectory, $filename)) {
                return true;
            }
        }
        
        return false;
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
        try {
            // Enhanced Browsershot arguments and headers for anti-bot evasion
            $userAgents = [
                'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/114 Safari/537.36',
                'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/15.1 Safari/605.1.15',
                'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:109.0) Gecko/20100101 Firefox/115.0',
                'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/115.0.0.0 Safari/537.36',
            ];
            $userAgent = $userAgents[array_rand($userAgents)];
            $viewportSizes = [
                [1920, 1080],
                [1366, 768],
                [1536, 864],
                [1280, 800],
            ];
            $viewport = $viewportSizes[array_rand($viewportSizes)];
            $headers = [
                'Accept-Language' => 'en-US,en;q=0.9',
                'Accept-Encoding' => 'gzip, deflate, br',
                'Referer' => 'https://www.google.com/',
                'DNT' => '1',
            ];
            $args = [
                '--no-sandbox',
                '--disable-setuid-sandbox',
                '--disable-dev-shm-usage',
                '--disable-gpu',
                '--disable-blink-features=AutomationControlled',
                '--disable-automation',
                '--disable-infobars',
                '--disable-web-security',
                '--disable-features=IsolateOrigins,site-per-process',
                '--window-size=' . $viewport[0] . ',' . $viewport[1],
                '--lang=en-US',
                '--start-maximized',
                '--hide-scrollbars',
                '--disable-extensions',
                '--user-agent=' . $userAgent,
            ];
            $html = \Spatie\Browsershot\Browsershot::url($url)
                ->setChromePath(env('CHROME_PATH', 'C:\Program Files\Google\Chrome\Application\chrome.exe'))
                ->setOption('args', $args)
                ->setOption('waitUntil', env('BROWSERSHOT_WAIT_UNTIL', 'networkidle0'))
                ->setOption('headers', $headers)
                ->setViewport($viewport[0], $viewport[1])
                ->timeout(env('BROWSERSHOT_TIMEOUT', 60))
                ->bodyHtml();

            // Use Readability to parse the HTML
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
     * If $selectedFiles is provided, only search within those specific files.
     */
    private function searchRelevantFileContent(string $userMessage, array $selectedFiles = []): array
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
            $similarChunks = $vectorSearch->searchSimilar($queryEmbedding, 5, $selectedFiles); // Get top 5 most relevant chunks
            
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

    /**
     * Search for relevant chunks of a cached webpage using vector similarity.
     */
    private function searchRelevantWebpageChunks(string $userMessage, string $webpageId): array
    {
        try {
            $embeddingService = new \App\Services\EmbeddingService();
            $vectorSearch = new \App\Services\VectorSearchService();
            
            // Generate embedding for user message
            $queryEmbedding = $embeddingService->getEmbedding($userMessage);
            
            if (!$queryEmbedding) {
                \Illuminate\Support\Facades\Log::warning('Failed to generate embedding for user message');
                return [];
            }
            
            // Search for similar chunks within the specific webpage
            $similarChunks = $vectorSearch->searchSimilar($queryEmbedding, 5, [$webpageId]); // Get top 5 most relevant chunks
            
            // Filter out low similarity results (threshold of 0.3)
            $relevantChunks = array_filter($similarChunks, function($chunk) {
                return $chunk['similarity'] > 0.3;
            });
            
            \Illuminate\Support\Facades\Log::info('Webpage RAG Search - Query: "' . $userMessage . '", Found: ' . count($relevantChunks) . ' relevant chunks for webpage ID ' . $webpageId);
            
            return $relevantChunks;
            
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error searching for relevant webpage chunks: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Cache and chunk a webpage's content for later retrieval using the webpage_chunks table.
     */
    private function cacheAndChunkWebpage(string $url, string $title, string $content): ?string
    {
        try {
            // Generate a unique ID for the webpage based on its URL
            $webpageId = md5($url);

            // Check if the webpage is already cached
            $existingCache = \Illuminate\Support\Facades\DB::table('webpage_chunks')
                ->where('webpage_id', $webpageId)
                ->first();

            if ($existingCache) {
                // Webpage is already cached, no need to re-cache
                return $webpageId;
            }

            // Use the existing Chunker service for chunking
            $chunker = new \App\Services\Chunker();
            $chunks = $chunker->chunkText($content);

            // Insert the chunks into the new webpage_chunks table
            foreach ($chunks as $chunk) {
                \Illuminate\Support\Facades\DB::table('webpage_chunks')->insert([
                    'webpage_id' => $webpageId,
                    'url' => $url,
                    'title' => $title,
                    'chunk' => $chunk,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }

            return $webpageId;
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error caching webpage: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Build context from relevant webpage chunks for the LLM prompt.
     */
    private function buildWebpageContext(array $relevantChunks, string $title, string $url): string
    {
        if (empty($relevantChunks)) {
            return '';
        }
        $context = "IMPORTANT: The following information comes from a webpage. Use this information as your primary source when answering questions:\n\n";
        $context .= "Page Title: {$title}\n";
        $context .= "URL: {$url}\n\n";
        foreach ($relevantChunks as $index => $chunk) {
            $context .= "--- Content chunk (relevance: " . round($chunk['similarity'], 3) . ") ---\n";
            $context .= $chunk['chunk'] . "\n\n";
        }
        $context .= "CRITICAL INSTRUCTION: When answering questions, ALWAYS prioritize and use the information above from the webpage as your primary source. Only fall back to your general knowledge about DEPDev if the question is completely unrelated to the webpage content. Always cite the webpage title and URL when possible.\n\n";
        return $context;
    }
}
