<?php

// app/Http/Controllers/API/MessageController.php
namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Attachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Events\MessageSent;

class MessageController extends Controller
{
    public function index(Conversation $conversation)
    {
        $this->authorize('view', $conversation);

        $messages = $conversation->messages()
            ->with(['user', 'attachments'])
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json($messages);
    }

    public function store(Request $request, Conversation $conversation)
    {
        $this->authorize('view', $conversation);

        $request->validate([
            'body' => 'required_if:type,text|string|max:5000',
            'type' => 'in:text,image,file',
            'file' => 'required_if:type,image,file|file|max:10240', // 10MB max
        ]);

        $user = Auth::user();
        $messageData = [
            'conversation_id' => $conversation->id,
            'user_id' => $user->id,
            'type' => $request->type ?? 'text',
            'body' => $request->body,
        ];

        $message = Message::create($messageData);

        // Handle file upload
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $path = $file->store('attachments', 'public');

            Attachment::create([
                'message_id' => $message->id,
                'filename' => $file->getClientOriginalName(),
                'path' => $path,
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
            ]);

            if (!$message->body) {
                $message->update(['body' => '📎 ' . $file->getClientOriginalName()]);
            }
        }

        // Broadcast event
        broadcast(new MessageSent($message->load('user', 'attachments')))->toOthers();

        return response()->json($message->load('user', 'attachments'));
    }
}