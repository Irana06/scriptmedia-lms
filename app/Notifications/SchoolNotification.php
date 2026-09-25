<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

/**
 * Notifikasi di dalam aplikasi (ikon lonceng). Sengaja tidak di-queue: server
 * cPanel sekolah umumnya tidak menjalankan queue worker.
 */
class SchoolNotification extends Notification
{
    public function __construct(
        public string $title,
        public string $body,
        public string $url,
        public string $icon = 'bell',
    ) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array{title: string, body: string, url: string, icon: string} */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->title,
            'body' => $this->body,
            'url' => $this->url,
            'icon' => $this->icon,
        ];
    }
}
