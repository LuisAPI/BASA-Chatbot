@extends('layouts.app')

@section('title', 'Bot for Automated Semantic Assistance')

@section('head')
    <style>
        body {
            background: url('/images/BP-DEPDev%20Zoom%20Background.jpg') no-repeat center center fixed;
            background-size: cover;
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
                <ul class="dropdown-menu" aria-labelledby="attachmentDropdown" style="min-width: 260px;">
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
                            <label for="file-attach" class="form-label mb-1">Upload File</label>
                            <input type="file" class="form-control form-control-sm" id="file-attach" accept=".txt,.pdf,.doc,.docx,.rtf,.odt,.csv,.xlsx,.xls">
                            <button class="btn btn-sm btn-primary mt-2 w-100" type="button" id="attachFileBtn">Attach File</button>
                        </div>
                    </li>
                </ul>
            </div>
            <textarea id="message" class="form-control" placeholder="Type your question..." autocomplete="off" required rows="1" style="resize:none; min-height:38px; overflow-y:hidden;"></textarea>
            <button class="btn btn-primary" type="submit">Send</button>
        </form>
        <div class="d-flex align-items-center gap-2">
            <input type="checkbox" id="auto-retry" style="width:auto;" checked>
            <label for="auto-retry" class="mb-0">Retry sending output if connection is lost</label>
        </div>
    </div>
    <div class="basa-disclaimer-bar w-100 bg-light text-center py-2 small text-muted" style="position: sticky; bottom: 0; left: 0; z-index: 9; border-top: 1px solid #e0e0e0;">
        BASA can make mistakes in providing information. Please check carefully.
    </div>
    <div id="processing-files" class="mb-2"></div>
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

function addMessage(text, sender, isError = false, retryCallback = null, isLoading = false) {
    const msgDiv = document.createElement('div');
    msgDiv.className = 'chat-message';
    const avatar = document.createElement('div');
    avatar.className = 'chat-avatar ' + sender;
    avatar.textContent = sender === 'user' ? 'You' : 'Bot';
    const content = document.createElement('div');
    content.className = 'chat-content ' + sender;
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
    }
    msgDiv.appendChild(avatar);
    msgDiv.appendChild(content);
    chatLog.appendChild(msgDiv);
    chatLog.scrollTop = chatLog.scrollHeight;
}

async function sendMessage(msg) {
    addMessage('', 'bot', false, null, true); // show loading dots
    let resp;
    try {
        resp = await fetch('/chatbot/ask', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({ message: msg })
        });
    } catch (e) {
        resp = null;
    }
    // Remove the loading message
    chatLog.removeChild(chatLog.lastChild);
    if (resp && resp.ok) {
        const data = await resp.json();
        addMessage(data.reply, 'bot');
    } else {
        addMessage('Error: Could not get response.', 'bot', true, () => {
            sendMessage(msg);
        });
        if (autoRetry.checked) {
            setTimeout(() => sendMessage(msg), 1000);
        }
    }
}

async function streamMessage(msg) {
    // Add a placeholder bot message to the chat UI and get its DOM element
    const msgDiv = document.createElement('div');
    msgDiv.className = 'chat-message';
    const avatar = document.createElement('div');
    avatar.className = 'chat-avatar bot';
    avatar.textContent = 'Bot';
    const content = document.createElement('div');
    content.className = 'chat-content bot';
    content.innerHTML = '<span class="loading-dots"><span></span><span></span><span></span></span>';
    msgDiv.appendChild(avatar);
    msgDiv.appendChild(content);
    chatLog.appendChild(msgDiv);
    chatLog.scrollTop = chatLog.scrollHeight;

    const response = await fetch('/chatbot/stream', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({ message: msg })
    });

    if (!response.ok || !response.body) {
        content.textContent = 'Error: Could not get response.';
        return;
    }

    const reader = response.body.getReader();
    const decoder = new TextDecoder();
    let result = '';
    let done, value;
    content.innerHTML = '';
    try {
        while (true) {
            ({ done, value } = await reader.read());
            if (done) break;
            const chunk = decoder.decode(value, { stream: true });
            result += chunk;
            content.textContent = result;
            chatLog.scrollTop = chatLog.scrollHeight;
        }
        if (!result.trim()) {
            content.textContent = 'No response from model.';
        }
    } catch (err) {
        content.textContent = 'Error: Could not get response.';
    }
}

async function handleBotReply(msg) {
    // Ask backend if streaming is enabled
    const resp = await fetch('/chatbot/streaming-enabled', { method: 'GET' });
    let isStreaming = false;
    if (resp.ok) {
        const data = await resp.json();
        isStreaming = !!data.streaming;
    }
    if (isStreaming) {
        await streamMessage(msg);
    } else {
        await sendMessage(msg);
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
    if (!msg) return;
    addMessage(msg, 'user');
    messageInput.value = '';
    // Use streaming or fallback to normal reply
    handleBotReply(msg);
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
        // Show file pill UI
        showFilePill(file);
        const formData = new FormData();
        formData.append('file', file);
        // Optionally, add the message as well
        const msg = messageInput.value.trim();
        if (msg) {
            formData.append('message', msg);
        }
        fetch('/chatbot/upload', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            addMessage(data.reply, 'bot');
            clearFilePill();
        })
        .catch(() => {
            addMessage('Error: Could not upload or process the file.', 'bot', true);
            clearFilePill();
        });
        fileAttachInput.value = '';
        document.body.click(); // closes any open dropdown
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

// Listen for file processing events (using Laravel Echo)
if (window.Echo) {
    Echo.channel('files')
        .listen('.FileProcessed', (e) => {
            showFileProcessed(e.fileName);
        });
}

function showFileProcessing(fileName) {
    let div = document.createElement('div');
    div.className = 'alert alert-info d-flex align-items-center mb-2';
    div.id = 'processing-' + fileName;
    div.innerHTML = `<span class="spinner-border spinner-border-sm me-2"></span>Processing <strong>${fileName}</strong>...`;
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
</script>
@endsection