# Repository Guidelines

## Project Structure & Module Organization

This Laravel 13 application targets PHP 8.4 locally (with `composer.json` allowing PHP 8.3+) and uses class-based Livewire 4, Flux, Tailwind CSS 4, and Vite. Keep domain code in `app/`, routes in `routes/`, Blade templates in `resources/views/`, JavaScript in `resources/js/`, and styles in `resources/css/`. Migrations, factories, and seeders belong under `database/`. Put workflow coverage in `tests/Feature/` and isolated logic tests in `tests/Unit/`. Treat `public/build/` as generated output.

## Build, Test, and Development Commands

- `composer setup` installs PHP/Node dependencies, creates `.env`, migrates the database, and builds assets.
- `composer dev` runs the Laravel server, queue listener, and Vite watcher together.
- `npm run build` creates production assets in `public/build/`.
- `composer test` clears configuration, checks formatting, runs PHPStan level 7, and executes the test suite.
- `php artisan test --filter=ProfileUpdateTest` runs a focused test while developing.

## Coding Style & Naming Conventions

Follow `.editorconfig`: UTF-8, LF endings, four-space indentation, and two spaces in YAML. Use `composer lint` to apply Laravel Pint and `composer lint:check` to verify formatting. Use PSR-4 namespaces, PascalCase classes, camelCase methods, snake_case database columns, and kebab-case Blade filenames. Prefer small Livewire components over logic-heavy views. Reuse theme components for badges and statuses.

## Testing Guidelines

Tests use Pest 5 with an in-memory SQLite database. Name files by behavior or subject, ending in `Test.php`. Add concise feature tests for substantial workflows, especially authentication, imports, assignments, attendance, and grading. Run `composer test` before opening a pull request; no coverage threshold is configured.

## Product, Security & Design Constraints

Each deployment serves one school (one database per install, deployed separately per client via `scripts/server-deploy.sh`) for in-person SMP/SMA/SMK/madrasah classes — not real-time video classes. Four roles: admin, guru (teacher), siswa (student), and ortu (parent/guardian, read-only). Admins and teachers use email/password accounts. Students cannot self-register: imports create NISN- or NIS-based accounts (private schools often lack NISN) with generated passwords and a required first-login password change; only staff reset student passwords.

Guardians may self-register, but a guardian-student link only takes effect after admin approval (`GuardianLink`/`guardian_student`), and `App\Auth\StudentAccess` is the single place that decides read access (`viewedStudent()`) versus act-as-student access (`actingStudent()`, always the logged-in student, never a guardian). Any new guardian-facing feature must go through the read-only path only — never let a guardian submit assignments, take quizzes, or otherwise act as the student. New installs run `php artisan sekolah:setup` once to create the school profile, first admin, and active academic year. Never commit `.env` or credentials.

Match the supplied ScriptMedia reference: Questrial, 18px cards, subtle borders/shadows, pill badges, navy `#0B2545`, tosca `#2CA6A4`, orange `#F4A300`, and off-white `#F4FAFA`.

## Commit & Pull Request Guidelines

Use short imperative Conventional Commit subjects such as `feat: add student import`; run `git log` for the established style. Do not add a `Co-Authored-By` trailer for any AI assistant — commits use the developer's own git credentials only. Pull requests should explain scope, list migrations/configuration changes, link the issue, include UI screenshots when relevant, and confirm `composer test` passes.
