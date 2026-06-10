# Yammi Audit Log for Laravel

Universal change history and audit log for Laravel. It records who changed what and when across your Eloquent models, with rich actor attribution, field-level diffs, human-readable relationship labels and a timeline dashboard.

## Why

Existing audit packages answer *what* changed but rarely *who* really changed it. A status flip by a queued job, an Artisan command or the scheduler all collapse into an anonymous `null`. This package treats actor attribution as a first-class concern: user, job, command, scheduler or system.

## Status

Early development. The public API is not stable yet.

## Requirements

- PHP `^8.1`
- Laravel `^9.0 || ^10.0 || ^11.0 || ^12.0 || ^13.0`

## License

MIT. See [LICENSE](LICENSE).
