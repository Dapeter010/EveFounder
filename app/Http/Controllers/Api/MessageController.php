<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Models\UserProfile;
use App\Models\Message;
use App\Models\Matcher;
use App\Events\MessageSent;

// ADD THIS IMPORT
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
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
                $otherUser = User::with(['photos' => function ($query) {
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
                        'photos' => $otherUser->photos->map(function ($photo) {
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
                    'recipient_id' => $message->receiver_id,
                    'content' => $message->content,
                    'type' => $message->type,
                    'media_url' => $message->media_url,
                    'view_once' => $message->view_once ?? false,
                    'viewed_at' => $message->viewed_at,
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
                'message' => implode(",", $validator->errors()->all()),
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

        // Load the message with sender relationship for broadcasting
        $message->load('sender');
        Log::info('About to broadcast MessageSent event', [
            'message_id' => $message->id,
            'sender_id' => $user->id,
            'receiver_id' => $receiverId,
            'match_id' => $matchId
        ]);

        // BROADCAST THE MESSAGE USING REVERB
        try {
            broadcast(new MessageSent($message, $user));
            Log::info('MessageSent event broadcast successfully');
        } catch (\Exception $e) {
            // Log the broadcasting error but don't fail the message sending
            Log::error('Failed to broadcast message: ' . $e->getMessage(), [
                'exception' => $e->getTraceAsString()
            ]);
        }

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

    public function sendMediaMessage(Request $request, $matchId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|in:image,video',
            'media' => 'required|file|mimes:jpg,jpeg,png,gif,mp4,mov|max:51200', // Max 50MB
            'view_once' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => implode(", ", $validator->errors()->all()),
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

        // Upload media file
        $mediaFile = $request->file('media');
        $mediaType = $request->input('type');
        $viewOnce = $request->boolean('view_once');

        // Store file in messages directory
        $path = $mediaFile->store("messages/{$matchId}", 'public');
        $mediaUrl = Storage::url($path);

        // Determine receiver ID
        $receiverId = $match->user1_id === $user->id ? $match->user2_id : $match->user1_id;

        // Create message
        $message = Message::create([
            'match_id' => $matchId,
            'sender_id' => $user->id,
            'receiver_id' => $receiverId,
            'content' => $viewOnce ? '🔒 View once media' : '📎 Media attachment',
            'type' => $mediaType,
            'media_url' => $mediaUrl,
            'view_once' => $viewOnce,
            'is_deleted' => false,
        ]);

        // Load the message with sender relationship for broadcasting
        $message->load('sender');

        // Broadcast the message
        try {
            broadcast(new MessageSent($message, $user));
            Log::info('Media message broadcast successfully');
        } catch (\Exception $e) {
            Log::error('Failed to broadcast media message: ' . $e->getMessage());
        }

        return response()->json([
            'success' => true,
            'message' => 'Media message sent successfully',
            'data' => [
                'id' => $message->id,
                'match_id' => $message->match_id,
                'sender_id' => $message->sender_id,
                'receiver_id' => $message->receiver_id,
                'recipient_id' => $message->receiver_id,
                'content' => $message->content,
                'type' => $message->type,
                'media_url' => $message->media_url,
                'view_once' => $message->view_once,
                'viewed_at' => $message->viewed_at,
                'read_at' => $message->read_at,
                'created_at' => $message->created_at,
                'updated_at' => $message->updated_at,
            ]
        ]);
    }

    public function markMediaAsViewed(Request $request, $matchId, $messageId): JsonResponse
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
            ->where('match_id', $matchId)
            ->where('receiver_id', $user->id)
            ->where('view_once', true)
            ->whereNull('viewed_at')
            ->first();

        if (!$message) {
            return response()->json([
                'success' => false,
                'message' => 'Message not found, already viewed, or not a view-once message'
            ], 404);
        }

        // Mark as viewed
        $message->update([
            'viewed_at' => now()
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Media marked as viewed'
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
                    'interests' => $otherUser->interests ? $otherUser->interests : [],
                    'profession' => $otherUser->profession,
                    'education' => $otherUser->education,
                ],
                'message_count' => $messageCount,
            ]
        ]);
    }

    public function sendTypingIndicator(Request $request, $matchId): JsonResponse
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

        // Determine receiver ID
        $receiverId = $match->user1_id === $user->id ? $match->user2_id : $match->user1_id;

        // Broadcast typing indicator (temporary event, doesn't save to database)
        try {
            broadcast(new \App\Events\UserTyping($matchId, $user->id, $receiverId));
        } catch (\Exception $e) {
            Log::error('Failed to broadcast typing indicator: ' . $e->getMessage());
        }

        return response()->json([
            'success' => true,
            'message' => 'Typing indicator sent'
        ]);
    }

}
