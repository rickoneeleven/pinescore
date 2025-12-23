# README Template v3.0

## Purpose

Get a new contributor from clone to running code, AND provide first-time server setup instructions. README is for humans doing deployment - it is NOT ingested by agents at startup, so it can contain operational depth.

Agent awareness lives in `ops/` docs (which ARE ingested).

## Principles

1. **Source of truth is the repo** - scan for commands, don't invent them
2. **Omit sections with no evidence** - no placeholders, no guesses
3. **Section budgets prevent bloat** - each section has a max line count
4. **Operational depth allowed** - first-time setup, troubleshooting, real procedures
5. **Point to ops/ for component awareness** - README says HOW to deploy, ops/ says WHAT exists

## Section Structure

Fixed sections with line budgets. **Omit sections that don't apply.**

| Section | Max Lines | Required | Purpose |
|---------|-----------|----------|---------|
| Title + Purpose | 5 | Yes | Project name, one-line description |
| Stack | 8 | Yes | Runtime versions, dependencies |
| Quick Start | 12 | Yes | Clone to running - happy path only |
| First-Time Server Setup | 60 | If applicable | Supervisor, systemd, capabilities, crontab, sysctl tweaks |
| Configuration | 25 | If applicable | Env vars, config files, tuning options |
| Common Operations | 20 | If applicable | Restart, clear cache, post-deploy checklist |
| Troubleshooting | 40 | If applicable | Known issues with tested solutions |
| Links | 5 | If applicable | Dashboard URLs, related repos |

**Total max: ~175 lines.** Most READMEs will be shorter - only use sections you need.

## Section Guidelines

### Title + Purpose
```markdown
# Project Name

One sentence: what this does and for whom.
```

### Stack
List runtime requirements with version sources:
```markdown
## Stack
- PHP 7.4+
- Laravel 7.30.3 (pinned in composer.json)
- Redis
- MariaDB/MySQL
- Supervisor (process management)
```

### Quick Start
The happy path ONLY. Assume dependencies are installed. Get to "it runs" fast:
```markdown
## Quick Start
git clone <repo>
cp .env.example .env
composer install
php artisan key:generate
php artisan migrate
php artisan horizon
```

### First-Time Server Setup
This is where operational depth lives. Include:
- Service installation (redis, supervisor)
- Config file examples (supervisor conf, systemd units)
- Capability/permission setup
- Crontab entries
- System tweaks (sysctl, etc.)

Use code blocks for config examples. This section can be detailed - it's for humans doing first-time deployment.

### Configuration
Document env vars that need explanation beyond their name:
```markdown
## Configuration
Required env vars - see `.env.example`:
- `CONTROL_IP_1`, `CONTROL_IP_2` - failsafe ping targets; engine checks these before marking nodes down
- `PING_DEADLINE_SECONDS` - total time for ping command (default 2)
```

### Common Operations
Copy-paste commands for routine tasks:
```markdown
## Common Operations
# After code changes
php artisan config:clear && php artisan cache:clear
php artisan horizon:terminate
sudo supervisorctl restart horizon
```

### Troubleshooting
Known issues with TESTED solutions. Each entry:
- Problem description
- How to diagnose
- Exact fix commands

```markdown
## Troubleshooting

### Runaway traceroute queue
If traceRoute queue balloons with duplicates:
1. Check depth: `redis-cli LLEN prefix_queues:traceRoute`
2. Clear: `redis-cli DEL prefix_queues:traceRoute`
3. Repopulate: `php artisan run:trace-route`
```

## Skeleton

```markdown
# Project Name

One sentence: what this does and for whom.

## Stack
- Runtime 1
- Runtime 2

## Quick Start
```bash
git clone <repo>
cp .env.example .env
# install commands
# run command
```

## First-Time Server Setup

### Redis
sudo apt install redis-server

### Supervisor
sudo apt install supervisor

Create `/etc/supervisor/conf.d/app.conf`:
```ini
[program:app]
command=/usr/bin/php /path/to/artisan horizon
autostart=true
autorestart=true
user=appuser
```

sudo supervisorctl reread && sudo supervisorctl update

### Crontab
```
* * * * * cd /path/to/app && php artisan schedule:run >> /dev/null 2>&1
```

## Configuration
Required env vars - see `.env.example`:
- `VAR_NAME` - explanation

## Common Operations
```bash
# Restart services
php artisan horizon:terminate
sudo supervisorctl restart app
```

## Troubleshooting

### Issue Name
Symptom and diagnosis steps.
Fix: `exact command`

## Links
- [Dashboard](https://...)
- Operations docs: `ops/`
```

## Validation Checklist

- [ ] Every command tested or verified in repo
- [ ] Section budgets respected
- [ ] Empty sections omitted (not left as placeholders)
- [ ] Points to `ops/` for component awareness
- [ ] No marketing copy, badges, or screenshots
