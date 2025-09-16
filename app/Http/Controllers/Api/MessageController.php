<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Models\UserProfile;
use App\Models\Message;
use App\Models\Matcher;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class MessageController extends Controller
{
    public function getConversations(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated'
            ], 401);
        }

        $conversations = DB::table('matches')
            ->where(function ($query) use ($user) {
                $query->where('user1_id', $user->id)
                      ->orWhere('user2_id', $user->id);
            })
            ->where('is_active', true)
            ->orderBy('matched_at', 'desc')
            ->get()
            ->map(function ($match) use ($user) {
                // Get the other user's ID
                $otherUserId = $match->user1_id === $user->id ? $match->user2_id : $match->user1_id;

                // Load the other user with photos
                $otherUser = User::with(['photos' => function($query) {
                    $query->orderBy('order', 'asc');
                }])->find($otherUserId);

                if (!$otherUser) {
                    return null;
                }

                // Get last message
                $lastMessage = Message::where('match_id', $match->id)
                    ->orderBy('created_at', 'desc')
                    ->first();

                // Get unread message count
                $unreadCount = Message::where('match_id', $match->id)
                    ->where('receiver_id', $user->id)
                    ->whereNull('read_at')
                    ->count();

                // Calculate age
                $age = $otherUser->date_of_birth ? Carbon::parse($otherUser->date_of_birth)->age : null;

                // Get photos
                $photos = $otherUser->photos->pluck('photo_url')->toArray();
                $avatar = $photos[0] ?? null;

                // Check if user is online (last active within 15 minutes)
                $isOnline = $otherUser->last_active_at &&
                           Carbon::parse($otherUser->last_active_at)->gt(Carbon::now()->subMinutes(15));

                return [
                    'id' => $match->id,
                    'match_id' => $match->id,
                    'user' => [
                        'id' => $otherUser->id,
                        'first_name' => $otherUser->first_name,
                        'last_name' => $otherUser->last_name,
                        'age' => $age,
                        'date_of_birth' => $otherUser->date_of_birth,
                        'is_online' => $isOnline,
                        'photos' => $otherUser->photos->map(function($photo) {
                            return [
                                'id' => $photo->id,
                                'photo_url' => $photo->photo_url,
                                'order' => $photo->order,
                                'is_primary' => $photo->is_primary
                            ];
                        })
                    ],
                    'name' => $otherUser->first_name,
                    'age' => $age,
                    'avatar' => $avatar,
                    'online' => $isOnline,
                    'last_message' => $lastMessage ? [
                        'id' => $lastMessage->id,
                        'content' => $lastMessage->content,
                        'created_at' => $lastMessage->created_at,
                        'sender_id' => $lastMessage->sender_id
                    ] : null,
                    'lastMessage' => $lastMessage ? $lastMessage->content : 'No messages yet',
                    'timestamp' => $lastMessage ? $lastMessage->created_at->diffForHumans() : $match->matched_at,
                    'unread_count' => $unreadCount,
                    'unread' => $unreadCount,
                    'matched_at' => $match->matched_at,
                ];
            })
            ->filter() // Remove null entries
            ->values(); // Re-index array

        return response()->json([
            'success' => true,
            'data' => $conversations
        ]);
    }

    public function getMessages(Request $request, $matchId): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated'
            ], 401);
        }

        // Verify user is part of this match
        $match = DB::table('matches')
            ->where('id', $matchId)
            ->where(function ($query) use ($user) {
                $query->where('user1_id', $user->id)
                      ->orWhere('user2_id', $user->id);
            })
            ->where('is_active', true)
            ->first();

        if (!$match) {
            return response()->json([
                'success' => false,
                'message' => 'Match not found or access denied'
            ], 404);
        }

        // Get messages for this match
        $messages = Message::where('match_id', $matchId)
            ->where('is_deleted', false)
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function ($message) {
                return [
                    'id' => $message->id,
                    'match_id' => $message->match_id,
                    'sender_id' => $message->sender_id,
                    'receiver_id' => $message->receiver_id,
                    'content' => $message->content,
                    'type' => $message->type,
                    'read_at' => $message->read_at,
                    'is_deleted' => $message->is_deleted,
                    'created_at' => $message->created_at,
                    'updated_at' => $message->updated_at,
                ];
            });

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

        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated'
            ], 401);
        }

        // Verify user is part of this match
        $match = DB::table('matches')
            ->where('id', $matchId)
            ->where(function ($query) use ($user) {
                $query->where('user1_id', $user->id)
                      ->orWhere('user2_id', $user->id);
            })
            ->where('is_active', true)
            ->first();

        if (!$match) {
            return response()->json([
                'success' => false,
                'message' => 'Match not found or access denied'
            ], 404);
        }

        // Determine receiver ID
        $receiverId = $match->user1_id === $user->id ? $match->user2_id : $match->user1_id;

        // Create message
        $message = Message::create([
            'match_id' => $matchId,
            'sender_id' => $user->id,
            'receiver_id' => $receiverId,
            'content' => trim($request->content),
            'type' => $request->type ?? 'text',
            'is_deleted' => false,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Message sent successfully',
            'data' => [
                'id' => $message->id,
                'match_id' => $message->match_id,
                'sender_id' => $message->sender_id,
                'receiver_id' => $message->receiver_id,
                'content' => $message->content,
                'type' => $message->type,
                'read_at' => $message->read_at,
                'created_at' => $message->created_at,
                'updated_at' => $message->updated_at,
            ]
        ]);
    }

    public function markAsRead(Request $request, $messageId): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated'
            ], 401);
        }

        // Find message and verify user is the receiver
        $message = Message::where('id', $messageId)
            ->where('receiver_id', $user->id)
            ->whereNull('read_at')
            ->first();

        if (!$message) {
            return response()->json([
                'success' => false,
                'message' => 'Message not found or already read'
            ], 404);
        }

        // Mark as read
        $message->update([
            'read_at' => now()
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Message marked as read'
        ]);
    }

    public function deleteMessage(Request $request, $messageId): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated'
            ], 401);
        }

        // Find message and verify user is the sender
        $message = Message::where('id', $messageId)
            ->where('sender_id', $user->id)
            ->where('is_deleted', false)
            ->first();

        if (!$message) {
            return response()->json([
                'success' => false,
                'message' => 'Message not found or access denied'
            ], 404);
        }

        // Soft delete - mark as deleted but keep in database
        $message->update([
            'is_deleted' => true
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Message deleted successfully'
        ]);
    }

    public function getConversationInfo(Request $request, $matchId): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated'
            ], 401);
        }

        // Verify user is part of this match and get match info
        $match = DB::table('matches')
            ->where('id', $matchId)
            ->where(function ($query) use ($user) {
                $query->where('user1_id', $user->id)
                      ->orWhere('user2_id', $user->id);
            })
            ->where('is_active', true)
            ->first();

        if (!$match) {
            return response()->json([
                'success' => false,
                'message' => 'Match not found or access denied'
            ], 404);
        }

        // Get the other user's info
        $otherUserId = $match->user1_id === $user->id ? $match->user2_id : $match->user1_id;
        $otherUser = User::with(['photos', 'userProfile'])->find($otherUserId);

        if (!$otherUser) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        // Get message count
        $messageCount = Message::where('match_id', $matchId)
            ->where('is_deleted', false)
            ->count();

        $age = $otherUser->date_of_birth ? Carbon::parse($otherUser->date_of_birth)->age : null;
        $photos = $otherUser->photos->pluck('photo_url')->toArray();

        return response()->json([
            'success' => true,
            'data' => [
                'match_id' => $match->id,
                'matched_at' => $match->matched_at,
                'user' => [
                    'id' => $otherUser->id,
                    'name' => $otherUser->first_name . ' ' . $otherUser->last_name,
                    'first_name' => $otherUser->first_name,
                    'age' => $age,
                    'location' => $otherUser->location,
                    'bio' => $otherUser->bio,
                    'photos' => $photos,
                    'interests' => $otherUser->interests ? $otherUser->interests: [],
                    'profession' => $otherUser->profession,
                    'education' => $otherUser->education,
                ],
                'message_count' => $messageCount,
            ]
        ]);
    }
}
