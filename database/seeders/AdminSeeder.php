<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::query()->updateOrCreate(
            ['email' => config('lms.default_admin.email')],
            [
                'name' => config('lms.default_admin.name'),
                'password' => config('lms.default_admin.password'),
                'email_verified_at' => now(),
            ],
        );

        $admin->syncRoles(['admin']);
    }
}
