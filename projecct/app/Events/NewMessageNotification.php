<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Alıcının kişisel kanalına (App.Models.User.{id}) anlık "yeni mesaj" bildirimi.
 * AppLayout bunu dinleyerek sidebar okunmamış rozetini sayfa yenilemeden günceller.
 */
class NewMessageNotification implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $recipientId,
        public Message $message
    ) {
    }

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('App.Models.User.'.$this->recipientId);
    }

    public function broadcastAs(): string
    {
        return 'message.notification';
    }

    public function broadcastWith(): array
    {
        return [
            'conversation_id' => $this->message->conversation_id,
            'sender_id'       => $this->message->sender_id,
        ];
    }
}
