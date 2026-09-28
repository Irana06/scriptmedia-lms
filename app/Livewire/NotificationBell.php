<?php

namespace App\Livewire;

use App\Models\Announcement;
use App\Models\User;
use App\Support\AnnouncementAudience;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Component;

class NotificationBell extends Component
{
    private const FEED_SIZE = 10;

    public function open(string $id): void
    {
        $notification = $this->user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        $this->redirect((string) ($notification->data['url'] ?? route('dashboard')), navigate: true);
    }

    public function openAnnouncements(): void
    {
        $user = $this->user();
        $user->forceFill(['announcements_seen_at' => now()])->save();

        $this->redirect(AnnouncementAudience::readingRoute($user), navigate: true);
    }

    public function markAllRead(): void
    {
        $user = $this->user();
        $user->unreadNotifications()->update(['read_at' => now()]);
        $user->forceFill(['announcements_seen_at' => now()])->save();
    }

    public function render(): View
    {
        $user = $this->user();
        // Akun baru tidak dibanjiri pengumuman lama yang terbit sebelum akunnya ada.
        $seenSince = $user->announcements_seen_at ?? $user->created_at;
        $newAnnouncements = AnnouncementAudience::visibleTo($user)
            ->when($seenSince, fn ($query) => $query->where('created_at', '>', $seenSince))
            ->count();

        // Pengumuman tampil satu per satu bersama notifikasi lain dan tetap ada
        // setelah dibaca (hanya tanda "belum dibaca" yang hilang), sama seperti
        // notifikasi tugas. Dulu hanya satu baris ringkas yang hilang saat diklik.
        $announcements = AnnouncementAudience::visibleTo($user)
            ->latest()
            ->limit(self::FEED_SIZE)
            ->get()
            ->map(fn (Announcement $announcement): array => [
                'key' => "announcement-{$announcement->id}",
                'action' => 'openAnnouncements',
                'argument' => null,
                'title' => $announcement->title,
                'body' => Str::limit($announcement->body, 120),
                'icon' => 'megaphone',
                'label' => 'Pengumuman',
                'time' => $announcement->created_at,
                'unread' => $seenSince === null || $announcement->created_at?->gt($seenSince) === true,
            ]);

        $notifications = $user->notifications()
            ->latest()
            ->limit(self::FEED_SIZE)
            ->get()
            ->map(fn (DatabaseNotification $notification): array => [
                'key' => "notification-{$notification->id}",
                'action' => 'open',
                'argument' => (string) $notification->id,
                'title' => (string) ($notification->data['title'] ?? 'Notifikasi'),
                'body' => (string) ($notification->data['body'] ?? ''),
                'icon' => (string) ($notification->data['icon'] ?? 'bell'),
                'label' => null,
                'time' => $notification->created_at,
                'unread' => $notification->read_at === null,
            ]);

        return view('livewire.notification-bell', [
            'items' => $announcements->concat($notifications)
                ->sortByDesc(fn (array $item): int => $item['time']?->getTimestamp() ?? 0)
                ->take(self::FEED_SIZE)
                ->values(),
            'unreadTotal' => $user->unreadNotifications()->count() + $newAnnouncements,
        ]);
    }

    private function user(): User
    {
        $user = Auth::user();
        abort_unless($user instanceof User, 403);

        return $user;
    }
}
