<?php

namespace App\Livewire;

use App\Models\Announcement;
use App\Models\CalendarEvent;
use App\Models\SchoolClass;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('components.layouts.guru-admin')]
#[Title('Pengumuman & Kalender')]
class CommunicationManager extends Component
{
    #[Url]
    public string $tab = 'announcements';

    public string $announcementTitle = '';

    public string $announcementBody = '';

    public string $announcementTarget = 'all';

    public string $announcementClassId = '';

    public string $eventTitle = '';

    public string $eventDate = '';

    public string $eventDescription = '';

    public function mount(): void
    {
        $this->eventDate = now()->format('Y-m-d');
    }

    public function saveAnnouncement(): void
    {
        $rules = [
            'announcementTitle' => ['required', 'string', 'max:255'],
            'announcementBody' => ['required', 'string', 'max:10000'],
            'announcementTarget' => ['required', Rule::in(['all', 'class'])],
            'announcementClassId' => [$this->announcementTarget === 'class' ? 'required' : 'nullable', 'integer'],
        ];
        $validated = $this->validate($rules);
        $classId = null;

        if ($validated['announcementTarget'] === 'class') {
            $schoolClass = $this->availableClasses()->whereKey($validated['announcementClassId'])->firstOrFail();
            $classId = $schoolClass->id;
        }

        Announcement::query()->create([
            'title' => $validated['announcementTitle'],
            'body' => $validated['announcementBody'],
            'target' => $validated['announcementTarget'],
            'class_id' => $classId,
            'created_by' => Auth::id(),
        ]);
        $this->reset('announcementTitle', 'announcementBody', 'announcementClassId');
        $this->announcementTarget = 'all';
        session()->flash('communication_status', 'Pengumuman berhasil diterbitkan.');
    }

    public function deleteAnnouncement(int $id): void
    {
        $announcement = Announcement::query()->findOrFail($id);
        if (! Auth::user()?->hasRole('admin') && $announcement->created_by !== Auth::id()) {
            throw ValidationException::withMessages(['announcementTitle' => 'Anda hanya dapat menghapus pengumuman sendiri.']);
        }
        $announcement->delete();
    }

    public function saveEvent(): void
    {
        $validated = $this->validate([
            'eventTitle' => ['required', 'string', 'max:255'],
            'eventDate' => ['required', 'date'],
            'eventDescription' => ['nullable', 'string', 'max:5000'],
        ]);
        CalendarEvent::query()->create([
            'title' => $validated['eventTitle'],
            'date' => $validated['eventDate'],
            'description' => $validated['eventDescription'] ?: null,
            'created_by' => Auth::id(),
        ]);
        $this->reset('eventTitle', 'eventDescription');
        $this->eventDate = now()->format('Y-m-d');
        session()->flash('communication_status', 'Agenda kalender berhasil ditambahkan.');
    }

    public function deleteEvent(int $id): void
    {
        $event = CalendarEvent::query()->findOrFail($id);
        if (! Auth::user()?->hasRole('admin') && $event->created_by !== Auth::id()) {
            abort(403);
        }
        $event->delete();
    }

    public function render(): View
    {
        return view('livewire.communication-manager', [
            'classes' => $this->availableClasses()->get(),
            'announcements' => $this->visibleAnnouncements()->latest()->limit(20)->get(),
            'events' => CalendarEvent::query()->with('creator')->orderBy('date')->limit(30)->get(),
        ]);
    }

    /** @return Builder<SchoolClass> */
    private function availableClasses(): Builder
    {
        $query = SchoolClass::query()->whereHas('academicYear', fn ($builder) => $builder->where('is_active', true));

        if (! Auth::user()?->hasRole('admin')) {
            $query->whereHas('classSubjects', fn ($builder) => $builder->where('teacher_id', Auth::id()));
        }

        return $query->orderBy('name');
    }

    /** @return Builder<Announcement> */
    private function visibleAnnouncements(): Builder
    {
        $query = Announcement::query()->with('schoolClass', 'creator');

        if (! Auth::user()?->hasRole('admin')) {
            $classIds = $this->availableClasses()->pluck('id');
            $query->where(function ($builder) use ($classIds): void {
                $builder->where('target', 'all')
                    ->orWhereIn('class_id', $classIds)
                    ->orWhere('created_by', Auth::id());
            });
        }

        return $query;
    }
}
