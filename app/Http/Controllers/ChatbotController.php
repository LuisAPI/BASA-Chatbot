<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use fivefilters\Readability\Readability;
use fivefilters\Readability\Configuration;

class ChatbotController extends Controller
{
    public function index()
    {
        return view('chatbot');
    }

    public function ask(Request $request)
    {
        $systemPrompt = <<<EOT
        You are a helpful assistant for employees of the Department of Economy, Planning and Development (DEPDev), an executive department of the national government of the Republic of the Philippines.

        The DEPDev, formerly known as the National Economic and Development Authority (NEDA), is the country’s premier socioeconomic planning body, highly regarded as the authority in macroeconomic forecasting and policy analysis and research. It provides high-level advice to policymakers in Congress and the Executive Branch.
        
        DEPDev's key responsibilities include:
        a. Coordination of activities such as the formulation of policies, plans, and programs to efficiently set the broad parameters for national and sub-national (area-wide, regional, and local development);
        b. Review, evaluation, and monitoring of infrastructure projects identified under the Comprehensive and Integrated Infrastructure Program consistent with the government’s thrust of increasing investment spending for the growing demand on quality infrastructure facilities; and
        c. Undertaking of short-term policy reviews to provide critical analyses of development issues and policy alternatives to decision-makers.

        Vision:
        DEPDev envisions a country where public and private sectors perform their respective roles efficiently, such that people have equal access to opportunities, resulting in inclusive development and zero poverty.

        Mission:
        DEPDev’s mission is to formulate continuing, coordinated, and fully-integrated socioeconomic policies, plans, and programs to enable and empower every Filipino to enjoy a matatag, maginhawa, at panatag na buhay.

        Core Values
        DEPDev’s core values are Integrity, Professionalism, and Excellence.

        Your role is to assist users by providing accurate and relevant information about the agency's functions, services, and government structure. You should:
        - Provide clear and concise answers to user inquiries.
        - Offer information about the agency's programs, services, and initiatives.
        - Explain the agency's role within the national government.
        - Clarify the agency's functions and responsibilities.
        - Guide users on how to access services or information.
        - Answer questions about the agency's structure, including its divisions and key personnel.
        - Provide information about the agency's policies and procedures.

        You should not:
        - Provide personal opinions or unverified information.
        - Engage in discussions unrelated to the agency's functions or government structure.
        - Offer legal or financial advice.
        - Discuss sensitive or confidential information.

        Always answer user questions about the agency's functions and government structure in well-written, properly capitalized, and clearly formatted paragraphs. Use professional language and correct grammar.
        EOT;
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

        // Check if the user message contains a URL (with or without a question)
        $url = null;
        $question = null;
        if (preg_match('/(https?:\/\/[^\s]+)/i', $userMessage, $matches)) {
            $url = $matches[1];
            $question = trim(str_replace($url, '', $userMessage));
            if (empty($question)) {
                $question = 'Summarize the following webpage.';
            }
            // Check robots.txt before fetching
            $parsedUrl = parse_url($url);
            $robotsUrl = $parsedUrl['scheme'] . '://' . $parsedUrl['host'] . '/robots.txt';
            try {
                $robotsResponse = Http::timeout(5)->get($robotsUrl);
                $robotsTxt = $robotsResponse->successful() ? $robotsResponse->body() : '';
                $isAllowed = true;
                if ($robotsTxt) {
                    $lines = preg_split('/\r?\n/', $robotsTxt);
                    $userAgent = false;
                    foreach ($lines as $line) {
                        $line = trim($line);
                        if (stripos($line, 'User-agent:') === 0) {
                            $userAgent = (stripos($line, 'User-agent: *') === 0);
                        } elseif ($userAgent && stripos($line, 'Disallow:') === 0) {
                            $disallowedPath = trim(substr($line, 9));
                            if ($disallowedPath && strpos($parsedUrl['path'] ?? '/', $disallowedPath) === 0) {
                                $isAllowed = false;
                                break;
                            }
                        } elseif (stripos($line, 'User-agent:') === 0) {
                            $userAgent = false;
                        }
                    }
                }
                if (!$isAllowed) {
                    return response()->json(['reply' => 'Sorry, I am not allowed to access this page due to the site\'s robots.txt rules.']);
                }
            } catch (\Exception $e) {
                // If robots.txt fails, proceed (fail open, but could be changed to fail closed)
            }
            try {
                $webResponse = Http::timeout(10)
                    ->withHeaders(['User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36'])
                    ->get($url);
                $html = $webResponse->body();
                $readability = new Readability(new Configuration());
                $readability->parse($html);
                $content = $readability->getContent();
                $title = $readability->getTitle();
                $content = strip_tags($content);
                if (strlen($content) > 4000) {
                    $content = substr($content, 0, 1000) . '... [truncated]';
                }
                $userMessage = $question . " Title: $title. Content: $content";
            } catch (\Exception $e) {
                return response()->json(['reply' => 'Sorry, I could not fetch or process the webpage.']);
            }
        }

        // Prepare the full prompt for the model
        // $fullPrompt = $systemPrompt . "\n\nUser: " . $userMessage . "\nAssistant:";
        $fullPrompt = $systemPrompt . "\nUser: " . $userMessage;

        // Add placeholder for assistant response
        $responsePlaceholder = "\nAssistant: ";

        // Send the request to the model API using the system prompt field
        $response = Http::timeout(60)->post('http://127.0.0.1:11434/api/generate', [
            'model' => 'tinyllama',
            'system' => $systemPrompt,
            'prompt' => $userMessage,
            'stream' => false
        ]);

        $data = $response->json();

        $botReply = $data['response'] ?? 'No response from model.';

        // Post-process the response: remove 'Assistant:' prefix, capitalize sentences, and ensure paragraph structure
        // 1. Remove 'Assistant:' prefix (case-insensitive, with or without space)
        $botReply = preg_replace('/^\s*Assistant:\s*/i', '', $botReply);

        // 2. Capitalize first letter of each sentence (basic approach)
        $botReply = preg_replace_callback('/([.!?]\s+|^)([a-z])/', function ($matches) {
            return $matches[1] . strtoupper($matches[2]);
        }, $botReply);

        // 3. Ensure paragraph structure: replace double newlines or long runs of whitespace with paragraph breaks
        $botReply = preg_replace("/(\r?\n){2,}/", "\n\n", $botReply);
        $botReply = trim($botReply);

        return response()->json(['reply' => $botReply]);
    }
}
