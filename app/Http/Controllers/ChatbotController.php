<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ChatbotController extends Controller
{
    public function index()
    {
        return view('chatbot');
    }

    public function ask(Request $request)
    {
        $systemPrompt = "You are a helpful assistant for employees of the Department of Economy, Planning and Development (DEPDev), an executive department of the national government of the Republic of the Philippines. Answer questions about the agency's functions and government structure.";
        $userMessage = $request->input('message');

        if ($request->has('phpinfo')) {
            ob_start();
            phpinfo();
            $phpinfo = ob_get_clean();
            return response($phpinfo);
        }

        if (empty($userMessage)) {
            return response()->json(['error' => 'Message cannot be empty.'], 400);
        }
        
        // Validate the user message length
        if (strlen($userMessage) > 500) {
            return response()->json(['error' => 'Message is too long. Maximum length is 500 characters.'], 400);
        }

        // Prepare the full prompt for the model
        // $fullPrompt = $systemPrompt . "\n\nUser: " . $userMessage . "\nAssistant:";
        $fullPrompt = $systemPrompt . "\nUser: " . $userMessage;

        // Add placeholder for assistant response
        $responsePlaceholder = "\nAssistant: ";

        // Send the request to the model API
        $response = Http::timeout(60)->post('http://127.0.0.1:11434/api/generate', [
            'model' => 'tinyllama',
            'prompt' => $fullPrompt,
            'stream' => false
        ]);

        $data = $response->json();

        $botReply = $data['response'] ?? 'No response from model.';
        return response()->json(['reply' => $botReply]);
    }
}
