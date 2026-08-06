<?php

namespace App\Http\Controllers;

use App\Actions\ResetStudentPassword;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StudentController extends Controller
{
    public function index(): View
    {
        $students = User::role('siswa')
            ->orderBy('name')
            ->paginate(15);

        return view('students.index', compact('students'));
    }

    public function resetPassword(
        Request $request,
        User $student,
        ResetStudentPassword $resetStudentPassword,
    ): RedirectResponse {
        $password = $resetStudentPassword->handle($student, $request->user());

        return back()
            ->with('reset_student_id', $student->id)
            ->with('reset_student_name', $student->name)
            ->with('generated_password', $password)
            ->withHeaders(['Cache-Control' => 'no-store']);
    }
}
