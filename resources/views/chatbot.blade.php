{{-- File: chatbot.blade.php --}}

@extends('layouts.app')

@section('title', 'Bot for Automated Semantic Assistance')

@section('head')
    <style>
        body {
            background: url('/images/BP-DEPDev%20Zoom%20Background.jpg') no-repeat center center fixed;
            background-size: cover;
        }
        
        /* File selection modal styles */
        .file-item {
            transition: background-color 0.2s ease;
        }
        
        .file-item:hover {
            background-color: #f8f9fa;
        }
        
        .file-item:last-child {
            border-bottom: none !important;
        }
        
        .file-name {
            word-break: break-word;
        }
        
        #fileSelectionContainer {
            scrollbar-width: thin;
            scrollbar-color: #dee2e6 #f8f9fa;
        }
        
        #fileSelectionContainer::-webkit-scrollbar {
            width: 6px;
        }
        
        #fileSelectionContainer::-webkit-scrollbar-track {
            background: #f8f9fa;
        }
        
        #fileSelectionContainer::-webkit-scrollbar-thumb {
            background: #dee2e6;
            border-radius: 3px;
        }
        
        #fileSelectionContainer::-webkit-scrollbar-thumb:hover {
            background: #adb5bd;
        }
        
        /* Action buttons styling */
        .chat-content.bot .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.75rem;
            border-radius: 0.25rem;
        }
        
        .chat-content.bot .btn-outline-secondary:hover {
            background-color: #6c757d;
            border-color: #6c757d;
            color: white;
        }
        
        .chat-content.bot .btn-outline-primary:hover {
            background-color: #0d6efd;
            border-color: #0d6efd;
            color: white;
        }
        
        .chat-content.bot .btn-success {
            background-color: #198754;
            border-color: #198754;
            color: white;
        }
    </style>
@endsection

@section('content')
<div class="chat-container">
    <div id="chat-log" class="chat-log">
        <noscript>
            <div class="chat-message">
                <div class="chat-avatar bot">Bot</div>
                <div class="chat-content bot">
                    <p><strong>JavaScript is required:</strong> This chatbot interface will not work unless JavaScript is enabled in your browser. Please enable JavaScript to use BASA.</p>
                </div>
            </div>
        </noscript>
    </div>
    <div class="chat-input-bar position-sticky bottom-0 start-0 w-100 bg-white border-top p-3" style="z-index: 10;">
        <!-- File pill UI -->
        <div id="file-pill-container" class="mb-2" style="display:none;"></div>
        <form id="chat-form" class="d-flex gap-2 mb-2 align-items-start">
            <div class="dropdown">
                <button class="btn btn-primary d-flex align-items-center justify-content-center p-2" type="button" id="attachmentDropdown" data-bs-toggle="dropdown" aria-expanded="false" style="height: 38px; width: 38px;">
                    <i class="bi bi-paperclip" style="font-size: 1.3em;"></i>
                </button>
                <ul class="dropdown-menu" aria-labelledby="attachmentDropdown" style="min-width: 300px;">
                    <li><button class="dropdown-item" type="button" id="test-multiline">Test reply</button></li>
                    <li>
                        <div class="px-3 py-2">
                            <label for="url-attach" class="form-label mb-1">Insert URL</label>
                            <input type="url" class="form-control form-control-sm" id="url-attach" placeholder="https://example.com">
                            <button class="btn btn-sm btn-primary mt-2 w-100" type="button" id="attachUrlBtn">Attach URL</button>
                        </div>
                    </li>
                    <li>
                        <div class="px-3 py-2">
                            <label for="file-attach" class="form-label mb-1">Upload New File</label>
                            <input type="file" class="form-control form-control-sm" id="file-attach" accept=".txt,.pdf,.doc,.docx,.rtf,.odt,.csv,.xlsx,.xls">
                            <button class="btn btn-sm btn-primary mt-2 w-100" type="button" id="attachFileBtn">Upload & Process</button>
                        </div>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <button class="dropdown-item" type="button" id="openFileSelectorBtn">
                            <i class="bi bi-folder2-open me-2"></i>Select Existing Files
                        </button>
                    </li>
                </ul>
            </div>
            <textarea id="message" class="form-control" placeholder="Type your question..." autocomplete="off" required rows="1" style="resize:none; min-height:38px; overflow-y:hidden;"></textarea>
            <button class="btn btn-primary" type="submit">Send</button>
        </form>
        <div class="d-flex align-items-center gap-2">
            <div class="form-check">
                <input type="checkbox" class="form-check-input" id="auto-retry" checked>
                <label class="form-check-label" for="auto-retry">Retry sending output if connection is lost</label>
            </div>
        </div>
    </div>
    <div class="basa-disclaimer-bar w-100 bg-light text-center fw-lighter py-2 small text-muted" style="position: sticky; bottom: 0; left: 0; z-index: 9; border-top: 1px solid #e0e0e0;">
        BASA can make mistakes in providing information. Check carefully.
    </div>
    <div id="processing-files" class="mb-2"></div>
</div>

<!-- File Selection Modal -->
<div class="modal fade" id="fileSelectionModal" tabindex="-1" aria-labelledby="fileSelectionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="fileSelectionModalLabel">
                    <i class="bi bi-folder2-open me-2"></i>Select Files for Context
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Search and Filter Bar -->
                <div class="row mb-3">
                    <div class="col-md-8">
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-search"></i></span>
                            <input type="text" class="form-control" id="fileSearchInput" placeholder="Search files...">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <select class="form-select" id="fileTypeFilter">
                            <option value="">All Types</option>
                            <option value="system">System Documents</option>
                            <option value="user">User Uploads</option>
                        </select>
                    </div>
                </div>
                
                <!-- File Count and Selection Info -->
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <span class="badge bg-primary" id="totalFilesCount">0 files</span>
                        <span class="badge bg-success ms-2" id="selectedFilesCount">0 selected</span>
                    </div>
                    <div>
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="selectAllBtn">Select All</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary ms-1" id="clearAllBtn">Clear All</button>
                    </div>
                </div>
                
                <!-- Files List -->
                <div id="fileSelectionContainer" class="border rounded" style="max-height: 400px; overflow-y: auto;">
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2 text-muted">Loading available files...</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="applyFileSelectionBtn">
                    <i class="bi bi-check-circle me-1"></i>Apply Selection
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
const chatLog = document.getElementById('chat-log');
const chatForm = document.getElementById('chat-form');
const messageInput = document.getElementById('message');
const autoRetry = document.getElementById('auto-retry');
const filePillContainer = document.getElementById('file-pill-container');
const processingFilesDiv = document.getElementById('processing-files');

// File selection modal variables
const fileSelectionModal = document.getElementById('fileSelectionModal');
const openFileSelectorBtn = document.getElementById('openFileSelectorBtn');
const fileSelectionContainer = document.getElementById('fileSelectionContainer');
const fileSearchInput = document.getElementById('fileSearchInput');
const fileTypeFilter = document.getElementById('fileTypeFilter');
const totalFilesCount = document.getElementById('totalFilesCount');
const selectedFilesCount = document.getElementById('selectedFilesCount');
const selectAllBtn = document.getElementById('selectAllBtn');
const clearAllBtn = document.getElementById('clearAllBtn');
const applyFileSelectionBtn = document.getElementById('applyFileSelectionBtn');

let selectedFiles = new Set(); // Track selected files for RAG context
let allAvailableFiles = []; // Store all available files for filtering

function appendAttachmentPreview(msgDiv, attachment) {
    if (attachment) {
        const previewDiv = document.createElement('div');
        previewDiv.innerHTML = renderAttachmentPreview(attachment);
        msgDiv.appendChild(previewDiv);
    }
}

function addMessage(text, sender, isError = false, retryCallback = null, isLoading = false, type = null, attachment = null) {
    // Detect error type for bot
    const isBotError = (type === 'error') || (isError) || (sender === 'bot' && typeof text === 'string' && text.match(/^I\'m sorry, but I cannot access the webpage|Sorry, I could not fetch|not allowed to access this page|content is not accessible/i));
    
    const msgDiv = document.createElement('div');
    msgDiv.className = 'chat-message';
    const avatar = document.createElement('div');
    avatar.className = 'chat-avatar ' + sender;
    avatar.textContent = sender === 'user' ? 'You' : 'Bot';

    // Attachment preview (above message)
    appendAttachmentPreview(msgDiv, attachment);

    const content = document.createElement('div');
    if (isBotError) {
        content.className = 'alert alert-danger';
    } else {
        content.className = 'chat-content ' + sender;
    }
    content.innerHTML = '';
    if (isLoading) {
        content.innerHTML = '<span class="loading-dots"><span></span><span></span><span></span></span>';
    } else if (isError && retryCallback) {
        content.textContent = text + ' ';
        const retryBtn = document.createElement('button');
        retryBtn.textContent = 'Try again';
        retryBtn.className = 'btn btn-link btn-sm p-0 ms-2';
        retryBtn.onclick = retryCallback;
        content.appendChild(retryBtn);
    } else {
        // Render paragraphs, preserving line breaks
        text.split(/\n{2,}/).forEach((para, idx, arr) => {
            const p = document.createElement('p');
            p.style.marginBottom = idx < arr.length - 1 ? '1em' : '0';
            p.textContent = para.trim();
            content.appendChild(p);
        });
        
        // Add action buttons for bot responses (not for errors or loading states)
        if (sender === 'bot' && !isError && !isLoading && text.trim()) {
            const actionButtons = document.createElement('div');
            actionButtons.className = 'mt-2 d-flex gap-2';
            actionButtons.style.fontSize = '0.85em';
            
            // Copy to clipboard button
            const copyBtn = document.createElement('button');
            copyBtn.className = 'btn btn-outline-secondary btn-sm';
            copyBtn.innerHTML = '<i class="bi bi-clipboard me-1"></i>Copy';
            copyBtn.onclick = function() {
                navigator.clipboard.writeText(text).then(() => {
                    // Show success feedback
                    const originalText = copyBtn.innerHTML;
                    copyBtn.innerHTML = '<i class="bi bi-check me-1"></i>Copied!';
                    copyBtn.className = 'btn btn-success btn-sm';
                    setTimeout(() => {
                        copyBtn.innerHTML = originalText;
                        copyBtn.className = 'btn btn-outline-secondary btn-sm';
                    }, 2000);
                }).catch(err => {
                    console.error('Failed to copy text: ', err);
                    // Fallback for older browsers
                    const textArea = document.createElement('textarea');
                    textArea.value = text;
                    document.body.appendChild(textArea);
                    textArea.select();
                    document.execCommand('copy');
                    document.body.removeChild(textArea);
                    
                    const originalText = copyBtn.innerHTML;
                    copyBtn.innerHTML = '<i class="bi bi-check me-1"></i>Copied!';
                    copyBtn.className = 'btn btn-success btn-sm';
                    setTimeout(() => {
                        copyBtn.innerHTML = originalText;
                        copyBtn.className = 'btn btn-outline-secondary btn-sm';
                    }, 2000);
                });
            };
            
            // Regenerate button
            const regenerateBtn = document.createElement('button');
            regenerateBtn.className = 'btn btn-outline-primary btn-sm';
            regenerateBtn.innerHTML = '<i class="bi bi-arrow-clockwise me-1"></i>Regenerate';
            regenerateBtn.onclick = function() {
                // Find the user message that preceded this bot response
                const messages = chatLog.querySelectorAll('.chat-message');
                let userMessage = null;
                
                // Look backwards from current message to find the most recent user message
                for (let i = messages.length - 1; i >= 0; i--) {
                    if (messages[i].querySelector('.chat-avatar.user')) {
                        userMessage = messages[i].querySelector('.chat-content.user').textContent;
                        break;
                    }
                }
                
                if (userMessage) {
                    // Remove the current bot response
                    chatLog.removeChild(msgDiv);
                    // Send the same user message again
                    handleBotReply(userMessage);
                } else {
                    console.error('Could not find user message to regenerate');
                }
            };
            
            actionButtons.appendChild(copyBtn);
            actionButtons.appendChild(regenerateBtn);
            content.appendChild(actionButtons);
        }
    }
    msgDiv.appendChild(avatar);
    msgDiv.appendChild(content);
    chatLog.appendChild(msgDiv);
    chatLog.scrollTop = chatLog.scrollHeight;
}

async function sendMessage(msg, attachment = null) {
    addMessage('', 'bot', false, null, true, null, attachment);
    let resp;
    try {
        resp = await fetch('/chatbot/ask', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({ 
                message: msg,
                selected_files: Array.from(selectedFiles) // Include selected files for RAG context
            })
        });
    } catch (e) {
        resp = null;
    }
    // Remove the loading message
    chatLog.removeChild(chatLog.lastChild);
    if (resp && resp.ok) {
        try {
            const data = await resp.json();
            
            // Validate response structure
            if (!data || typeof data !== 'object') {
                throw new Error('Invalid response format from server');
            }
            
            if (!data.reply) {
                throw new Error('No reply field in response');
            }
            
            let message = data.reply;
            let type = data.type || null;
            
            // Add RAG information if used and debug is enabled
            if (data.rag_used && data.debug_enabled) {
                try {
                    let files = Array.isArray(data.rag_files) ? data.rag_files.join(', ') : '';
                    let chunksFound = typeof data.rag_chunks_found === 'number' ? data.rag_chunks_found : 'unknown';
                    message += '\n\n[Response based on ' + chunksFound + ' chunks from: ' + files + ']';
                    console.log('RAG used:', data);
                } catch (ragError) {
                    console.error('Error processing RAG data:', ragError);
                    message += '\n\n[Response enhanced with uploaded documents]';
                }
            } else if (data.rag_used) {
                // RAG was used but debug is disabled - just log to console
                console.log('RAG used but debug disabled - not showing indicator');
            } else {
                console.log('RAG not used - no relevant content found');
            }
            
            addMessage(message, 'bot', false, null, false, type);
        } catch (parseError) {
            console.error('Error parsing response:', parseError);
            addMessage('Error: Received invalid response from server. Please try again.', 'bot', true, () => {
                sendMessage(msg);
            });
            if (autoRetry.checked) {
                setTimeout(() => sendMessage(msg), 1000);
            }
        }
    } else {
        addMessage('Error: Could not get response.', 'bot', true, () => {
            sendMessage(msg);
        });
        if (autoRetry.checked) {
            setTimeout(() => sendMessage(msg), 1000);
        }
    }
}

async function streamMessage(msg, attachment = null) {
    let retryCallback = () => streamMessage(msg, attachment);
    addMessage('', 'bot', false, null, true, null, attachment);
    const response = await fetch('/chatbot/stream', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({ 
            message: msg,
            selected_files: Array.from(selectedFiles) // Include selected files for RAG context
        })
    });
    chatLog.removeChild(chatLog.lastChild);

    // Handle JSON error response before streaming
    const contentType = response.headers.get('content-type');
    if (!response.ok || (contentType && contentType.includes('application/json'))) {
        try {
            const data = await response.json();
            if (data && data.type === 'error') {
                addMessage(data.reply, 'bot', false, null, false, 'error', attachment);
                return;
            }
            if (data && data.reply) {
                addMessage(data.reply, 'bot', false, null, false, null, attachment);
                return;
            }
        } catch (e) {
            addMessage('Error: Could not get response.', 'bot', true, retryCallback, false, null, attachment);
            return;
        }
    }

    if (!response.body) {
        addMessage('Error: Could not get response.', 'bot', true, retryCallback, false, null, attachment);
        if (autoRetry.checked) {
            setTimeout(() => streamMessage(msg, attachment), 1000);
        }
        return;
    }
    const reader = response.body.getReader();
    const decoder = new TextDecoder();
    let result = '';
    let done, value;
    let hadError = false;
    try {
        while (true) {
            ({ done, value } = await reader.read());
            if (done) break;
            const chunk = decoder.decode(value, { stream: true });
            result += chunk;
            // Update the bot message in the UI
            if (chatLog.lastChild && chatLog.lastChild.classList.contains('chat-message') && chatLog.lastChild.querySelector('.chat-avatar.bot')) {
                const contentDiv = chatLog.lastChild.querySelector('.chat-content.bot');
                // Clear existing content and add paragraphs
                contentDiv.innerHTML = '';
                result.split(/\n{2,}/).forEach((para, idx, arr) => {
                    const p = document.createElement('p');
                    p.style.marginBottom = idx < arr.length - 1 ? '1em' : '0';
                    p.textContent = para.trim();
                    contentDiv.appendChild(p);
                });
            } else {
                // If for some reason the last message is not the bot, add a new one
                addMessage(result, 'bot', false, null, false, null, attachment);
            }
            chatLog.scrollTop = chatLog.scrollHeight;
        }
        if (!result.trim()) {
            addMessage('No response from model.', 'bot', true, retryCallback, false, null, attachment);
            if (autoRetry.checked) {
                setTimeout(() => streamMessage(msg, attachment), 1000);
            }
        } else {
            // Finalize the message with addMessage to standardize formatting and add buttons
            chatLog.removeChild(chatLog.lastChild);
            addMessage(result, 'bot', false, null, false, null, attachment);
        }
    } catch (err) {
        hadError = true;
        addMessage('Error: Could not get response.', 'bot', true, retryCallback, false, null, attachment);
        if (autoRetry.checked) {
            setTimeout(() => streamMessage(msg, attachment), 1000);
        }
    }
}

async function handleBotReply(msg, attachment = null) {
    // Ask backend if streaming is enabled
    const resp = await fetch('/chatbot/streaming-enabled', { method: 'GET' });
    let isStreaming = false;
    if (resp.ok) {
        const data = await resp.json();
        isStreaming = !!data.streaming;
    }
    if (isStreaming) {
        await streamMessage(msg, attachment);
    } else {
        await sendMessage(msg, attachment);
    }
}

// Auto-expand textarea height on input
messageInput.addEventListener('input', function() {
    this.style.height = 'auto';
    this.style.height = (this.scrollHeight) + 'px';
});

// Handle Enter/Shift+Enter for textarea
messageInput.addEventListener('keydown', function(e) {
    if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        chatForm.dispatchEvent(new Event('submit', { cancelable: true }));
    }
});

chatForm.addEventListener('submit', function(e) {
    e.preventDefault();
    const msg = messageInput.value.trim();
    const attachment = window.currentAttachment;
    if (!msg && !attachment) return;
    addMessage(msg, 'user', false, null, false, null, attachment);
    messageInput.value = '';
    window.currentAttachment = null;
    document.getElementById('file-pill-container').innerHTML = '';
    document.getElementById('file-pill-container').style.display = 'none';
    // Use streaming or fallback to normal reply
    handleBotReply(msg, attachment);
});

document.getElementById('test-multiline').addEventListener('click', function() {
    addMessage(
        'This is a test of multi-line bot output.\n\nHere is a new paragraph.\nLine two of the same paragraph.\n\nAnother paragraph follows.\n\n- Bullet 1\n- Bullet 2',
        'bot'
    );
});

// Attachments dropdown logic
const urlAttachInput = document.getElementById('url-attach');
const attachUrlBtn = document.getElementById('attachUrlBtn');
const fileAttachInput = document.getElementById('file-attach');
const attachFileBtn = document.getElementById('attachFileBtn');

attachUrlBtn && attachUrlBtn.addEventListener('click', function() {
    if (urlAttachInput && urlAttachInput.value.trim()) {
        messageInput.value = urlAttachInput.value.trim() + (messageInput.value ? ('\n' + messageInput.value) : '');
        urlAttachInput.value = '';
        // Optionally close dropdown
        document.body.click(); // closes any open dropdown
        messageInput.focus();
    }
});

attachFileBtn && attachFileBtn.addEventListener('click', function() {
    if (fileAttachInput && fileAttachInput.files.length > 0) {
        const file = fileAttachInput.files[0];
        showFileProcessing(file.name);
        showFilePill(file);
        processingFiles.add(file.name); // Track this file for polling
        
        const formData = new FormData();
        formData.append('file', file);
        const msg = messageInput.value.trim();
        if (msg) {
            formData.append('message', msg);
        }
        let uploadTimedOut = false;
        const uploadTimeout = setTimeout(() => {
            uploadTimedOut = true;
            clearFilePill();
            removeFileProcessing(file.name);
            processingFiles.delete(file.name);
            addMessage('Error: File upload timed out. Please try again.', 'bot', true);
        }, 60000); // 60s timeout
        fetch('/chatbot/upload', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: formData
        })
        .then(response => {
            clearTimeout(uploadTimeout);
            if (uploadTimedOut) return;
            if (!response.ok) throw new Error('Upload failed');
            return response.json();
        })
        .then(data => {
            if (uploadTimedOut) return;
            addMessage(data.reply, 'bot');
            clearFilePill();
            // Don't remove from processingFiles - let polling handle the status
        })
        .catch(() => {
            clearTimeout(uploadTimeout);
            if (uploadTimedOut) return;
            addMessage('Error: Could not upload or process the file.', 'bot', true);
            clearFilePill();
            removeFileProcessing(file.name);
            processingFiles.delete(file.name);
        });
        fileAttachInput.value = '';
        document.body.click();
        messageInput.focus();
    }
});

function showFilePill(file) {
    filePillContainer.innerHTML = '';
    const pill = document.createElement('span');
    pill.className = 'badge rounded-pill bg-info text-dark d-inline-flex align-items-center px-3 py-2';
    pill.style.fontSize = '1em';
    pill.textContent = file.name;
    const removeBtn = document.createElement('button');
    removeBtn.type = 'button';
    removeBtn.className = 'btn-close ms-2';
    removeBtn.style.fontSize = '0.9em';
    removeBtn.setAttribute('aria-label', 'Remove');
    removeBtn.onclick = function() {
        clearFilePill();
        fileAttachInput.value = '';
    };
    pill.appendChild(removeBtn);
    filePillContainer.appendChild(pill);
    filePillContainer.style.display = '';
}

function clearFilePill() {
    filePillContainer.innerHTML = '';
    filePillContainer.style.display = 'none';
}

// Polling-based file processing status (no WebSocket required)

function showFileProcessing(fileName) {
    let div = document.createElement('div');
    div.className = 'alert alert-info d-flex align-items-center mb-2';
    div.id = 'processing-' + fileName;
    div.innerHTML = `<span class="spinner-border spinner-border-sm me-2"></span>Processing&nbsp;<strong>${fileName}</strong>...`;
    processingFilesDiv.appendChild(div);
}

function showFileProcessed(fileName) {
    let div = document.getElementById('processing-' + fileName);
    if (div) {
        div.className = 'alert alert-success d-flex align-items-center mb-2';
        div.innerHTML = `<span class="bi bi-check-circle-fill text-success me-2"></span>File <strong>${fileName}</strong> is ready for use.`;
        setTimeout(() => div.remove(), 5000);
    }
}

function showFileFailed(fileName, errorMsg) {
    let div = document.getElementById('processing-' + fileName);
    if (div) div.remove();
    let errorDiv = document.createElement('div');
    errorDiv.className = 'alert alert-danger d-flex align-items-center mb-2';
    errorDiv.innerHTML = `<span class="bi bi-x-circle-fill text-danger me-2"></span>File <strong>${fileName}</strong> failed to process: <span class="ms-1">${errorMsg}</span>`;
    processingFilesDiv.appendChild(errorDiv);
    setTimeout(() => errorDiv.remove(), 10000);
}

function removeFileProcessing(fileName) {
    let div = document.getElementById('processing-' + fileName);
    if (div) div.remove();
}

// Simple polling for file processing status (no WebSocket required)
let processingFiles = new Set();
let pollingInterval;

function startFileProcessingPolling() {
    if (pollingInterval) return;
    
    pollingInterval = setInterval(() => {
        if (processingFiles.size === 0) return;
        
        console.log('Polling for files:', Array.from(processingFiles));
        
        // Check processing status via AJAX
        fetch('/chatbot/processing-status', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                files: Array.from(processingFiles)
            })
        })
        .then(response => response.json())
        .then(data => {
            console.log('Polling response:', data);
            data.forEach(file => {
                console.log('Processing file:', file.fileName, 'Status:', file.status);
                if (file.status === 'completed') {
                    console.log('File completed:', file.fileName);
                    showFileProcessed(file.fileName);
                    processingFiles.delete(file.fileName);
                    // Check RAG status after file completion
                    checkRagStatus();
                } else if (file.status === 'failed') {
                    console.log('File failed:', file.fileName, file.error);
                    showFileFailed(file.fileName, file.error || 'Processing failed');
                    processingFiles.delete(file.fileName);
                } else if (file.status === 'processing') {
                    console.log('File still processing:', file.fileName);
                    // Keep showing the processing status, don't remove from processingFiles
                    // The existing showFileProcessing() call will continue to show
                }
            });
        })
        .catch(error => {
            console.log('Polling error:', error);
        });
    }, 5000); // Poll every 5 seconds
}

function stopFileProcessingPolling() {
    if (pollingInterval) {
        clearInterval(pollingInterval);
        pollingInterval = null;
    }
}

// Check RAG status and show indicator
function checkRagStatus() {
    fetch('/chatbot/rag-info')
        .then(response => response.json())
        .then(data => {
            console.log('RAG Status:', data);
            if (data.rag_enabled) {
                showRagIndicator(data.available_files.length);
            } else {
                hideRagIndicator();
            }
        })
        .catch(error => {
            console.log('Error checking RAG status:', error);
        });
}

function showRagIndicator(fileCount) {
    let indicator = document.getElementById('rag-indicator');
    if (!indicator) {
        indicator = document.createElement('div');
        indicator.id = 'rag-indicator';
        indicator.className = 'alert alert-success d-flex align-items-center mb-2';
        indicator.innerHTML = `<span class="bi bi-file-earmark-text me-2"></span>RAG Enabled: ${fileCount} file(s) available for enhanced responses`;
        document.querySelector('.chat-container').insertBefore(indicator, document.querySelector('.chat-log'));
    } else {
        indicator.innerHTML = `<span class="bi bi-file-earmark-text me-2"></span>RAG Enabled: ${fileCount} file(s) available for enhanced responses`;
        indicator.style.display = 'block';
    }
}

function hideRagIndicator() {
    let indicator = document.getElementById('rag-indicator');
    if (indicator) {
        indicator.style.display = 'none';
    }
}

// Global error handler for uncaught JavaScript errors
window.addEventListener('error', function(event) {
    console.error('Uncaught JavaScript error:', event.error);
    addMessage('Error: An unexpected error occurred. Please refresh the page and try again.', 'bot', true);
});

// Global handler for unhandled promise rejections
window.addEventListener('unhandledrejection', function(event) {
    console.error('Unhandled promise rejection:', event.reason);
    addMessage('Error: A network or processing error occurred. Please try again.', 'bot', true);
});

// File selection modal functions
function openFileSelector() {
    loadAvailableFiles();
    const modal = new bootstrap.Modal(fileSelectionModal);
    modal.show();
}

function loadAvailableFiles() {
    fileSelectionContainer.innerHTML = `
        <div class="text-center py-4">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2 text-muted">Loading available files...</p>
        </div>
    `;
    
    fetch('/chatbot/available-files')
        .then(response => response.json())
        .then(data => {
            allAvailableFiles = data.files || [];
            renderFileSelection(allAvailableFiles);
            updateFileCounts();
        })
        .catch(error => {
            console.error('Error loading files:', error);
            fileSelectionContainer.innerHTML = `
                <div class="text-center py-4">
                    <i class="bi bi-exclamation-triangle text-danger" style="font-size: 2rem;"></i>
                    <p class="mt-2 text-danger">Error loading files. Please try again.</p>
                </div>
            `;
        });
}

function renderFileSelection(files) {
    fileSelectionContainer.innerHTML = '';
    
    if (files.length === 0) {
        fileSelectionContainer.innerHTML = `
            <div class="text-center py-4">
                <i class="bi bi-folder-x text-muted" style="font-size: 2rem;"></i>
                <p class="mt-2 text-muted">No files available. Upload some files first.</p>
            </div>
        `;
        return;
    }
    
    files.forEach(file => {
        const fileDiv = document.createElement('div');
        fileDiv.className = 'file-item p-3 border-bottom';
        fileDiv.style.cursor = 'pointer';
        fileDiv.onclick = function(e) {
            // Don't trigger if clicking on the checkbox itself
            if (e.target.type === 'checkbox') {
                return;
            }
            // Don't trigger if clicking on the close button or other interactive elements
            if (e.target.tagName === 'BUTTON' || e.target.closest('button')) {
                return;
            }
            const checkbox = this.querySelector('input[type="checkbox"]');
            checkbox.checked = !checkbox.checked;
            checkbox.dispatchEvent(new Event('change'));
        };
        
        const checkbox = document.createElement('input');
        checkbox.type = 'checkbox';
        checkbox.className = 'form-check-input me-3';
        checkbox.id = 'file-' + file.name.replace(/[^a-zA-Z0-9]/g, '_');
        checkbox.value = file.name;
        checkbox.checked = selectedFiles.has(file.name);
        checkbox.addEventListener('change', function(e) {
            e.stopPropagation();
            const fileName = this.value;
            
            if (this.checked) {
                selectedFiles.add(fileName);
                console.log('Added file:', fileName, 'Total selected:', selectedFiles.size);
            } else {
                selectedFiles.delete(fileName);
                console.log('Removed file:', fileName, 'Total selected:', selectedFiles.size);
            }
            
            updateSelectedFilesDisplay();
            updateFileCounts();
        });
        
        const badge = file.is_system_document ? 
            '<span class="badge bg-primary me-2">System</span>' : 
            '<span class="badge bg-secondary me-2">User</span>';
        
        const fileIcon = file.is_system_document ? 'bi-shield-check' : 'bi-file-earmark-text';
        
        // Append the checkbox first
        fileDiv.appendChild(checkbox);
        
        // Create the content div
        const contentDiv = document.createElement('div');
        contentDiv.className = 'd-flex align-items-center';
        contentDiv.innerHTML = `
            <i class="bi ${fileIcon} me-2 ${file.is_system_document ? 'text-primary' : 'text-secondary'}"></i>
            <div class="flex-grow-1">
                <div class="d-flex align-items-center mb-1">
                    ${badge}
                    <strong class="file-name">${file.name}</strong>
                </div>
                <div class="text-muted small">
                    <i class="bi bi-file-earmark me-1"></i>${file.file_type}
                    <span class="mx-2">•</span>
                    <i class="bi bi-collection me-1"></i>${file.chunk_count} chunks
                    <span class="mx-2">•</span>
                    <i class="bi bi-hdd me-1"></i>${file.file_size}
                </div>
            </div>
        `;
        
        fileDiv.appendChild(contentDiv);
        fileSelectionContainer.appendChild(fileDiv);
    });
    
    updateSelectedFilesDisplay();
}

function updateSelectedFilesDisplay() {
    // Update the file pill container to show selected files
    if (!filePillContainer) return;
    
    filePillContainer.innerHTML = '';
    
    if (selectedFiles.size > 0) {
        selectedFiles.forEach(fileName => {
            const pill = document.createElement('span');
            pill.className = 'badge rounded-pill bg-success text-white d-inline-flex align-items-center px-3 py-2 me-2 mb-2';
            pill.style.fontSize = '0.9em';
            pill.textContent = fileName;
            
            const removeBtn = document.createElement('button');
            removeBtn.type = 'button';
            removeBtn.className = 'btn-close btn-close-white ms-2';
            removeBtn.style.fontSize = '0.8em';
            removeBtn.setAttribute('aria-label', 'Remove');
            removeBtn.onclick = function() {
                selectedFiles.delete(fileName);
                updateSelectedFilesDisplay();
                updateFileCounts();
                // Update checkbox state in modal
                const checkbox = document.getElementById('file-' + fileName.replace(/[^a-zA-Z0-9]/g, '_'));
                if (checkbox) checkbox.checked = false;
            };
            
            pill.appendChild(removeBtn);
            filePillContainer.appendChild(pill);
        });
        
        filePillContainer.style.display = 'block';
        console.log('Updated file pills - showing', selectedFiles.size, 'files');
    } else {
        filePillContainer.style.display = 'none';
        console.log('Updated file pills - none selected');
    }
}

function updateFileCounts() {
    if (totalFilesCount) {
        totalFilesCount.textContent = `${allAvailableFiles.length} files`;
    }
    if (selectedFilesCount) {
        selectedFilesCount.textContent = `${selectedFiles.size} selected`;
    }
    console.log('Updated file counts - Total:', allAvailableFiles.length, 'Selected:', selectedFiles.size);
}

function filterFiles() {
    const searchTerm = fileSearchInput.value.toLowerCase();
    const typeFilter = fileTypeFilter.value;
    
    const filteredFiles = allAvailableFiles.filter(file => {
        const matchesSearch = file.name.toLowerCase().includes(searchTerm);
        const matchesType = !typeFilter || 
            (typeFilter === 'system' && file.is_system_document) ||
            (typeFilter === 'user' && !file.is_system_document);
        
        return matchesSearch && matchesType;
    });
    
    renderFileSelection(filteredFiles);
}

function selectAllFiles() {
    const visibleCheckboxes = fileSelectionContainer.querySelectorAll('input[type="checkbox"]');
    visibleCheckboxes.forEach(checkbox => {
        checkbox.checked = true;
        selectedFiles.add(checkbox.value);
    });
    updateSelectedFilesDisplay();
    updateFileCounts();
    console.log('Select All - Total selected:', selectedFiles.size);
}

function clearAllFiles() {
    const visibleCheckboxes = fileSelectionContainer.querySelectorAll('input[type="checkbox"]');
    visibleCheckboxes.forEach(checkbox => {
        checkbox.checked = false;
        selectedFiles.delete(checkbox.value);
    });
    updateSelectedFilesDisplay();
    updateFileCounts();
    console.log('Clear All - Total selected:', selectedFiles.size);
}

// Add event listeners for file selection modal
openFileSelectorBtn && openFileSelectorBtn.addEventListener('click', function() {
    openFileSelector();
});

fileSearchInput && fileSearchInput.addEventListener('input', function() {
    filterFiles();
});

fileTypeFilter && fileTypeFilter.addEventListener('change', function() {
    filterFiles();
});

selectAllBtn && selectAllBtn.addEventListener('click', function() {
    selectAllFiles();
});

clearAllBtn && clearAllBtn.addEventListener('click', function() {
    clearAllFiles();
});

applyFileSelectionBtn && applyFileSelectionBtn.addEventListener('click', function() {
    const modal = bootstrap.Modal.getInstance(fileSelectionModal);
    modal.hide();
    updateSelectedFilesDisplay();
});

// Start polling when page loads
document.addEventListener('DOMContentLoaded', function() {
    startFileProcessingPolling();
    checkRagStatus(); // Check RAG status on page load
});
</script>
@endsection