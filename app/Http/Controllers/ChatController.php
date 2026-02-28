<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Message;
use App\Services\AiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ChatController extends Controller
{
    public function __construct(
        private readonly AiService $aiService
    ) {}

    /**
     * Show the chat interface with history.
     */
    public function index(Request $request, ?Conversation $conversation = null): Response
    {
        $sessionId = $request->session()->getId();

        // Get all conversations for the sidebar
        $conversations = Conversation::where('session_id', $sessionId)
            ->latest()
            ->get(['id', 'title', 'created_at']);

        // Load messages for the active conversation or empty array
        $messages = [];
        if ($conversation && $conversation->session_id === $sessionId) {
            $messages = $conversation->messages()->get()
                ->map(fn(Message $msg) => [
                    'id' => $msg->id,
                    'role' => $msg->role,
                    'content' => $msg->content,
                    'attachments' => $msg->formatted_attachments,
                    'timestamp' => $msg->created_at->format('g:i A'),
                ]);
        }

        return Inertia::render('Chat', [
            'conversations' => $conversations,
            'activeConversationId' => $conversation?->id,
            'messages' => $messages,
        ]);
    }

    /**
     * Start a new chat session.
     */
    public function create(Request $request): JsonResponse
    {
        $sessionId = $request->session()->getId();

        $conversation = Conversation::create([
            'session_id' => $sessionId,
            'title' => 'New Chat',
        ]);

        return response()->json([
            'success' => true,
            'conversation' => $conversation
        ]);
    }

    /**
     * Handle a chat message and return AI response.
     */
    public function send(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'message' => 'required|string|max:5000',
            'conversation_id' => 'nullable|exists:conversations,id',
            'model' => 'nullable|string',
            'files.*' => 'nullable|file|max:10240', // 10MB limit
        ]);

        $sessionId = $request->session()->getId();
        $conversationId = $validated['conversation_id'] ?? null;

        // If no conversation_id, create a new one
        if (!$conversationId) {
            $conversation = Conversation::create([
                'session_id' => $sessionId,
                'title' => substr($validated['message'], 0, 30) . '...',
            ]);
            $conversationId = $conversation->id;
        } else {
            $conversation = Conversation::find($conversationId);
            // Verify session
            if ($conversation->session_id !== $sessionId) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            // Update title if it's still 'New Chat'
            if ($conversation->title === 'New Chat') {
                $conversation->update([
                    'title' => substr($validated['message'], 0, 30) . '...',
                ]);
            }
        }

        // Handle file uploads
        $attachments = [];
        if ($request->hasFile('files')) {
            foreach ($request->file('files') as $file) {
                $path = $file->store('attachments', 'public');
                $attachments[] = [
                    'name' => $file->getClientOriginalName(),
                    'path' => '/storage/' . $path,
                    'type' => $file->getMimeType(),
                    'isImage' => str_starts_with($file->getMimeType(), 'image/'),
                ];
            }
        }

        // Store user message
        $userMessage = Message::create([
            'conversation_id' => $conversationId,
            'session_id' => $sessionId,
            'role' => 'user',
            'content' => $validated['message'],
            'attachments' => $attachments,
        ]);

        // Get history for context
        $history = Message::where('conversation_id', $conversationId)
            ->orderBy('created_at', 'asc')
            ->get(['role', 'content'])
            ->toArray();

        try {
            $aiResponse = $this->aiService->generateResponse(
                $validated['message'],
                $history,
                $attachments,
                $validated['model'] ?? 'gemini-2.5-flash'
            );

            // Store AI response
            $assistantMessage = Message::create([
                'conversation_id' => $conversationId,
                'session_id' => $sessionId,
                'role' => 'assistant',
                'content' => $aiResponse,
            ]);

            return response()->json([
                'success' => true,
                'conversation_id' => $conversationId,
                'title' => $conversation->title,
                'message' => [
                    'id' => $assistantMessage->id,
                    'role' => 'assistant',
                    'content' => $aiResponse,
                    'attachments' => $assistantMessage->formatted_attachments,
                    'timestamp' => $assistantMessage->created_at->format('g:i A'),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Delete a conversation.
     */
    public function destroy(Request $request, Conversation $conversation): JsonResponse
    {
        if ($conversation->session_id !== $request->session()->getId()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $conversation->messages()->delete();
        $conversation->delete();

        return response()->json(['success' => true]);
    }
    /**
     * Delete all conversations for the session.
     */
    public function destroyAll(Request $request): JsonResponse
    {
        $sessionId = $request->session()->getId();
        
        $conversations = Conversation::where('session_id', $sessionId)->get();
        foreach ($conversations as $conversation) {
            $conversation->messages()->delete();
            $conversation->delete();
        }

        return response()->json(['success' => true]);
    }
}
