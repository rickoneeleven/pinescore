# Scheduled Maintenance

DATETIME of last agent review: 23 Dec 2025 12:01 (Europe/London)

## Purpose

Cron-driven database cleanup and node expiry via HTTP endpoints.

## Key Files

- `application/controllers/api_nightly.php` - cleanup endpoints
- `application/models/table_maintenance_model.php` - ping_result_table truncation logic

## Endpoints

| Endpoint | Cron | Action |
|----------|------|--------|
| `/api_nightly/` | `24 * * * *` | Hourly cleanup (see below) |
| `/api_nightly/flushPingResultTable` | `*/5 * * * *` | Delete unchanged ping results > 24h |
| `/api_nightly/onceAday` | `26 04 * * *` | Reset count_direction flags |

## Hourly Cleanup (`index`)

| Table | Retention | Notes |
|-------|-----------|-------|
| `ping_result_table` | 7 days | Also truncated if AUTO_INCREMENT > 1B |
| `ci_sessions` | 7 days | Garbage collection fallback |
| `verify_email` | 48 hours | |
| `stats` | 48 hours | |
| `stats_total` | 48 hours | |
| `historic_pinescore` | 3 years | |
| `traceroutes` | 90 days | |
| `ping_ip_table` | 1 month inactive | Sends expiry email, deletes node + alerts |

## Agent Commands

```bash
lynx --dump https://pinescore.com/api_nightly/
```

## Notes

- Truncation at 1B AUTO_INCREMENT prevents integer overflow on ping_result_table
