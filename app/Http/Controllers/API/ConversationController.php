<?php

// app/Http/Controllers/API/ConversationController.php
namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ConversationController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $conversations = $user->conversations()
            ->with(['latestMessage', 'users' => function ($query) use ($user) {
                $query->where('users.id', '!=', $user->id);
            }])
            ->withCount('participants')
            ->orderByDesc(function ($query) {
                $query->select('created_at')
                    ->from('messages')
                    ->whereColumn('conversation_id', 'conversations.id')
                    ->orderByDesc('created_at')
                    ->limit(1);
            })
            ->get();

        return response()->json($conversations);
    }

    public function store(Request $request)
    {
        $request->validate([
            'username' => 'required|exists:users,username',
        ]);

        $user = Auth::user();
        $otherUser = User::where('username', $request->username)->first();

        // Check if conversation already exists
        $existingConversation = $user->conversations()
            ->whereHas('users', function ($query) use ($otherUser) {
                $query->where('users.id', $otherUser->id);
            })
            ->where('type', 'direct')
            ->first();

        if ($existingConversation) {
            return response()->json($existingConversation->load('users'));
        }

        // Create new conversation
        $conversation = Conversation::create([
            'type' => 'direct',
            'created_by' => $user->id,
        ]);

        // Add participants
        $conversation->users()->attach([$user->id, $otherUser->id]);

        return response()->json($conversation->load('users'), 201);
    }

    public function show($id)
    {
        $conversation = Conversation::with(['users', 'messages.user'])
            ->findOrFail($id);

        $this->authorize('view', $conversation);

        return response()->json($conversation);
    }
}