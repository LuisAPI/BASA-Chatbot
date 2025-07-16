<?php

// File: app/Jobs/ProcessFileForRAG.php

namespace App\Jobs;

use App\Services\Chunker;
use App\Services\EmbeddingService;
use App\Services\VectorSearchService;
use App\Events\FileProcessed;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessFileForRAG implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $filePath;
    public $fileName;
    public $userId;

    public function __construct($filePath, $fileName, $userId = null)
    {
        $this->filePath = $filePath;
        $this->fileName = $fileName;
        $this->userId = $userId;
    }

    public function handle()
    {
        try {
            $ext = strtolower(pathinfo($this->fileName, PATHINFO_EXTENSION));
            $content = '';
            
            // Determine the full path based on the file location
            if (str_starts_with($this->filePath, 'documents/')) {
                // File is in public/documents directory (including subdirectories)
                $fullPath = public_path($this->filePath);
            } else {
                // File is in private storage (user uploads)
                $fullPath = storage_path('app/private/' . $this->filePath);
            }
            if (in_array($ext, ['txt', 'csv', 'rtf', 'odt'])) {
                $content = file_get_contents($fullPath);
            } elseif ($ext === 'pdf') {
                try {
                    $parser = new \Smalot\PdfParser\Parser();
                    $pdf = $parser->parseFile($fullPath);
                    $content = $pdf->getText();
                } catch (\Exception $e) {
                    $content = '[PDF parsing failed: ' . $e->getMessage() . ']';
                }
            } elseif (in_array($ext, ['doc', 'docx'])) {
                try {
                    $phpWord = \PhpOffice\PhpWord\IOFactory::load($fullPath);
                    $text = '';
                    foreach ($phpWord->getSections() as $section) {
                        $elements = $section->getElements();
                        foreach ($elements as $element) {
                            if ($element instanceof \PhpOffice\PhpWord\Element\Text) {
                                $text .= $element->getText() . "\n";
                            } elseif ($element instanceof \PhpOffice\PhpWord\Element\TextRun) {
                                foreach ($element->getElements() as $subElement) {
                                    if ($subElement instanceof \PhpOffice\PhpWord\Element\Text) {
                                        $text .= $subElement->getText();
                                    }
                                }
                                $text .= "\n";
                            }
                        }
                    }
                    $content = $text;
                } catch (\Exception $e) {
                    $content = '[DOC/DOCX parsing failed: ' . $e->getMessage() . ']';
                }
            } elseif (in_array($ext, ['xlsx', 'xls'])) {
                try {
                    $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($fullPath);
                    $sheet = $spreadsheet->getActiveSheet();
                    $rows = $sheet->toArray();
                    $lines = array();
                    foreach ($rows as $row) {
                        $lines[] = implode("\t", $row);
                    }
                    $content = implode("\n", $lines);
                } catch (\Exception $e) {
                    $content = '[Excel parsing failed: ' . $e->getMessage() . ']';
                }
            } else {
                $content = '[Unsupported file type]';
            }
            $chunker = new Chunker();
            $embedder = new EmbeddingService();
            $vectorSearch = new VectorSearchService();
            $chunks = $chunker->chunkText($content);
            foreach ($chunks as $chunk) {
                $embedding = $embedder->getEmbedding($chunk);
                if ($embedding) {
                    $vectorSearch->storeChunk($this->fileName, $chunk, $embedding, $this->userId);
                }
            }
            
            // Update the UserFile record with actual file size and mark as completed
            if ($this->userId) {
                $fileSize = filesize($fullPath);
                \App\Models\UserFile::where('user_id', $this->userId)
                    ->where('original_name', $this->fileName)
                    ->where('storage_path', $this->filePath)
                    ->update([
                        'processing_status' => 'completed',
                        'processed_at' => now(),
                        'file_size' => $fileSize
                    ]);
            }
            
            // Log the successful processing
            // Log::info('📦 Dispatching FileProcessed event for: ' . $this->fileName);

            // Dispatch the event after processing
            event(new FileProcessed($this->fileName, 'completed'));
        } catch (\Exception $e) {
            // Update the UserFile record to mark as failed
            if ($this->userId) {
                \App\Models\UserFile::where('user_id', $this->userId)
                    ->where('original_name', $this->fileName)
                    ->update([
                        'processing_status' => 'failed',
                        'error_message' => $e->getMessage()
                    ]);
            }
            
            // Dispatch failed event
            event(new FileProcessed($this->fileName, 'failed'));
            event(new \App\Events\FileFailed($this->fileName, $e->getMessage()));
        }
    }
}
