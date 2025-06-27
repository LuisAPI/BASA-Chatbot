<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>DEPDev Chatbot</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f8fafc; }
        .chat-container { max-width: 600px; margin: 40px auto; background: #fff; border-radius: 8px; box-shadow: 0 2px 8px #0001; padding: 24px; }
        .chat-message { display: flex; gap: 12px; margin-bottom: 18px; }
        .chat-avatar { width: 36px; height: 36px; border-radius: 50%; background: #e3f2fd; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 1.1em; color: #1976d2; }
        .chat-avatar.bot { background: #e8f5e9; color: #388e3c; }
        .chat-content { background: #f4f6f8; border-radius: 8px; padding: 12px 16px; white-space: pre-line; font-size: 1.05em; flex: 1; }
        .chat-content.user { background: #e3f2fd; }
        .chat-content.bot { background: #e8f5e9; }
        .chat-log { display: flex; flex-direction: column; gap: 0; min-height: 200px; margin-bottom: 16px; }
    </style>
</head>
<body>
<div class="chat-container">
    <h3 class="mb-3">DEPDev Chatbot</h3>
    <div id="chat-log" class="chat-log"></div>
    <form id="chat-form" class="d-flex gap-2">
        <textarea id="message" class="form-control" placeholder="Type your question..." autocomplete="off" required rows="1" style="resize:none; min-height:38px; overflow-y:hidden;"></textarea>
        <button class="btn btn-primary" type="submit">Send</button>
    </form>
    <div class="d-flex align-items-center gap-2 mt-2">
        <input type="checkbox" id="auto-retry" style="width:auto;">
        <label for="auto-retry" class="mb-0">Retry sending output if connection is lost</label>
        <button id="test-multiline" class="btn btn-secondary btn-sm ms-auto" type="button">Test multiline bot reply</button>
    </div>
</div>
<script>
const chatLog = document.getElementById('chat-log');
const chatForm = document.getElementById('chat-form');
const messageInput = document.getElementById('message');
const autoRetry = document.getElementById('auto-retry');

function addMessage(text, sender, isError = false, retryCallback = null) {
    const msgDiv = document.createElement('div');
    msgDiv.className = 'chat-message';
    const avatar = document.createElement('div');
    avatar.className = 'chat-avatar ' + sender;
    avatar.textContent = sender === 'user' ? 'You' : 'Bot';
    const content = document.createElement('div');
    content.className = 'chat-content ' + sender;
    content.innerHTML = '';
    if (isError && retryCallback) {
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
    addMessage('...', 'bot');
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
</script>
</body>
</html>
