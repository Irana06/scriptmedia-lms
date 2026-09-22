<?php

namespace App\Providers;

use App\Http\Responses\RoleLoginResponse;
use App\Models\AcademicYear;
use App\Models\GuardianLink;
use App\Models\SchoolProfile;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(LoginResponseContract::class, RoleLoginResponse::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->shareAdminNavigationCounts();
        $this->shareSchoolHeader();
    }

    /**
     * Angka di sidebar admin, supaya permintaan orang tua yang menunggu terlihat
     * dari halaman mana pun. Hanya dihitung untuk admin: guru memakai layout yang
     * sama dan tidak perlu menanggung query tambahan.
     */
    protected function shareAdminNavigationCounts(): void
    {
        view()->composer('components.layouts.guru-admin', function (View $view): void {
            $user = auth()->user();

            $view->with('pendingGuardianCount', $user instanceof User && $user->hasRole('admin')
                ? GuardianLink::query()->pending()->count()
                : 0);
        });
    }

    /**
     * Nama sekolah dan tahun ajaran aktif di header, dulu tertulis tetap "SMA
     * Nusantara" — salah untuk sekolah lain mana pun yang memakai produk ini.
     */
    protected function shareSchoolHeader(): void
    {
        view()->composer('components.layouts.guru-admin', function (View $view): void {
            $view->with('schoolName', SchoolProfile::current()->name);
            $view->with('activeYearLabel', AcademicYear::query()->where('is_active', true)->value('year_label'));
        });
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
