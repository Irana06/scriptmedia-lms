<?php

namespace App\Http\Controllers;

use App\Actions\ResetAccountPassword;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TeacherController extends Controller
{
    public function index(): View
    {
        $teachers = User::role('guru')
            ->orderBy('name')
            ->paginate(15);

        return view('teachers.index', compact('teachers'));
    }

    public function resetPassword(
        Request $request,
        User $teacher,
        ResetAccountPassword $resetAccountPassword,
    ): RedirectResponse {
        abort_unless($teacher->hasRole('guru'), 404);

        $password = $resetAccountPassword->handle($teacher, $request->user());

        return back()
            ->with('reset_user_id', $teacher->id)
            ->with('reset_user_name', $teacher->name)
            ->with('generated_password', $password)
            ->withHeaders(['Cache-Control' => 'no-store']);
    }
}
