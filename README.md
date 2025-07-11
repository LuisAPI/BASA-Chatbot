# BASA: Bot for Automated Semantic Assistance

BASA is an internal chatbot for the Philippine Department of Economy, Planning and Development (DEPDev), built with Laravel and powered by local LLMs via Ollama. It provides professional, context-aware answers to agency-related questions, and features a modern, user-friendly chat interface.

## Features
- Modern, responsive chat UI (ChatGPT-style)
- File upload and document parsing for PDF, DOCX, XLSX, and other formats
- Retrieval-Augmented Generation (RAG) powered by `nomic-embed-text` for semantic chunk search
- Enhanced file gallery with static previews and grid/list view toggle
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

### Option A: Quick Setup (Recommended)

For a complete automated setup:

```sh
# 1. Clone and install dependencies
git clone https://luisapi-admin@bitbucket.org/luisapi/basa-chatbot.git
cd basa-chatbot
composer install
npm install

# 2. Environment configuration
cp .env.example .env
# Edit .env to configure your settings

# 3. Run comprehensive setup
php artisan setup:basa
```

The `setup:basa` command will automatically:
- Check database connection
- Run migrations
- Set up queue tables
- Generate application key
- Process default government documents
- Clear application caches
- Create required directories

**Options:**
- `--skip-documents` - Skip processing default documents
- `--force` - Force reprocessing of existing documents

### Option B: Step-by-Step Setup

If the automated setup fails or you prefer manual control:

#### 1. Clone and install dependencies
```sh
git clone https://luisapi-admin@bitbucket.org/luisapi/basa-chatbot.git
cd basa-chatbot
composer install
npm install
```

#### 2. Environment configuration

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

#### 3. Database setup

```sh
php artisan migrate
```

#### 4. Optional: Enable file processing queue

```sh
php artisan queue:table
php artisan migrate
php artisan queue:work
```

#### 5. Process default documents (Optional)

```sh
php artisan documents:process-default
```

This processes bundled government documents for the RAG system.

#### 6. Start servers

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

### Ollama Connection Management

BASA includes a comprehensive Ollama connection management system:

#### Check Connection Status
```sh
# Basic status check
php artisan ollama:status

# Detailed connection information
php artisan ollama:status --detailed
```

#### Configuration Options
The system supports multiple Ollama endpoints and auto-discovery:

**Environment Variables:**
```env
OLLAMA_ENDPOINT=http://localhost:11434
OLLAMA_CORPORATE_ENDPOINT=https://ollama.depdev.gov.ph
OLLAMA_AUTO_DISCOVER=true
OLLAMA_TIMEOUT=30
OLLAMA_RETRY_ATTEMPTS=3
```

**Features:**
- **Auto-discovery**: Automatically finds Ollama instances on the network
- **Fallback endpoints**: Multiple endpoint support with automatic failover
- **User preferences**: Save and remember preferred endpoints
- **Connection testing**: Built-in endpoint validation
- **Graceful degradation**: Handles connection failures gracefully

Then access: [http://localhost:8080/chatbot](http://localhost:8080/chatbot)

## Default Government Documents

BASA comes bundled with essential government documents that provide the core knowledge base for the chatbot. These documents are automatically available in every instance without requiring manual uploads.

### Location
Default documents are stored in `public/documents/` and are version-controlled with the application.

### Directory Structure
Documents can be organized in subdirectories for better organization:
```
public/documents/
├── pdp/          # Philippine Development Plans
├── oc/           # Office Circulars
├── policies/     # Government Policies
└── README.md
```

### Included Documents
- **Philippine Development Plan (PDP) 2023-2028** - The country's medium-term development plan
- Other government policy documents and reference materials

### Processing Default Documents
To process the bundled documents for the RAG system:

```sh
# Process all default documents
php artisan documents:process-default

# Force reprocessing of existing documents
php artisan documents:process-default --force
```

### Adding New Default Documents
1. Place PDF files in `public/documents/`
2. Run `php artisan documents:process-default`
3. Documents are immediately available in the chatbot

### Visual Indicators
In the File Gallery, system documents are marked with:
- Blue border and header in Grid view
- "System" badge
- Shield icon instead of file icon
- Highlighted row in List view

## File Gallery

The enhanced File Gallery provides a Google Drive-style interface for managing uploaded documents:

### Features
- **Static Previews**: Each file shows a preview of its content (first 200 characters)
- **View Toggle**: Switch between Grid view (cards) and List view (table)
- **File Information**: File type, size, upload date, and chunk count
- **System Documents**: Visual distinction between user uploads and bundled documents
- **Responsive Design**: Works on desktop and mobile devices
- **Hover Effects**: Interactive file cards with smooth animations

### Access
Navigate to the File Gallery via:
- Sidebar menu: "File Gallery" link
- Direct URL: `http://localhost:8080/chatbot/files`

### Usage
1. **Grid View** (default): Card-based layout showing file previews
2. **List View**: Table format with all file details in columns
3. **View Chunks**: Click "View Chunks" to see detailed file content
4. **File Details**: Hover over cards to see additional information
5. **System Documents**: Blue-highlighted cards indicate bundled government documents

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