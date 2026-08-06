# Repository Guidelines

## Project Structure & Module Organization

This is a Laravel 13, PHP 8.3 application using class-based Livewire 4, Flux, Tailwind CSS 4, and Vite. Keep domain code in `app/` (`Livewire/` for interactive components, `Models/` for Eloquent models, and `Actions/` or `Concerns/` for reusable behavior). Define HTTP routes in `routes/`; place Blade templates in `resources/views/`, JavaScript in `resources/js/`, and styles in `resources/css/`. Database migrations, factories, and seeders belong under `database/`. Put HTTP and workflow coverage in `tests/Feature/` and isolated logic tests in `tests/Unit/`. Treat `public/build/` as generated output.

## Build, Test, and Development Commands

- `composer setup` installs PHP/Node dependencies, creates `.env`, migrates the database, and builds assets.
- `composer dev` runs the Laravel server, queue listener, and Vite watcher together.
- `npm run build` creates production assets in `public/build/`.
- `composer test` clears configuration, checks formatting, runs PHPStan level 7, and executes the test suite.
- `php artisan test --filter=ProfileUpdateTest` runs a focused test while developing.

## Coding Style & Naming Conventions

Follow `.editorconfig`: UTF-8, LF endings, four-space indentation, and two spaces in YAML. Run `composer lint` to apply Laravel Pint; use `composer lint:check` for a non-mutating check. Use PSR-4 namespaces, PascalCase PHP classes, camelCase methods and variables, snake_case database columns, and kebab-case Blade filenames. Prefer small class-based Livewire components over logic-heavy views. Reuse shared theme components for every badge and status rather than creating one-off styles.

## Testing Guidelines

Tests use Pest 5 with Laravel helpers and an in-memory SQLite database. Name files by behavior or subject, ending in `Test.php`, and keep Arrange-Act-Assert intent obvious. Add concise feature tests for every substantial workflow, especially authentication, imports, assignments, attendance, and grading. Run `composer test` before opening a pull request; no numeric coverage threshold is currently configured.

## Product, Security & Design Constraints

The product is a standalone, single-tenant LMS supporting in-person SMP/SMA/SMK classes. Do not add parent/guardian modules or real-time online-class/video features. Admins and teachers use email/password accounts. Students cannot self-register: imports create accounts with NISN usernames, generated passwords, and a required first-login password change; only admins or teachers reset student passwords. Never commit `.env` or credentials.

Match the supplied ScriptMedia reference: Questrial, 18px cards, subtle borders/shadows, pill badges, navy `#0B2545`, tosca `#2CA6A4`, orange `#F4A300`, and off-white `#F4FAFA`.

## Commit & Pull Request Guidelines

Git history is unavailable in this checkout. Until a project convention is established, use short imperative Conventional Commit subjects such as `feat: add student import`. Pull requests should explain scope, list migrations/configuration changes, link the issue, include UI screenshots when relevant, and confirm `composer test` passes.
