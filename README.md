# BASA: Bot for Automated Semantic Assistance

BASA is an internal chatbot for the Philippine Department of Economy, Planning and Development (DEPDev), built with Laravel and powered by local LLMs via Ollama. It provides professional, context-aware answers to agency-related questions, and features a modern, user-friendly chat interface.

## Features
- Modern, responsive chat UI (ChatGPT-style)
- File upload and document parsing for PDF, DOCX, XLSX, and other formats
- Retrieval-Augmented Generation (RAG) powered by `nomic-embed-text` for semantic chunk search
- Animated loading indicator and retry on failure
- Auto-streaming support for real-time token responses
- Background queue processing for large documents
- Real-time notifications when file processing completes (via Redis broadcasting)
- System prompt and context injection for accurate, professional answers

## Supported File Types
- `.pdf` (via `smalot/pdfparser`)
- `.docx`, `.doc`, `.odt`, `.rtf` (via `phpoffice/phpword`)
- `.xlsx`, `.xls`, `.csv` (via `phpoffice/phpspreadsheet`)
- `.txt` and similar plaintext files

## Getting Started

### 1. Clone and install dependencies
```sh
git clone https://luisapi-admin@bitbucket.org/luisapi/basa-chatbot.git
cd basa-chatbot
composer install
npm install
```

### 2. Environment configuration

```sh
cp .env.example .env
php artisan key:generate
```

Then edit `.env` to configure:

* Laravel app (`APP_URL`, `APP_DEBUG`, etc.)
* Database connection (default: SQLite)
* Queue driver (`QUEUE_CONNECTION=database`)
* LLM model (`LLM_MODEL=tinyllama` or `phi`, etc.)
* Ollama endpoint (`http://localhost:11434`)
* Broadcasting driver (`BROADCAST_DRIVER=redis`)

### 3. Database setup

```sh
php artisan migrate
```

### 4. Optional: Enable file processing queue

```sh
php artisan queue:table
php artisan migrate
php artisan queue:work
```

### 5. Start servers

**Option A: Use the start script (Windows)**
```sh
.\start-all.ps1
```

**Option B: Start manually**
```sh
# Terminal 1: Laravel development server
php artisan serve --port=8080

# Terminal 2: Queue worker
php artisan queue:work

# Terminal 3: Vite development server
npm run dev
```

- `php artisan serve --port=8080` starts the Laravel web server.
- `php artisan queue:work` processes background jobs for file processing.
- `npm run dev` starts the Vite development server for frontend assets.

Start Ollama and make sure both your chat model and embedding model are installed and running:

```sh
ollama run tinyllama
ollama pull nomic-embed-text
```

> `tinyllama` or similar is used for generating responses.
> `nomic-embed-text` is used for vector embedding in RAG.

Then access: [http://localhost:8080/chatbot](http://localhost:8080/chatbot)

## Real-time Broadcasting

BASA uses database broadcasting with polling for file processing notifications. When files are uploaded and processed in the background, users receive near real-time notifications about the processing status through AJAX polling.

### Broadcasting Configuration

The broadcasting is configured to use database as the driver:
- **Driver**: Database (SQLite)
- **Method**: AJAX polling (every 2 seconds)
- **Events**: `FileProcessed` and `FileFailed` events

### How It Works

1. **File Upload** → Laravel processes file in background queue
2. **Processing Complete** → `FileProcessed` event dispatched and stored in `broadcasts` table
3. **Frontend Polling** → JavaScript polls `/chatbot/processing-status` every 2 seconds
4. **Status Update** → UI updates with processing status

### Troubleshooting Broadcasting

If file processing notifications aren't working:

1. **Check database**: Ensure the `broadcasts` table exists and is accessible
2. **Check browser console**: Look for AJAX polling errors
3. **Verify event dispatch**: Check Laravel logs for event broadcasting errors
4. **Check queue worker**: Ensure `php artisan queue:work` is running

## Composer Dependencies

These packages were added for file parsing and RAG support:

| Package                                                                   | Description                                         |
| ------------------------------------------------------------------------- | --------------------------------------------------- |
| [`smalot/pdfparser`](https://github.com/smalot/pdfparser)                 | Extracts text content from PDF files                |
| [`phpoffice/phpword`](https://github.com/PHPOffice/PHPWord)               | Reads `.doc`, `.docx`, `.odt`, and `.rtf` documents |
| [`phpoffice/phpspreadsheet`](https://github.com/PHPOffice/PhpSpreadsheet) | Parses `.xlsx`, `.xls`, `.csv` spreadsheet files    |
| [`predis/predis`](https://github.com/predis/predis)                       | Redis client for broadcasting                       |
| [`laravel/horizon`](https://laravel.com/docs/horizon) *(optional)*        | (Optional) Queue dashboard for Redis (if used)      |

> These allow BASA to process uploaded office documents and extract meaningful context for chunking and embedding.

## About

BASA (Bot for Automated Semantic Assistance) is designed for internal use by DEPDev employees, providing fast, reliable, and contextually relevant answers about the agency's functions, services, and structure. It leverages local language models via Ollama and Laravel's elegant ecosystem.

---

*This project is built with Laravel. For more information, visit the [Laravel documentation](https://laravel.com/docs).*