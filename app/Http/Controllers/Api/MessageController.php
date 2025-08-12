<?php

namespace App\Http\Controllers\Api;

//use App\Http\Controllers\Controller;
use App\Models\UserProfile;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class MessageController extends Controller
{
    public function getConversations(Request $request): JsonResponse
    {
        // In real app, get user from token
        $user = UserProfile::where('username', 'demo')->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        $conversations = DB::table('matches')
            ->where(function ($query) use ($user) {
                $query->where('user1_id', $user->user_id)
                      ->orWhere('user2_id', $user->user_id);
            })
            ->where('is_active', true)
            ->get()
            ->map(function ($match) use ($user) {
                $otherUserId = $match->user1_id === $user->user_id ? $match->user2_id : $match->user1_id;
                $otherUser = UserProfile::where('user_id', $otherUserId)->first();

                $lastMessage = Message::where('match_id', $match->id)
                    ->orderBy('created_at', 'desc')
                    ->first();

                $unreadCount = Message::where('match_id', $match->id)
                    ->where('receiver_id', $user->user_id)
                    ->whereNull('read_at')
                    ->count();

                return [
                    'match_id' => $match->id,
                    'user' => $otherUser,
                    'last_message' => $lastMessage,
                    'unread_count' => $unreadCount,
                    'matched_at' => $match->matched_at,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $conversations
        ]);
    }

    public function getMessages(Request $request, $matchId): JsonResponse
    {
        // In real app, get user from token and verify they're part of this match
        $user = UserProfile::where('username', 'demo')->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        $messages = Message::where('match_id', $matchId)
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $messages
        ]);
    }

    public function sendMessage(Request $request, $matchId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'content' => 'required|string|max:1000',
            'type' => 'nullable|in:text,image,gif',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // In real app, get user from token
        $user = UserProfile::where('username', 'demo')->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        // Get match and determine receiver
        $match = DB::table('matches')->where('id', $matchId)->first();

        if (!$match) {
            return response()->json([
                'success' => false,
                'message' => 'Match not found'
            ], 404);
        }

        $receiverId = $match->user1_id === $user->user_id ? $match->user2_id : $match->user1_id;

        $message = Message::create([
            'match_id' => $matchId,
            'sender_id' => $user->user_id,
            'receiver_id' => $receiverId,
            'content' => $request->content,
            'type' => $request->type ?? 'text',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Message sent successfully',
            'data' => $message
        ]);
    }

    public function markAsRead(Request $request, $messageId): JsonResponse
    {
        // In real app, get user from token
        $user = UserProfile::where('username', 'demo')->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        $message = Message::where('id', $messageId)
            ->where('receiver_id', $user->user_id)
            ->first();

        if (!$message) {
            return response()->json([
                'success' => false,
                'message' => 'Message not found'
            ], 404);
        }

        $message->markAsRead();

        return response()->json([
            'success' => true,
            'message' => 'Message marked as read'
        ]);
    }
}
