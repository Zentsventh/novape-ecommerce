<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Chatbot\ChatbotMessageRequest;
use App\Services\Chatbot\ChatbotService;
use Illuminate\Http\JsonResponse;

class ChatbotController extends Controller
{
    public function __construct(
        private readonly ChatbotService $chatbotService
    ) {}

    public function message(ChatbotMessageRequest $request): JsonResponse
    {
        try {
            $reply = $this->chatbotService->getReply($request->input('messages'));
            
            return response()->json([
                'success' => true,
                'reply' => $reply
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
