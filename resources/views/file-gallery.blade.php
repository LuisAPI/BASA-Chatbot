@extends('layouts.app')

@section('title', 'File Gallery')

@section('content')
<div class="container-fluid h-100">
    <div class="row h-100">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mt-4 mb-4">
                <h2><i class="bi bi-folder2-open me-2"></i>File Gallery</h2>
                <div class="d-flex align-items-center">
                    <span class="badge bg-primary me-2">{{ $totalChunks }} total chunks</span>
                    <a href="/chatbot" class="btn btn-outline-primary">
                        <i class="bi bi-arrow-left me-1"></i>Back to Chat
                    </a>
                </div>
            </div>

            @if($filesWithPreviews->isEmpty())
                <div class="alert alert-info">
                    <i class="bi bi-info-circle me-2"></i>
                    No files have been processed yet. Upload some files in the chat to see them here.
                </div>
            @else
                <!-- View Toggle Buttons -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div class="d-flex align-items-center">
                        <span class="text-muted me-3">{{ $filesWithPreviews->count() }} files</span>
                    </div>
                    <div class="btn-group" role="group" aria-label="View toggle">
                        <input type="radio" class="btn-check" name="viewMode" id="gridViewBtn" value="grid" checked>
                        <label class="btn btn-outline-secondary" for="gridViewBtn">
                            <i class="bi bi-grid-3x3-gap"></i> Grid
                        </label>
                        
                        <input type="radio" class="btn-check" name="viewMode" id="listViewBtn" value="list">
                        <label class="btn btn-outline-secondary" for="listViewBtn">
                            <i class="bi bi-list"></i> List
                        </label>
                    </div>
                </div>

                <!-- Grid View -->
                <div id="gridView" class="view-mode">
                    <div class="row">
                        @foreach($filesWithPreviews as $file)
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card h-100 file-card {{ $file->is_system_document ? 'border-primary' : '' }}">
                                    <div class="card-header d-flex justify-content-between align-items-center {{ $file->is_system_document ? 'bg-primary text-white' : '' }}">
                                        <h6 class="mb-0">
                                            <i class="bi {{ $file->is_system_document ? 'bi-shield-check' : 'bi-file-earmark-text' }} me-2"></i>
                                            {{ Str::limit($file->source, 30) }}
                                        </h6>
                                        <div class="d-flex align-items-center">
                                            @if($file->is_system_document)
                                                <span class="badge bg-light text-primary me-1">System</span>
                                            @endif
                                            <span class="badge {{ $file->is_system_document ? 'bg-light text-primary' : 'bg-secondary' }}">{{ $file->chunk_count }} chunks</span>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-2">
                                            <small class="text-muted">
                                                <i class="bi bi-file-earmark me-1"></i>{{ $file->file_type }}
                                            </small>
                                        </div>
                                        <div class="mb-2">
                                            <small class="text-muted">
                                                <i class="bi bi-calendar me-1"></i>
                                                {{ $file->uploaded_at ? \Carbon\Carbon::parse($file->uploaded_at)->format('M j, Y') : 'Unknown' }}
                                            </small>
                                        </div>
                                        <div class="mb-2">
                                            <small class="text-muted">
                                                <i class="bi bi-hdd me-1"></i>{{ $file->file_size }}
                                            </small>
                                        </div>
                                        @if($file->preview)
                                            <div class="preview-content mb-3">
                                                <p class="card-text text-muted small" style="font-size: 0.85em; line-height: 1.4;">
                                                    {{ $file->preview }}
                                                </p>
                                            </div>
                                        @endif
                                        <div class="d-flex justify-content-between align-items-center">
                                            <button class="btn btn-sm btn-outline-primary view-chunks" 
                                                    data-file="{{ $file->source }}">
                                                <i class="bi bi-eye me-1"></i>View Chunks
                                            </button>
                                            <small class="text-muted">{{ Str::limit($file->source, 25) }}</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- List View -->
                <div id="listView" class="view-mode" style="display: none;">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>File Name</th>
                                    <th>Type</th>
                                    <th>Size</th>
                                    <th>Chunks</th>
                                    <th>Uploaded</th>
                                    <th>Preview</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($filesWithPreviews as $file)
                                    <tr class="{{ $file->is_system_document ? 'table-primary' : '' }}">
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <i class="bi {{ $file->is_system_document ? 'bi-shield-check' : 'bi-file-earmark-text' }} me-2 {{ $file->is_system_document ? 'text-primary' : 'text-primary' }}"></i>
                                                <div>
                                                    <div class="fw-medium">
                                                        {{ $file->source }}
                                                        @if($file->is_system_document)
                                                            <span class="badge bg-primary ms-2">System</span>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark">{{ $file->file_type }}</span>
                                        </td>
                                        <td>
                                            <small class="text-muted">{{ $file->file_size }}</small>
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary">{{ $file->chunk_count }}</span>
                                        </td>
                                        <td>
                                            <small class="text-muted">
                                                {{ $file->uploaded_at ? \Carbon\Carbon::parse($file->uploaded_at)->format('M j, Y') : 'Unknown' }}
                                            </small>
                                        </td>
                                        <td>
                                            @if($file->preview)
                                                <div class="preview-text" style="max-width: 300px;">
                                                    <small class="text-muted">{{ $file->preview }}</small>
                                                </div>
                                            @else
                                                <small class="text-muted">No preview</small>
                                            @endif
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary view-chunks" 
                                                    data-file="{{ $file->source }}">
                                                <i class="bi bi-eye me-1"></i>View
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Modal for viewing chunks -->
<div class="modal fade" id="chunksModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-file-earmark-text me-2"></i>
                    <span id="modalFileName"></span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="badge bg-info" id="chunkCount"></span>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="showChunkIds">
                        <label class="form-check-label" for="showChunkIds">
                            Show chunk IDs
                        </label>
                    </div>
                </div>
                <div id="chunksContainer" class="border rounded p-3" style="max-height: 500px; overflow-y: auto;">
                    <!-- Chunks will be loaded here -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle view mode toggle
    const gridView = document.getElementById('gridView');
    const listView = document.getElementById('listView');
    
    if (!gridView || !listView) {
        console.error('View containers not found');
        return;
    }
    
    document.querySelectorAll('input[name="viewMode"]').forEach(radio => {
        radio.addEventListener('change', function() {
            const viewMode = this.value;
            console.log('View mode changed to:', viewMode);
            
            if (viewMode === 'grid') {
                gridView.style.display = 'block';
                listView.style.display = 'none';
            } else if (viewMode === 'list') {
                gridView.style.display = 'none';
                listView.style.display = 'block';
            }
        });
    });
    
    // Also add click handlers to labels for better compatibility
    document.querySelectorAll('label[for="gridViewBtn"], label[for="listViewBtn"]').forEach(label => {
        label.addEventListener('click', function() {
            const forId = this.getAttribute('for');
            const radio = document.getElementById(forId);
            if (radio) {
                radio.checked = true;
                radio.dispatchEvent(new Event('change'));
            }
        });
    });

    // Handle view chunks button clicks
    document.querySelectorAll('.view-chunks').forEach(button => {
        button.addEventListener('click', function() {
            const fileName = this.getAttribute('data-file');
            loadFileChunks(fileName);
        });
    });

    // Handle show chunk IDs checkbox
    document.getElementById('showChunkIds').addEventListener('change', function() {
        const chunks = document.querySelectorAll('.chunk-item');
        chunks.forEach(chunk => {
            const idElement = chunk.querySelector('.chunk-id');
            if (idElement) {
                idElement.style.display = this.checked ? 'inline' : 'none';
            }
        });
    });
});

function loadFileChunks(fileName) {
    // Show loading state
    document.getElementById('modalFileName').textContent = fileName;
    document.getElementById('chunkCount').textContent = 'Loading...';
    document.getElementById('chunksContainer').innerHTML = '<div class="text-center"><div class="spinner-border" role="status"></div></div>';
    
    // Show modal
    const modal = new bootstrap.Modal(document.getElementById('chunksModal'));
    modal.show();

    // Fetch chunks
    fetch('/chatbot/file-chunks', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({ file: fileName })
    })
    .then(response => response.json())
    .then(data => {
        document.getElementById('chunkCount').textContent = `${data.total_chunks} chunks`;
        
        const container = document.getElementById('chunksContainer');
        container.innerHTML = '';
        
        if (data.chunks.length === 0) {
            container.innerHTML = '<div class="alert alert-warning">No chunks found for this file.</div>';
            return;
        }
        
        data.chunks.forEach((chunk, index) => {
            const chunkDiv = document.createElement('div');
            chunkDiv.className = 'chunk-item border-bottom pb-3 mb-3';
            chunkDiv.innerHTML = `
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <h6 class="mb-0">
                        Chunk ${index + 1}
                        <span class="chunk-id text-muted ms-2" style="display: none;">(ID: ${chunk.id})</span>
                    </h6>
                    <small class="text-muted">${new Date(chunk.created_at).toLocaleString()}</small>
                </div>
                <div class="chunk-content bg-light p-3 rounded">
                    <pre class="mb-0" style="white-space: pre-wrap; font-size: 0.9em;">${escapeHtml(chunk.chunk)}</pre>
                </div>
            `;
            container.appendChild(chunkDiv);
        });
    })
    .catch(error => {
        console.error('Error loading chunks:', error);
        document.getElementById('chunksContainer').innerHTML = '<div class="alert alert-danger">Error loading chunks. Please try again.</div>';
    });
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
</script>

<style>
.file-card {
    transition: transform 0.2s, box-shadow 0.2s;
}

.file-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.preview-content {
    background-color: #f8f9fa;
    border-radius: 4px;
    padding: 8px;
    border-left: 3px solid #dee2e6;
}

.preview-text {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.table tbody tr:hover {
    background-color: #f8f9fa;
}

.btn-check:checked + .btn-outline-secondary {
    background-color: #6c757d;
    border-color: #6c757d;
    color: white;
}
</style>
@endsection 