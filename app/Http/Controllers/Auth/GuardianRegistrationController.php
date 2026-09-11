<?php

namespace App\Http\Controllers\Auth;

use App\Concerns\PasswordValidationRules;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class GuardianRegistrationController extends Controller
{
    use PasswordValidationRules;

    public function create(): View
    {
        return view('auth.ortu-daftar');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->merge(['email' => Str::lower(trim((string) $request->input('email')))]);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email:rfc', 'max:255', Rule::unique(User::class)],
            'phone' => ['required', 'string', 'regex:/^\+?[0-9 ]{9,20}$/'],
            'password' => $this->passwordRules(),
        ], [
            'email.unique' => 'Email ini sudah terdaftar. Silakan masuk.',
            'phone.regex' => 'Nomor HP hanya boleh berisi angka, spasi, dan tanda +.',
        ]);

        // Akun yang daftar sendiri selalu berperan orang tua dan belum tertaut ke
        // anak mana pun. Data anak baru terlihat setelah admin menyetujui tautan.
        $guardian = DB::transaction(function () use ($validated): User {
            $user = User::query()->create([
                'name' => (string) $validated['name'],
                'email' => (string) $validated['email'],
                'phone' => (string) $validated['phone'],
                'password' => (string) $validated['password'],
            ]);
            $user->assignRole(Role::findOrCreate('ortu', 'web'));

            return $user;
        });

        Auth::login($guardian);
        $request->session()->regenerate();

        return redirect()
            ->route('dashboard.ortu')
            ->with('guardian_status', 'Akun berhasil dibuat. Langkah berikutnya: hubungkan akun dengan anak Anda.');
    }
}
