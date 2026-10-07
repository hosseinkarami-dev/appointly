# Appointly deployment runbook

## Release checks

1. Run `php artisan test --compact`.
2. Run `npm run build`.
3. Run `php artisan migrate --force` during the release step.
4. Confirm `/up` and `/health/ready` return success.
5. Confirm the scheduler runs `php artisan schedule:run` every minute.
6. Confirm a queue worker is running with `php artisan queue:work --tries=3`.
7. Send a real test email through the configured production mail transport and verify delivery.
8. Confirm `APP_DEBUG=false`, HTTPS, production secrets, and trusted-host/proxy settings.
9. Confirm backups are enabled and complete a restore into a non-production environment.
10. Review the latest dependency audits and CI run before promoting the release.

## Laravel Cloud

Use separate staging and production environments. Configure `QUEUE_CONNECTION=database`, attach a managed database, enable a scheduler process, and use a managed queue worker when available. Do not write backups to the application filesystem because deployment instances are ephemeral.

Before production traffic, enable managed database backups and document the retention period, encryption, and restore owner. A backup is not considered verified until a restore has been performed against a non-production environment.

After environment or secret changes, redeploy the environment. Monitor the deployment to completion and check the queue worker, scheduler, database, and readiness endpoint. `/health/ready` currently verifies database connectivity and reports the configured queue connection; it does not prove that a worker is alive or processing jobs, so monitor the worker through the hosting platform.

## Incident checks

- `php artisan queue:failed` lists failed notification and webhook jobs.
- `php artisan queue:retry all` retries recoverable failures after the underlying issue is fixed.
- `php artisan schedule:list` confirms the reminder schedule is registered.
- `/health/ready` distinguishes database readiness from application HTTP availability.
