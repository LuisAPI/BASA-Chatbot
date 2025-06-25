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
        $userMessage = $request->input('message');
        $response = Http::post('http://localhost:11434/api/generate', [
            'model' => 'tinyllama',
            'prompt' => $userMessage,
            'stream' => false
        ]);
        $data = $response->json();
        $botReply = $data['response'] ?? 'No response from model.';
        return response()->json(['reply' => $botReply]);
    }
}
