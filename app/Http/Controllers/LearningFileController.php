<?php

namespace App\Http\Controllers;

use App\Auth\StudentAccess;
use App\Models\AssignmentSubmission;
use App\Models\MaterialFile;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LearningFileController extends Controller
{
    public function material(Request $request, MaterialFile $materialFile, StudentAccess $studentAccess): StreamedResponse
    {
        $materialFile->loadMissing('material.classSubject.schoolClass.students');
        $classSubject = $materialFile->material->classSubject;
        $user = $request->user();
        $allowed = $user->hasRole('admin')
            || $classSubject->teacher_id === $user->id
            || $classSubject->schoolClass->students->contains(
                fn (User $student): bool => $studentAccess->canView($user, $student->id),
            );
        abort_unless($allowed && $materialFile->type !== 'link', 403);

        if ($request->boolean('preview') && in_array($materialFile->type, ['image', 'video', 'pdf'], true)) {
            return Storage::disk('local')->response($materialFile->file_path, null, [], 'inline');
        }

        return Storage::disk('local')->download($materialFile->file_path);
    }

    public function submission(Request $request, AssignmentSubmission $submission, StudentAccess $studentAccess): StreamedResponse
    {
        $submission->loadMissing('assignment.classSubject');
        $user = $request->user();
        $allowed = $user->hasRole('admin')
            || $studentAccess->canView($user, $submission->student_id)
            || $submission->assignment->classSubject->teacher_id === $user->id;
        abort_unless($allowed, 403);

        return Storage::disk('local')->download($submission->file_path);
    }
}
