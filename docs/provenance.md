# Provenance: actor, origin, correlation

[Back to README](../README.md)

Provenance is the heart of the package. Every record answers not only *what* changed, but *who executed it*, *who started it*, and *through what chain it reached the database*.

## The record shape

```json
{
    "event": "updated",
    "auditable": "App\\Models\\Order #42",
    "actor":  {"type": "job",  "label": "App\\Jobs\\ChargeOrder"},
    "origin": {"type": "user", "label": "John Doe"},
    "correlation_id": "550e8400-e29b-41d4-a716-446655440000",
    "changes": {"status": {"old": "pending", "new": "paid"}}
}
```

A user clicked "pay", a queued job made the write, and the log kept both.

## Actor

Who executed the change, resolved by an extensible provider chain:

- `user`: the authenticated user.
- `job`: a queued job (the job that performed the write).
- `command`: an Artisan command.
- `scheduler`: a scheduled task.
- `system`: no resolvable actor (fallback).

The chain is open: register your own provider to attribute changes to anything your app understands.

## Origin

Who *started* the chain. When a user dispatches a job, the user is serialized into the queue payload, so the origin survives a real queue worker. The audit row records both the immediate executor (actor) and the root cause (origin).

This is proven by a test that runs through a database queue worker, not just a synchronous dispatch.

## Correlation id

One id is assigned per unit of work (request, command, or job and its descendants). Every change made anywhere in that cascade shares the id, so a single action that fans out across many models is one query away:

```php
AuditLog::chain($correlationId); // every change in the cascade
```

The trace page draws the cascade as a ladder, indented by job-nesting depth, so a request that dispatched jobs that dispatched more jobs reads top to bottom.

## Impersonation

When an admin works as another user (login-as), the label names both:

```
Jane Doe (impersonated by Support Admin)
```

Session keys are configurable via `actor.impersonation_keys`, and `lab404/laravel-impersonate` works out of the box. Most audit packages attribute the change to the victim only.

## Foreign-key labels

Instead of `user_id: 5`, the record snapshots a human-readable label ("John Doe") at the moment of the event. It survives later deletion of the referenced row. Map the columns you want resolved in the config `labels.map`.
