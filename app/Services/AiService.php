<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiService
{
    private string $geminiApiKey;
    private string $groqApiKey;

    public function __construct()
    {
        $this->geminiApiKey = config('services.gemini.api_key', env('GEMINI_API_KEY', ''));
        $this->groqApiKey = config('services.groq.api_key', env('GROQ_API_KEY', ''));
    }

    /**
     * Send a prompt to Google Gemini and return generated text.
     */
    public function generateResponse(string $userMessage, array $conversationHistory = [], array $attachments = [], string $model = 'gemini-2.5-flash'): string
    {
        // Dynamic Routing: Detect provider from model name
        if (str_starts_with($model, 'gemini')) {
            return $this->handleGemini($model, $userMessage, $conversationHistory, $attachments);
        }
        
        // Otherwise try Groq (Llama, DeepSeek, Mixtral)
        return $this->handleGroq($model, $userMessage, $conversationHistory, $attachments);
    }

    private function handleGemini(string $model, string $userMessage, array $conversationHistory, array $attachments): string
    {
        if (empty($this->geminiApiKey)) {
            return $this->simulateResponse($userMessage);
        }

        try {
            return $this->callGemini($model, $userMessage, $conversationHistory, $attachments);
        } catch (\Exception $e) {
            Log::warning("Gemini model {$model} failed, trying fallback", ['error' => $e->getMessage()]);
            // Emergency fallback to a known stable model if the requested one fails
            $stableGemini = 'gemini-2.0-flash-lite';
            if ($model === $stableGemini) throw $e; 
            return $this->callGemini($stableGemini, $userMessage, $conversationHistory, $attachments);
        }
    }

    private function handleGroq(string $model, string $userMessage, array $conversationHistory, array $attachments): string
    {
        if (empty($this->groqApiKey)) {
            return "Please configure `GROQ_API_KEY` in your `.env` file to use {$model}.";
        }

        try {
            return $this->callGroq($model, $userMessage, $conversationHistory, $attachments);
        } catch (\Exception $e) {
            Log::error("Groq API Error for model {$model}: " . $e->getMessage());
            return "Groq Error: " . $e->getMessage();
        }
    }

    /**
     * Call a specific Gemini model.
     */
    private function callGemini(string $model, string $userMessage, array $conversationHistory, array $attachments): string
    {
        // Build conversation contents for Gemini
        $contents = [];

        // System Instruction - For stable v1, we inject it into the first message or as context
        $systemPrompt = "You are a senior full-stack developer and AI assistant. Explain concepts clearly with code examples. Use markdown. Be concise and professional.";

        // Add conversation history
        foreach (array_slice($conversationHistory, -20) as $msg) {
            $contents[] = [
                'role' => $msg['role'] === 'assistant' ? 'model' : 'user',
                'parts' => [['text' => $msg['content']]],
            ];
        }

        // Add current user message with system context if it's the first message
        $userParts = [];
        if (empty($contents)) {
            $userParts[] = ['text' => "Instructions: {$systemPrompt}\n\nUser Question: {$userMessage}"];
        } else {
            $userParts[] = ['text' => $userMessage];
        }

        // Add attachments
        foreach ($attachments as $att) {
            if ($att['isImage']) {
                $filePath = public_path($att['path']);
                if (file_exists($filePath)) {
                    $userParts[] = [
                        'inline_data' => [
                            'mime_type' => $att['type'],
                            'data' => base64_encode(file_get_contents($filePath))
                        ]
                    ];
                }
            }
        }

        $contents[] = [
            'role' => 'user',
            'parts' => $userParts,
        ];

        $url = "https://generativelanguage.googleapis.com/v1/models/{$model}:generateContent?key={$this->geminiApiKey}";

        $response = Http::withHeaders(['Content-Type' => 'application/json'])
            ->timeout(60)
            ->post($url, [
                'contents' => $contents,
                'generationConfig' => [
                    'temperature' => 0.7,
                    'maxOutputTokens' => 4096,
                ],
            ]);
        // ... handled below ...
        if ($response->failed()) {
            throw new \Exception("Gemini API Error: " . $response->body());
        }

        $data = $response->json();
        return $data['candidates'][0]['content']['parts'][0]['text'] ?? 'Empty response from Gemini';
    }

    /**
     * Call Groq API
     */
    private function callGroq(string $model, string $userMessage, array $conversationHistory, array $attachments = []): string
    {
        $messages = [];
        $messages[] = ['role' => 'system', 'content' => "You are a senior full-stack developer and AI assistant. Be concise and professional."];
        
        foreach (array_slice($conversationHistory, -10) as $msg) {
            $messages[] = [
                'role' => $msg['role'] === 'assistant' ? 'assistant' : 'user',
                'content' => $msg['content']
            ];
        }

        // Primary Model Mapping (Cleanest IDs for the API)
        $modelMap = [
            'llama-3.3-70b' => 'llama-3.3-70b-versatile',
            'llama-3.1-8b'  => 'llama-3.1-8b-instant',
            'llama-3.2-11b' => 'llama-3.2-11b-vision-preview',
        ];

        // Ensure we always have a clean ID or fallback for any decommissioned model
        if (in_array($model, ['mixtral-8x7b', 'mixtral-8x7b-32768', 'gemma-7b', 'gemma2-9b-it', 'deepseek-r1', 'llama-3-70b'])) {
             $model = 'llama-3.3-70b'; // Primary stable fallback
        }
        
        // Final ID selection
        $groqModel = $modelMap[$model] ?? $model;

        // Check if it's a vision task
        $hasImages = !empty(array_filter($attachments, fn($a) => $a['isImage'] ?? false));
        
        if ($hasImages && str_contains($groqModel, 'vision')) {
            $content = [['type' => 'text', 'text' => $userMessage]];
            foreach ($attachments as $att) {
                if ($att['isImage']) {
                    $filePath = public_path($att['path']);
                    if (file_exists($filePath)) {
                        $mimeType = $att['type'] ?? 'image/jpeg';
                        $base64 = base64_encode(file_get_contents($filePath));
                        $content[] = [
                            'type' => 'image_url',
                            'image_url' => [
                                'url' => "data:{$mimeType};base64,{$base64}"
                            ]
                        ];
                    }
                }
            }
            $messages[] = ['role' => 'user', 'content' => $content];
        } else {
            $messages[] = ['role' => 'user', 'content' => $userMessage];
        }

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->groqApiKey,
            'Content-Type' => 'application/json',
        ])->post('https://api.groq.com/openai/v1/chat/completions', [
            'model' => $groqModel,
            'messages' => $messages,
            'temperature' => 0.7,
            'max_tokens' => 4096,
        ]);

        if ($response->failed()) {
            throw new \Exception("Groq API Error: " . $response->body());
        }

        return $response->json()['choices'][0]['message']['content'] ?? 'Empty response from Groq';
    }

    /**
     * Simulate an AI response for demo / no-API-key mode.
     */
    private function simulateResponse(string $userMessage): string
    {
        $lowered = strtolower($userMessage);

        if (str_contains($lowered, 'hello') || str_contains($lowered, 'hi')) {
            return "Hello! 👋 I'm your AI assistant powered by Gemini Flash.\n\nHow can I help you today? I can assist with:\n\n- **Coding Questions** — Any programming language\n- **Code Reviews** — Paste your code and I'll review it\n- **Debugging** — Describe your error and I'll help\n- **Architecture** — System design & best practices\n\nJust type your question! 🚀";
        }

        if (str_contains($lowered, 'help')) {
            return "## What I Can Do 🤖\n\n| Feature | Description |\n|---------|-------------|\n| 💻 **Code Help** | Write, review, and explain code |\n| 🐛 **Debugging** | Find and fix bugs |\n| 📐 **Architecture** | Design patterns & best practices |\n| 📚 **Learning** | Explain concepts with examples |\n\n> **Tip:** Be specific in your questions for the best answers!\n\nJust type your question and press Enter.";
        }

        return "This is a **demo response** since no Gemini API key is configured.\n\nTo enable real AI responses:\n\n```env\n# Add to your .env file\nGEMINI_API_KEY=your-api-key-here\n```\n\n### How to get a free API key:\n\n1. Visit [Google AI Studio](https://aistudio.google.com)\n2. Sign in with your Google account\n3. Click **\"Get API Key\"**\n4. Copy and paste it into your `.env` file\n5. Restart your server\n\n> 💡 **Gemini 2.0 Flash** is completely free with generous rate limits!";
    }
}
