DATETIME of last agent review: 23 Dec 2025 11:38 (Europe/London)

# Pinescore ICMP Monitoring

CodeIgniter 2.2.0 application that tracks ICMP latency and packet loss for monitored nodes.

## Stack

- PHP 5.6 (application runtime; tests require PHP 7.0+)
- MySQL 5.7 / MariaDB 10.5.15 (MySQL 8 fails due to sql_mode requirements)
- Lynx browser (cron-driven HTTP calls)
- 10 GB system memory available (including swap)

## Quick Start

```bash
mysqladmin -u root -p create pinescore
mysql -u root -p pinescore < database_structure.sql
cp application/config/config.php.example application/config/config.php
cp application/config/database.php.example application/config/database.php
```

Edit database credentials in `application/config/database.php`, then confirm the daemon responds:

```bash
lynx --dump https://pinescore.com/daemon/bitsNbobs/updatepinescore
```

## First-Time Server Setup

### MySQL SQL Mode

Append to `/etc/mysql/my.cnf` and restart MySQL:

```ini
[mysqld]
sql_mode = STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION
```

```bash
sudo service mysql restart
```

This prevents ONLY_FULL_GROUP_BY errors.

### Web Server Tuning

On Webmin Hosting (or equivalent):
- PHP script execution mode: CGI Wrapper
- Maximum PHP script run time: 3600 seconds

### Crontab

All entries use `lynx --dump` to hit application endpoints.

| Schedule | Purpose | Command |
| --- | --- | --- |
| `0 0 * * *` | Daily offset/baseline stats | `lynx --dump http://pinescore.com/api_ping/ > /dev/null 2>&1` |
| `26 04 * * *` | Nightly cleanup | `lynx --dump https://pinescore.com/api_nightly/onceAday > /dev/null 2>&1` |
| `23,33,44 17 * * 1-5` | Promote short to long term scores (weekdays) | `lynx --dump http://pinescore.com/api_ping/longTermGroupScores > /dev/null 2>&1` |
| `24 * * * *` | Hourly cleanup and short term scores | `lynx --dump https://pinescore.com/api_nightly/ > /dev/null 2>&1` |
| `*/5 * * * *` | Flush ping_result_table on threshold | `lynx --dump https://pinescore.com/api_nightly/flushPingResultTable > /dev/null 2>&1` |
| `* * * * *` | Recalculate Pinescore per IP | `lynx --dump https://pinescore.com/daemon/bitsNbobs/updatepinescore > /dev/null 2>&1` |
| `31 08 * * *` | Daily latency alert check | `lynx --dump https://pinescore.com/daemon/bitsNbobs/checkChangeOfCurrentMsAgainstLTA > /dev/null 2>&1` |
| `35 00,06,12,18 * * *` | Update 30-day average latency | `lynx --dump https://pinescore.com/daemon/average30days > /dev/null 2>&1` |
| `10 09 * * *` | Purge uploads older than 30 days | `find /home/pinescore/public_html/111/* -mtime +30 -type f -delete` |
| `0 0 * * 0` | Touch ns_* files weekly to prevent deletion | `find /home/pinescore/public_html/111 -name 'ns_*' -type f -exec touch {} +` |

Note: `api_nightly` truncates `ping_result_table` when AUTO_INCREMENT exceeds 1,000,000,000 and logs to `health_dashboard`.

## Configuration

Edit `application/config/config.php`:
- `$dev_domain_tld` - local development domain (leave default for production)
- `from_email` - sender address for notifications
- `$config['encryption_key']` - strong random string

Edit `application/config/database.php`:
```php
'username' => 'db_user',
'password' => 'your_password',
'database' => 'pinescore',
```

Edit `application/models/cron_protect.php` with server's real local IP (not 127.0.0.1).

Edit `application/models/email_dev_or_no.php` to control dev environment email behavior.

## Database Export Notes

When exporting `database_structure.sql`, omit tables managed by the Laravel engine project:
- `failed_jobs`
- `traceroutes`
- `group_monthly_scores`

## Troubleshooting

### Header Size Complaints on Node Management

Set `$config['sess_use_database'] = true` in `application/config/config.php` and ensure the session table from `database_structure.sql` exists.

### Debug Helper

Load the vdebug helper for variable inspection:
```php
$this->load->file(APPPATH.'helpers/codeigniter-developers-debug-helper/vayes_helper.php');
vdebug($data, $die = false, $add_var_dump = false, $add_last_query = true);
```

## Links

- Operations docs: `ops/`
- Test suite: `tests/` (run with `php tests/run-tests.php`)
