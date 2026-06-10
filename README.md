# Yammi Audit Log for Laravel

Universal change history and audit log for Laravel. It records who changed what and when across your Eloquent models, with rich actor attribution, field-level diffs, correlation chains across models, a noise diagnostic for double writes, and a timeline dashboard.

## Why

Existing audit packages answer *what* changed but rarely *who* really changed it. A status flip by a queued job, an Artisan command or the scheduler all collapse into an anonymous `null`. This package treats actor attribution as a first-class concern: user, job, command, scheduler or system — and traces a whole cascade of changes across models back to the one action that started it.

## Features

- Zero-config capture of Eloquent create/update/delete/restore (opt-out per model).
- Multi-level actor attribution (user / job / command / scheduler / system) with an immediate-parent origin, so a nested `job → job → job` chain shows who spawned each step.
- Correlation id per unit of work (request, command or job and the jobs it dispatches), drawn as a cross-model change chain.
- Field-level diffs with secret redaction; timestamp-only "no-op" writes are recorded but flagged as noise.
- Dashboard with filters (model, event, actor, date), a noise page, and a chain/trace view.
- Retention pruning (PII), a configurable Gate and rate limit on the UI.

## Public API

Treat these as stable; everything marked `@internal` is an implementation detail and may change.

- **Facade** — `Yammi\AuditLog\Infrastructure\Facade\AuditLog`: `AuditLog::for($model)` returns a `TimelineData`.
- **DTOs** — `Yammi\AuditLog\Application\DTO\TimelineData` and `TimelineEntryData`.
- **Config** — `config/audit-log.php` (publish with `--tag=audit-log-config`).
- **Extension contracts** (bind your own implementation): `Application\Contract\ActorProvider`, `ActorResolver`, `ValueRedactor`, `LabelResolver`, `Clock`, `CorrelationResolver`, `AuditLogQuery`, and `Domain\Audit\Repository\AuditRecordRepository`.
- **Domain value objects/enums** for custom resolvers: `Actor`, `ActorType`, `ChangeType`, `Diff`, `AuditableReference`, `LabelSnapshot`.

## Requirements

- PHP `^8.1`
- Laravel `^9.0 || ^10.0 || ^11.0 || ^12.0 || ^13.0`

## License

MIT. See [LICENSE](LICENSE).
