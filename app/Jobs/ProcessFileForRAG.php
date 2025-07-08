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

    public function __construct($filePath, $fileName)
    {
        $this->filePath = $filePath;
        $this->fileName = $fileName;
    }

    public function handle()
    {
        try {
            $ext = strtolower(pathinfo($this->fileName, PATHINFO_EXTENSION));
            $content = '';
            $fullPath = storage_path('app/' . $this->filePath);
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
                    $vectorSearch->storeChunk($this->fileName, $chunk, $embedding);
                }
            }
            event(new FileProcessed($this->fileName));
        } catch (\Exception $e) {
            event(new \App\Events\FileFailed($this->fileName, $e->getMessage()));
        }
    }
}
