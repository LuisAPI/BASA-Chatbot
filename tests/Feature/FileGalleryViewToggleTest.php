<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

class FileGalleryViewToggleTest extends TestCase
{
    use RefreshDatabase;

    public function test_view_toggle_buttons_are_present()
    {
        // Insert test data
        DB::table('rag_chunks')->insert([
            [
                'source' => 'test-file.pdf',
                'chunk' => 'Test content',
                'embedding' => json_encode([0.1, 0.2, 0.3]),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ]);

        $response = $this->get('/chatbot/files');
        
        $response->assertStatus(200);
        
        // Check for toggle buttons
        $response->assertSee('Grid');
        $response->assertSee('List');
        
        // Check for correct IDs
        $response->assertSee('gridViewBtn');
        $response->assertSee('listViewBtn');
        
        // Check for view containers
        $response->assertSee('gridView');
        $response->assertSee('listView');
    }

    public function test_grid_view_is_default()
    {
        // Insert test data
        DB::table('rag_chunks')->insert([
            [
                'source' => 'test-file.pdf',
                'chunk' => 'Test content',
                'embedding' => json_encode([0.1, 0.2, 0.3]),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ]);

        $response = $this->get('/chatbot/files');
        
        $response->assertStatus(200);
        
        // Check that list view is hidden
        $response->assertSee('listView');
        $response->assertSee('display: none');
        
        // Grid view radio should be checked
        $response->assertSee('checked');
    }

    public function test_both_view_modes_contain_file_data()
    {
        // Insert test data
        DB::table('rag_chunks')->insert([
            [
                'source' => 'test-file.pdf',
                'chunk' => 'Test content for preview',
                'embedding' => json_encode([0.1, 0.2, 0.3]),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ]);

        $response = $this->get('/chatbot/files');
        
        $response->assertStatus(200);
        
        // Both views should contain the file data
        $response->assertSee('test-file.pdf');
        $response->assertSee('Test content for preview');
        
        // Grid view should have card structure
        $response->assertSee('card');
        $response->assertSee('file-card');
        
        // List view should have table structure
        $response->assertSee('table');
        $response->assertSee('thead');
    }

    public function test_javascript_event_handlers_are_present()
    {
        // Insert test data
        DB::table('rag_chunks')->insert([
            [
                'source' => 'test-file.pdf',
                'chunk' => 'Test content',
                'embedding' => json_encode([0.1, 0.2, 0.3]),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ]);

        $response = $this->get('/chatbot/files');
        
        $response->assertStatus(200);
        
        // Check for JavaScript event handlers
        $response->assertSee('viewMode');
        $response->assertSee('addEventListener');
        $response->assertSee('grid');
        $response->assertSee('list');
        
        // Check for label click handlers
        $response->assertSee('gridViewBtn');
        $response->assertSee('listViewBtn');
    }

    public function test_file_gallery_works_with_no_files()
    {
        $response = $this->get('/chatbot/files');
        
        $response->assertStatus(200);
        $response->assertSee('No files have been processed yet');
        $response->assertSee('0 total chunks');
    }
} 