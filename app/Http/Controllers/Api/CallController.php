<?php

namespace App\Http\Controllers\Api;

use App\Events\CallAccepted;
use App\Events\CallDeclined;
use App\Events\CallEnded;
use App\Events\CallInitiated;
use App\Events\WebRTCSignal;
use App\Http\Controllers\Controller;
use App\Models\Call;
use App\Models\CallEvent;
use App\Models\Matcher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class CallController extends Controller
{
    /**
     * Initiate a new call to a matched user.
     */
    public function initiate(Request $request)
    {
        $validated = $request->validate([
            'match_id' => 'required|integer|exists:matches,id',
            'type' => ['required', Rule::in(['audio', 'video'])],
        ]);

        $user = Auth::user();
        $match = Matcher::findOrFail($validated['match_id']);

        // Verify the match belongs to the user
        if ($match->user_id !== $user->id && $match->matched_user_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Determine receiver
        $receiverId = $match->user_id === $user->id ? $match->matched_user_id : $match->user_id;

        // Check if receiver has blocked the caller (dark mode privacy)
        $receiver = \App\Models\User::find($receiverId);
        if ($receiver->hasBlocked($user->id)) {
            return response()->json(['error' => 'Cannot call this user'], 403);
        }

        // Check for existing active call
        $existingCall = Call::where('match_id', $match->id)
            ->whereIn('status', ['ringing', 'ongoing'])
            ->first();

        if ($existingCall) {
            return response()->json(['error' => 'Call already in progress'], 409);
        }

        // Create the call
        $call = Call::create([
            'match_id' => $match->id,
            'caller_id' => $user->id,
            'receiver_id' => $receiverId,
            'type' => $validated['type'],
            'status' => 'ringing',
        ]);

        // Log the event
        CallEvent::createEvent($call->id, 'initiated', $user->id);

        // Broadcast to receiver
        broadcast(new CallInitiated($call, $user))->toOthers();

        return response()->json([
            'success' => true,
            'call' => $call->load(['caller', 'receiver']),
        ], 201);
    }

    /**
     * Accept an incoming call.
     */
    public function accept(Request $request, $callId)
    {
        $user = Auth::user();
        $call = Call::findOrFail($callId);

        // Verify the user is the receiver
        if ($call->receiver_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Check call is still ringing
        if ($call->status !== 'ringing') {
            return response()->json(['error' => 'Call is no longer active'], 409);
        }

        // Mark as accepted and started
        $call->markAsStarted();

        // Log the event
        CallEvent::createEvent($call->id, 'accepted', $user->id);

        // Broadcast to caller
        broadcast(new CallAccepted($call, $user))->toOthers();

        return response()->json([
            'success' => true,
            'call' => $call->fresh()->load(['caller', 'receiver']),
        ]);
    }

    /**
     * Decline an incoming call.
     */
    public function decline(Request $request, $callId)
    {
        $user = Auth::user();
        $call = Call::findOrFail($callId);

        // Verify the user is the receiver
        if ($call->receiver_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Check call is still ringing
        if ($call->status !== 'ringing') {
            return response()->json(['error' => 'Call is no longer active'], 409);
        }

        // Mark as declined
        $call->markAsDeclined();

        // Log the event
        CallEvent::createEvent($call->id, 'declined', $user->id);

        // Broadcast to caller
        broadcast(new CallDeclined($call, $user))->toOthers();

        return response()->json([
            'success' => true,
            'call' => $call->fresh(),
        ]);
    }

    /**
     * End an ongoing call.
     */
    public function end(Request $request, $callId)
    {
        $user = Auth::user();
        $call = Call::findOrFail($callId);

        // Verify the user is part of the call
        if ($call->caller_id !== $user->id && $call->receiver_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Mark as ended
        if ($call->status === 'ringing') {
            $call->markAsMissed();
            CallEvent::createEvent($call->id, 'missed', $user->id);
        } else {
            $call->markAsEnded();
            CallEvent::createEvent($call->id, 'ended', $user->id);
        }

        // Broadcast to other user
        broadcast(new CallEnded($call, $user))->toOthers();

        return response()->json([
            'success' => true,
            'call' => $call->fresh(),
        ]);
    }

    /**
     * Send WebRTC signaling data (SDP offer/answer, ICE candidates).
     */
    public function signal(Request $request, $callId)
    {
        $validated = $request->validate([
            'type' => ['required', Rule::in(['offer', 'answer', 'ice_candidate'])],
            'data' => 'required',
        ]);

        $user = Auth::user();
        $call = Call::findOrFail($callId);

        // Verify the user is part of the call
        if ($call->caller_id !== $user->id && $call->receiver_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Check call is active
        if (!$call->isActive()) {
            return response()->json(['error' => 'Call is not active'], 409);
        }

        // Log the signaling event
        CallEvent::createEvent($call->id, $validated['type'], $user->id, [
            'data' => $validated['data'],
        ]);

        // Broadcast to the other user
        broadcast(new WebRTCSignal($call, $user, $validated['type'], $validated['data']))->toOthers();

        return response()->json(['success' => true]);
    }

    /**
     * Get call history for the authenticated user.
     */
    public function history(Request $request)
    {
        $user = Auth::user();

        $calls = Call::where(function ($query) use ($user) {
            $query->where('caller_id', $user->id)
                  ->orWhere('receiver_id', $user->id);
        })
        ->with(['caller', 'receiver', 'match'])
        ->orderBy('created_at', 'desc')
        ->paginate(20);

        return response()->json($calls);
    }

    /**
     * Get details of a specific call.
     */
    public function show($callId)
    {
        $user = Auth::user();
        $call = Call::with(['caller', 'receiver', 'match', 'events'])
            ->findOrFail($callId);

        // Verify the user is part of the call
        if ($call->caller_id !== $user->id && $call->receiver_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        return response()->json($call);
    }

    /**
     * Get active call for a match (if any).
     */
    public function activeCall(Request $request, $matchId)
    {
        $user = Auth::user();
        $match = Match::findOrFail($matchId);

        // Verify the match belongs to the user
        if ($match->user_id !== $user->id && $match->matched_user_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $activeCall = Call::where('match_id', $matchId)
            ->whereIn('status', ['ringing', 'ongoing'])
            ->with(['caller', 'receiver'])
            ->first();

        return response()->json([
            'active_call' => $activeCall,
        ]);
    }
}
