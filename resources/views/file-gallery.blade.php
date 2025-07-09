@extends('layouts.app')

@section('title', 'File Gallery')

@section('content')
<div class="container-fluid h-100">
    <div class="row h-100">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="bi bi-folder2-open me-2"></i>File Gallery</h2>
                <div class="d-flex align-items-center">
                    <span class="badge bg-primary me-2">{{ $totalChunks }} total chunks</span>
                    <a href="/chatbot" class="btn btn-outline-primary">
                        <i class="bi bi-arrow-left me-1"></i>Back to Chat
                    </a>
                </div>
            </div>

            @if($files->isEmpty())
                <div class="alert alert-info">
                    <i class="bi bi-info-circle me-2"></i>
                    No files have been processed yet. Upload some files in the chat to see them here.
                </div>
            @else
                <div class="row">
                    @foreach($files as $file)
                        <div class="col-md-6 col-lg-4 mb-4">
                            <div class="card h-100">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h6 class="mb-0">
                                        <i class="bi bi-file-earmark-text me-2"></i>
                                        {{ Str::limit($file->source, 30) }}
                                    </h6>
                                    <span class="badge bg-secondary">{{ $file->chunk_count }} chunks</span>
                                </div>
                                <div class="card-body">
                                    <p class="card-text text-muted small">
                                        <strong>Full name:</strong> {{ $file->source }}
                                    </p>
                                    <button class="btn btn-sm btn-outline-primary view-chunks" 
                                            data-file="{{ $file->source }}">
                                        <i class="bi bi-eye me-1"></i>View Chunks
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endforeach
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
@endsection 