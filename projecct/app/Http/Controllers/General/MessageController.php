<?php

namespace App\Http\Controllers\General;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class MessageController extends Controller
{
    public function index(?Conversation $conversation = null)
    {
        $user = auth()->user();

        $conversations = Conversation::forUser($user)
            ->with(['userOne', 'userTwo', 'lastMessage'])
            ->orderByDesc('last_message_at')
            ->get();

        $active = null;
        $messages = collect();

        if ($conversation && $conversation->exists) {
            abort_unless($conversation->hasParticipant($user), 403);
            $active = $conversation->load(['userOne', 'userTwo']);
            $markedRead = $active->messages()->where('sender_id', '!=', $user->id)->whereNull('read_at')->update(['read_at' => now()]);
            if ($markedRead > 0) {
                broadcast(new \App\Events\MessagesRead($active->id, $user->id, now()->toISOString()));
            }
            $messages = $conversation->messages()->with('sender')->get();
        }

        $conversationsData = $conversations->map(function ($c) use ($user, $active) {
            $peer = $c->other($user);

            return [
                'id'          => $c->id,
                'url'         => route('messages.show', $c),
                'peer_name'   => $peer?->name ?? 'Kullanıcı',
                'peer_avatar' => $peer?->profile_img,
                'peer_online' => (bool) ($peer && $peer->show_online && $peer->isOnline()),
                'last_body'   => Str::limit($c->lastMessage?->body ?? 'Sohbeti başlat', 34),
                'unread'      => $c->unreadCountFor($user),
                'is_active'   => $active && $active->id === $c->id,
            ];
        })->values();

        $activeData = null;
        if ($active) {
            $peer = $active->other($user);
            $activeData = [
                'id'           => $active->id,
                'peer_name'    => $peer?->name,
                'peer_username'=> $peer?->username,
                'peer_avatar'  => $peer?->profile_img,
                'peer_online'  => (bool) ($peer && $peer->show_online && $peer->isOnline()),
                'peer_last_seen' => ($peer && $peer->show_online && ! $peer->isOnline()) ? $peer->lastSeenDiff() : null,
                'profile_url'  => $peer ? route('profile.public', $peer->username) : '#',
                'store_url'    => route('messages.store', $active),
                'poll_url'     => route('messages.poll', $active),
                'read_url'     => route('messages.read', $active),
            ];
        }

        $messagesData = $messages->map(fn ($m) => [
            'id'   => $m->id,
            'body' => $m->body,
            'mine' => $m->sender_id === $user->id,
            'time' => $m->created_at->format('H:i'),
            'read' => $m->read_at !== null,
        ])->values();

        return Inertia::render('Messages/Index', [
            'conversations' => $conversationsData,
            'active'        => $activeData,
            'messages'      => $messagesData,
            'index_url'     => route('messages.index'),
        ]);
    }

    public function show(Conversation $conversation)
    {
        return $this->index($conversation);
    }

    public function store(Request $request, Conversation $conversation)
    {
        $user = auth()->user();
        abort_unless($conversation->hasParticipant($user), 403);

        // Gizlilik: karşı taraf "sadece takipten mesaj" ayarını açtıysa ve bizi
        // takip etmiyorsa (biz admin değilsek) mevcut sohbette bile mesaj engellenir.
        $target = $conversation->other($user);
        if ($target && $target->messages_followers_only
            && ! $target->isFollowing($user->id)
            && ! $user->isAdmin()) {
            return back()->with('error', 'Bu kullanıcı yalnızca takip ettiği kişilerden mesaj alıyor.');
        }

        $data = $request->validate([
            'body' => ['required', 'string', 'max:2000'],
        ]);

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id'       => $user->id,
            'body'            => $data['body'],
        ]);

        $conversation->update(['last_message_at' => now()]);

        // Gerçek-zamanlı: mesajı özel konuşma kanalına anlık yayınla (Reverb WebSocket).
        broadcast(new \App\Events\MessageSent($message))->toOthers();

        // Alıcının kişisel kanalına "yeni mesaj" bildirimi → sidebar rozeti anlık artar.
        if ($target) {
            broadcast(new \App\Events\NewMessageNotification($target->id, $message));
        }

        if ($request->wantsJson()) {
            return response()->json([
                'id'      => $message->id,
                'body'    => $message->body,
                'mine'    => true,
                'time'    => $message->created_at->format('H:i'),
                'read'    => false,
            ]);
        }

        return redirect()->route('messages.show', $conversation);
    }

    public function poll(Request $request, Conversation $conversation)
    {
        $user = auth()->user();
        abort_unless($conversation->hasParticipant($user), 403);

        $afterId = (int) $request->query('after', 0);

        $new = $conversation->messages()
            ->with('sender')
            ->where('id', '>', $afterId)
            ->get();

        // Karşıdan gelen yeni mesajları okundu işaretle (yalnızca gerekiyorsa)
        $unread = $conversation->messages()
            ->where('sender_id', '!=', $user->id)
            ->whereNull('read_at');

        if ($unread->exists()) {
            $unread->update(['read_at' => now()]);
            broadcast(new \App\Events\MessagesRead($conversation->id, $user->id, now()->toISOString()));
        }

        return response()->json([
            'messages' => $new->map(fn ($m) => [
                'id'   => $m->id,
                'body' => $m->body,
                'mine' => $m->sender_id === $user->id,
                'time' => $m->created_at->format('H:i'),
                'name' => $m->sender->name,
                'read' => $m->read_at !== null,
            ]),
        ]);
    }

    /**
     * Aktif sohbette karşıdan gelen okunmamış mesajları toplu okundu işaretler ve
     * gönderene tek bir "okundu" olayı yayınlar (mesaj başına değil).
     */
    public function markRead(Request $request, Conversation $conversation)
    {
        $user = auth()->user();
        abort_unless($conversation->hasParticipant($user), 403);

        $marked = $conversation->messages()
            ->where('sender_id', '!=', $user->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        if ($marked > 0) {
            broadcast(new \App\Events\MessagesRead($conversation->id, $user->id, now()->toISOString()));
        }

        return response()->json(['ok' => true, 'marked' => $marked]);
    }

    /**
     * Sidebar rozeti için hafif okunmamış mesaj sayacı (polling yedeği).
     */
    public function unreadCount()
    {
        $user = auth()->user();

        return response()->json([
            'count' => $user ? $user->unreadMessagesCount() : 0,
        ]);
    }

    public function start(Request $request)
    {
        $user = auth()->user();

        $data = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        if ((int) $data['user_id'] === $user->id) {
            return redirect()->route('messages.index');
        }

        $target = User::findOrFail($data['user_id']);

        // Gizlilik: hedef kullanıcı "sadece takip ettiklerinden mesaj" ayarını açtıysa,
        // yalnızca onun takip ettiği kişiler (veya admin) yeni sohbet başlatabilir.
        if ($target->messages_followers_only
            && ! $target->isFollowing($user->id)
            && ! $user->isAdmin()) {
            return back()->with('error', 'Bu kullanıcı yalnızca takip ettiği kişilerden mesaj alıyor.');
        }

        $conversation = Conversation::between($user, $target);

        if ($request->filled('body')) {
            Message::create([
                'conversation_id' => $conversation->id,
                'sender_id'       => $user->id,
                'body'            => substr($request->input('body'), 0, 2000),
            ]);
            $conversation->update(['last_message_at' => now()]);
        }

        return redirect()->route('messages.show', $conversation);
    }
}
