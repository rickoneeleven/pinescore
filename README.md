DATETIME of last agent review: 24/09/2025 13:14 BST

# Pinescore ICMP Monitoring

Pinescore is a CodeIgniter 2.2.0 application that tracks ICMP latency and packet loss. The project runs on a classic LEMP stack and uses scheduled HTTP calls to keep scores and reports current.

## Requirements

- MySQL 5.7 (works on MariaDB 10.5.15; MySQL 8 fails because of required sql_mode)
- PHP 5.6 for the application (test runner needs PHP 7.0 or newer)
- 10 GB system memory available, swap included
- Lynx command line browser for cron driven HTTP calls

## Quick Start

### 1. Prepare the database

```bash
mysqladmin -u root -p create pinescore
mysql -u root -p pinescore < database_structure.sql
```

### 2. Copy base configuration

```bash
cp application/config/config.php.example application/config/config.php
cp application/config/database.php.example application/config/database.php
```

### 3. Application configuration

Edit `application/config/config.php` and set:

- `$dev_domain_tld` to match the local development domain (for production leave as is)
- `from_email` to a valid sender address for outbound notifications
- `$config['encryption_key']` to a strong random string

Update `application/models/email_dev_or_no.php` to decide when development environments send alert emails.

### 4. Database credentials and cron protection

Edit `application/config/database.php` and set the connection credentials:

```php
'username' => 'db_user',
'password' => 'harrylikesherchainberofsecrets',
'database' => 'pinescore',
```

Update `application/models/cron_protect.php` with the server's real local IP (not 127.0.0.1).

### 5. MySQL SQL mode

Append the SQL mode block to `/etc/mysql/my.cnf` and restart MySQL:

```ini
[mysqld]
sql_mode = STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION
```

```bash
sudo service mysql restart
```

This prevents ONLY_FULL_GROUP_BY errors when running API queries.

### 6. Web server tuning

On Webmin Hosting (or equivalent), the ping engine operates reliably with:

- PHP script execution mode: CGI Wrapper
- Maximum PHP script run time: 3600 seconds

After setup, confirm the daemon endpoint responds:

```bash
lynx --dump https://pinescore.com/daemon/bitsNbobs/updatepinescore
```

## Scheduled Jobs

All cron entries rely on `lynx --dump` to hit application endpoints. Install these on the production scheduler.

| Schedule | Purpose | Command |
| --- | --- | --- |
| `0 0 * * *` | Build offset and baseline score stats (daily) | `lynx --dump http://pinescore.com/api_ping/ > /dev/null 2>&1` |
| `26 04 * * *` | Nightly cleanup tasks | `lynx --dump https://pinescore.com/api_nightly/onceAday > /dev/null 2>&1` |
| `23,33,44 17 * * 1-5` | Promote short term scores to long term during weekdays | `lynx --dump http://pinescore.com/api_ping/longTermGroupScores > /dev/null 2>&1` |
| `24 * * * *` | Cleanup tables and log short term group scores | `lynx --dump https://pinescore.com/api_nightly/ > /dev/null 2>&1` |
| `*/5 * * * *` | Flush ping_result_table when thresholds are met | `lynx --dump https://pinescore.com/api_nightly/flushPingResultTable > /dev/null 2>&1` |
| `* * * * *` | Recalculate Pinescore for each monitored IP | `lynx --dump https://pinescore.com/daemon/bitsNbobs/updatepinescore > /dev/null 2>&1` |
| `31 08 * * *` | Daily latency alert check | `lynx --dump https://pinescore.com/daemon/bitsNbobs/checkChangeOfCurrentMsAgainstLTA > /dev/null 2>&1` |
| `35 00,06,12,18 * * *` | Update 30-day average latency | `lynx --dump https://pinescore.com/daemon/average30days > /dev/null 2>&1` |
| `10 09 * * *` | Purge uploads older than 30 days | `find /home/pinescore/public_html/111/* -mtime +30 -type f -delete` |
| `11 11 11 * *` | Touch static files to keep timestamps fresh | `touch /home/pinescore/public_html/111/ns_*` |

Additional cron behavior:

- `api_nightly` truncates `ping_result_table` when AUTO_INCREMENT exceeds 1,000,000,000 and records the event in `health_dashboard` with metric `ping_table_last_truncation`.
- Crons below the deletion task are optional for basic uptime monitoring but recommended for hygiene.

## Database export notes

When exporting `database_structure.sql`, omit tables managed by the Laravel-based engine project migrations:

- `failed_jobs`
- `traceroutes`
- `group_monthly_scores`

## Troubleshooting

- If node management pages complain about header size, set `$config['sess_use_database'] = true` in `application/config/config.php` and ensure the session table from `database_structure.sql` exists.

## Related documentation

- Tests live in `tests/` with instructions in `tests/README.md`.
- Debug helper guidance is under `application/helpers/codeigniter-developers-debug-helper/README.md`.
