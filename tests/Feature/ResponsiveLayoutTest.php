<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ResponsiveLayoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_sidebar_and_toggle_button_coordination()
    {
        $response = $this->get('/');
        $response->assertStatus(200);
        
        $html = $response->getContent();
        
        // Check that both sidebar and toggle button exist in the HTML
        $this->assertStringContainsString('id="sidebarCollapse"', $html);
        $this->assertStringContainsString('id="sidebarToggleBtn"', $html);
        
        // Check that the JavaScript coordination logic is present
        $this->assertStringContainsString('getBootstrapBreakpoint', $html);
        $this->assertStringContainsString('setSidebarState', $html);
        
        // Check that the JavaScript handles all breakpoints
        $this->assertStringContainsString('case \'xxl\':', $html);
        $this->assertStringContainsString('case \'lg\':', $html);
        $this->assertStringContainsString('case \'md\':', $html);
        $this->assertStringContainsString('case \'sm\':', $html);
    }

    public function test_toggle_button_has_correct_classes()
    {
        $response = $this->get('/');
        $response->assertStatus(200);
        
        $html = $response->getContent();
        
        // Check that toggle button starts with d-none class (hidden by default)
        $this->assertStringContainsString('class="btn btn-primary d-none"', $html);
        
        // Check that it has the correct data attributes for Bootstrap collapse
        $this->assertStringContainsString('data-bs-toggle="collapse"', $html);
        $this->assertStringContainsString('data-bs-target="#sidebarCollapse"', $html);
    }

    public function test_sidebar_has_correct_structure()
    {
        $response = $this->get('/');
        $response->assertStatus(200);
        
        $html = $response->getContent();
        
        // Check that sidebar has the correct classes
        $this->assertStringContainsString('class="col-md-2 col-lg-2 bg-primary sidebar collapse', $html);
        
        // Check that sidebar has proper navigation structure
        $this->assertStringContainsString('<ul class="nav flex-column', $html);
        $this->assertStringContainsString('class="nav-link d-flex align-items-center"', $html);
    }

    public function test_javascript_coordination_logic()
    {
        $response = $this->get('/');
        $response->assertStatus(200);
        
        $html = $response->getContent();
        
        // Check that the JavaScript properly coordinates sidebar and toggle button
        $this->assertStringContainsString('shouldShowSidebar = true', $html);
        $this->assertStringContainsString('shouldShowToggle = true', $html);
        $this->assertStringContainsString('shouldShowSidebar = false', $html);
        $this->assertStringContainsString('shouldShowToggle = false', $html);
        
        // Check that the toggle button classes are managed properly
        $this->assertStringContainsString('toggleBtn.classList.remove(\'d-none\')', $html);
        $this->assertStringContainsString('toggleBtn.classList.add(\'d-none\')', $html);
        
        // Check for overlay functionality
        $this->assertStringContainsString('updateOverlay', $html);
        $this->assertStringContainsString('sidebarOverlay', $html);
        
        // Check for debounced resize handling
        $this->assertStringContainsString('clearTimeout(resizeTimeout)', $html);
        $this->assertStringContainsString('setTimeout(function()', $html);
    }
} 