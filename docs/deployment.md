# Appointly deployment runbook

## Release checks

1. Run `php artisan test --compact`.
2. Run `npm run build`.
3. Run `php artisan migrate --force` during the release step.
4. Confirm `/up` and `/health/ready` return success.
5. Confirm the scheduler runs `php artisan schedule:run` every minute.
6. Confirm a queue worker is running with `php artisan queue:work --tries=3`.

## Laravel Cloud

Use separate staging and production environments. Configure `QUEUE_CONNECTION=database`, attach a managed database, enable a scheduler process, and use a managed queue worker when available. Do not write backups to the application filesystem because deployment instances are ephemeral.

Before production traffic, enable managed database backups and document the retention period, encryption, and restore owner. A backup is not considered verified until a restore has been performed against a non-production environment.

After environment or secret changes, redeploy the environment. Monitor the deployment to completion and check the queue, scheduler, database, and readiness endpoint.

## Incident checks

- `php artisan queue:failed` lists failed notification and webhook jobs.
- `php artisan queue:retry all` retries recoverable failures after the underlying issue is fixed.
- `php artisan schedule:list` confirms the reminder schedule is registered.
- `/health/ready` distinguishes database readiness from application HTTP availability.
