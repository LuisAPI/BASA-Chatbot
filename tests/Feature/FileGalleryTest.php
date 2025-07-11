<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

class FileGalleryTest extends TestCase
{
    use RefreshDatabase;

    public function test_file_gallery_page_loads_with_no_files()
    {
        $response = $this->get('/chatbot/files');
        
        $response->assertStatus(200);
        $response->assertViewIs('file-gallery');
        $response->assertSee('No files have been processed yet');
        $response->assertSee('0 total chunks');
    }

    public function test_file_gallery_shows_files_with_previews()
    {
        // Insert test data
        DB::table('rag_chunks')->insert([
            [
                'source' => 'test-document.pdf',
                'chunk' => 'This is the first chunk of content from the test document. It contains important information about the Department of Economy, Planning and Development.',
                'embedding' => json_encode([0.1, 0.2, 0.3]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'source' => 'test-document.pdf',
                'chunk' => 'This is the second chunk with different content about government policies and economic development.',
                'embedding' => json_encode([0.4, 0.5, 0.6]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'source' => 'presentation.pptx',
                'chunk' => 'PowerPoint presentation content about infrastructure projects and development goals.',
                'embedding' => json_encode([0.7, 0.8, 0.9]),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ]);

        $response = $this->get('/chatbot/files');
        
        $response->assertStatus(200);
        $response->assertViewIs('file-gallery');
        $response->assertSee('test-document.pdf');
        $response->assertSee('presentation.pptx');
        $response->assertSee('2 files');
        $response->assertSee('3 total chunks');
        
        // Check for preview content
        $response->assertSee('This is the first chunk of content from the test document');
        $response->assertSee('PowerPoint presentation content about infrastructure projects');
        
        // Check for file type badges
        $response->assertSee('PDF Document');
        $response->assertSee('PowerPoint Presentation');
        
        // Check for view toggle buttons
        $response->assertSee('Grid');
        $response->assertSee('List');
    }

    public function test_file_chunks_endpoint_returns_correct_data()
    {
        // Insert test data
        DB::table('rag_chunks')->insert([
            [
                'source' => 'test-file.txt',
                'chunk' => 'First chunk content',
                'embedding' => json_encode([0.1, 0.2, 0.3]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'source' => 'test-file.txt',
                'chunk' => 'Second chunk content',
                'embedding' => json_encode([0.4, 0.5, 0.6]),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ]);

        $response = $this->postJson('/chatbot/file-chunks', [
            'file' => 'test-file.txt'
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'file' => 'test-file.txt',
            'total_chunks' => 2
        ]);
        
        $data = $response->json();
        $this->assertCount(2, $data['chunks']);
        $this->assertEquals('First chunk content', $data['chunks'][0]['chunk']);
        $this->assertEquals('Second chunk content', $data['chunks'][1]['chunk']);
    }

    public function test_file_gallery_handles_empty_file_chunks_request()
    {
        $response = $this->postJson('/chatbot/file-chunks', [
            'file' => 'nonexistent-file.pdf'
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'file' => 'nonexistent-file.pdf',
            'total_chunks' => 0
        ]);
        
        $data = $response->json();
        $this->assertCount(0, $data['chunks']);
    }

    public function test_file_gallery_includes_correct_metadata()
    {
        // Insert test data with specific timestamps
        $uploadTime = now()->subDays(2);
        DB::table('rag_chunks')->insert([
            [
                'source' => 'large-document.pdf',
                'chunk' => str_repeat('A', 1000), // Large chunk to test file size calculation
                'embedding' => json_encode([0.1, 0.2, 0.3]),
                'created_at' => $uploadTime,
                'updated_at' => $uploadTime,
            ],
            [
                'source' => 'large-document.pdf',
                'chunk' => str_repeat('B', 500), // Another chunk
                'embedding' => json_encode([0.4, 0.5, 0.6]),
                'created_at' => $uploadTime,
                'updated_at' => $uploadTime,
            ]
        ]);

        $response = $this->get('/chatbot/files');
        
        $response->assertStatus(200);
        $response->assertSee('large-document.pdf');
        $response->assertSee('PDF Document');
        $response->assertSee('2 chunks');
        
        // Check that the upload date is displayed
        $formattedDate = $uploadTime->format('M j, Y');
        $response->assertSee($formattedDate);
        
        // Check that file size is displayed (should be around 1.5 KB)
        $response->assertSee('KB');
    }
} 