<?php

namespace App\Livewire;

use App\Models\User;
use App\Support\AnnouncementAudience;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Component;

class NotificationBell extends Component
{
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
        $unread = $user->unreadNotifications()->count();

        return view('livewire.notification-bell', [
            'notifications' => $user->notifications()->latest()->limit(8)->get(),
            'newAnnouncements' => $newAnnouncements,
            'unreadTotal' => $unread + $newAnnouncements,
        ]);
    }

    private function user(): User
    {
        $user = Auth::user();
        abort_unless($user instanceof User, 403);

        return $user;
    }
}
