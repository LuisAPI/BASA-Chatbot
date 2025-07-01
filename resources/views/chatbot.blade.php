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
</div>
@endsection

@section('scripts')
<script>
const chatLog = document.getElementById('chat-log');
const chatForm = document.getElementById('chat-form');
const messageInput = document.getElementById('message');
const autoRetry = document.getElementById('auto-retry');

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
    sendMessage(msg);
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

attachUrlBtn && attachUrlBtn.addEventListener('click', function() {
    if (urlAttachInput && urlAttachInput.value.trim()) {
        messageInput.value = urlAttachInput.value.trim() + (messageInput.value ? ('\n' + messageInput.value) : '');
        urlAttachInput.value = '';
        // Optionally close dropdown
        document.body.click(); // closes any open dropdown
        messageInput.focus();
    }
});
</script>
@endsection