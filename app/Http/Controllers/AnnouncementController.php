<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\User;
use App\Support\AnnouncementAudience;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AnnouncementController extends Controller
{
    public function show(Request $request, Announcement $announcement): View
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        // 404, bukan 403: siswa kelas lain tidak perlu tahu pengumuman itu ada.
        abort_unless(AnnouncementAudience::visibleTo($user)->whereKey($announcement->id)->exists(), 404);

        // Dianggap dibaca saat halamannya benar-benar dibuka, bukan saat diklik di lonceng.
        $announcement->markReadBy($user);
        $announcement->loadMissing('creator', 'schoolClass');

        return view('announcements.show', [
            'announcement' => $announcement,
            'layout' => match (true) {
                $user->hasAnyRole(['admin', 'guru']) => 'layouts.guru-admin',
                $user->hasRole('ortu') => 'layouts.ortu',
                default => 'layouts.siswa',
            },
            'others' => AnnouncementAudience::visibleTo($user)
                ->whereKeyNot($announcement->id)
                ->with('schoolClass')
                ->latest()
                ->limit(5)
                ->get(),
            'unreadIds' => Announcement::query()->unreadBy($user)->pluck('id')->all(),
            'listUrl' => AnnouncementAudience::listRoute($user),
        ]);
    }
}
