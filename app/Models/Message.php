<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    protected $fillable = [
        'conversation_id',
        'user_id',
        'session_id',
        'role',
        'content',
        'attachments',
    ];

    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'attachments' => 'array',
    ];

    /**
     * Helper to get full URLs for attachments
     */
    public function getFormattedAttachmentsAttribute()
    {
        if (!$this->attachments) return [];

        return array_map(function ($attachment) {
            return [
                'name' => $attachment['name'] ?? 'file',
                'url'  => asset($attachment['path']), // Converts path to full URL
                'type' => $attachment['type'] ?? '',
                'isImage' => $attachment['isImage'] ?? false,
            ];
        }, $this->attachments);
    }
}
