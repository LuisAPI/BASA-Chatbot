# BASA: Bot for Automated Semantic Assistance

BASA is an internal chatbot for the Philippine Department of Economy, Planning and Development (DEPDev), built with Laravel and powered by local LLMs via Ollama. It provides professional, context-aware answers to agency-related questions, and features a modern, user-friendly chat interface.

## Features
- Modern, responsive chat UI (ChatGPT-style)
- Multi-paragraph and multi-line support
- Animated loading indicator
- Retry and auto-retry on connection loss
- System prompt and context injection for accurate, professional answers

## Getting Started
1. Clone the repository and install dependencies:
   ```sh
   composer install
   npm install
   ```
2. Copy `.env.example` to `.env` and configure as needed.
3. Start the Laravel server:
   ```sh
   php artisan serve --port=8080
   ```
4. Start Ollama and ensure your chosen model (e.g., phi, tinyllama) is available.
5. Access BASA at [http://localhost:8080/chatbot](http://localhost:8080/chatbot)

## About
BASA (Bot for Automated Semantic Assistance) is designed for internal use by DEPDev employees, providing fast, reliable, and contextually relevant answers about the agency's functions, services, and structure.

---

*This project is based on Laravel. For more information, see the [Laravel documentation](https://laravel.com/docs).*
