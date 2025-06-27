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
        .chat-bubble { padding: 12px 18px; border-radius: 18px; margin-bottom: 10px; }
        .user { background: #e3f2fd; align-self: flex-end; }
        .bot { background: #e8f5e9; align-self: flex-start; }
        .chat-log { display: flex; flex-direction: column; gap: 8px; min-height: 200px; margin-bottom: 16px; }
    </style>
</head>
<body>
<div class="chat-container">
    <h3 class="mb-3">DEPDev Chatbot</h3>
    <div id="chat-log" class="chat-log"></div>
    <form id="chat-form" class="d-flex gap-2">
        <input type="text" id="message" class="form-control" placeholder="Type your question..." autocomplete="off" required>
        <button class="btn btn-primary" type="submit">Send</button>
    </form>
    <div class="d-flex align-items-center gap-2 mt-2">
        <input type="checkbox" id="auto-retry" style="width:auto;">
        <label for="auto-retry" class="mb-0">Retry sending output if connection is lost</label>
    </div>
</div>
<script>
const chatLog = document.getElementById('chat-log');
const chatForm = document.getElementById('chat-form');
const messageInput = document.getElementById('message');
const autoRetry = document.getElementById('auto-retry');

function addMessage(text, sender, isError = false, retryCallback = null) {
    const div = document.createElement('div');
    div.className = 'chat-bubble ' + sender;
    div.textContent = text;
    if (isError && retryCallback) {
        const retryBtn = document.createElement('button');
        retryBtn.textContent = 'Try again';
        retryBtn.className = 'btn btn-link btn-sm p-0 ms-2';
        retryBtn.onclick = retryCallback;
        div.appendChild(retryBtn);
    }
    chatLog.appendChild(div);
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
    chatLog.lastChild.textContent = '...';
    if (resp && resp.ok) {
        const data = await resp.json();
        chatLog.lastChild.textContent = data.reply;
    } else {
        chatLog.removeChild(chatLog.lastChild);
        addMessage('Error: Could not get response.', 'bot', true, () => {
            sendMessage(msg);
        });
        if (autoRetry.checked) {
            setTimeout(() => sendMessage(msg), 1000);
        }
    }
}

chatForm.addEventListener('submit', function(e) {
    e.preventDefault();
    const msg = messageInput.value.trim();
    if (!msg) return;
    addMessage(msg, 'user');
    messageInput.value = '';
    sendMessage(msg);
});
</script>
</body>
</html>
