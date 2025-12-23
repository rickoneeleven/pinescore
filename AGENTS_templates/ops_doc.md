# Ops Doc Template v2.0

## Purpose

All ops/ docs are **ingested at session start**. They exist for agent awareness - telling the agent WHAT exists and WHERE to find it. The agent reads source files on-demand when actually working on a component.

README tells humans HOW to deploy. ops/ tells agents WHAT exists.

## Constraints

- **Max 40 lines per doc** - ingested at startup, so brevity matters
- **Awareness, not instruction** - agent needs to know what exists, not step-by-step guides
- **Pointers, not content** - list file paths, let agent read them when needed
- **Commands must be copy-paste ready** - no placeholders like `<your-path>`

## Template

```markdown
# [Component Name]

DATETIME of last agent review: DD MMM YYYY HH:MM (Europe/London)

## Purpose
One sentence describing what this component does.

## Key Files
- `path/to/main.ts` - brief role
- `path/to/other.ts` - brief role

## Related
- `ops/OTHER_DOC.md` - cross-cutting dependency
- External service/config location

## Agent Commands
```bash
# Copy-paste ready commands for this component
command --with --real --args
```

## Notes
- Only critical context not obvious from code (1-3 bullets max)
- Delete this section if empty

## Intentional Behavior
- Non-obvious design decisions that future agents might question
- Prevents agents from "fixing" things that aren't broken
- Delete this section if empty
```

---

## Good Example

```markdown
# Horizon Queue Management

DATETIME of last agent review: 15 Dec 2025 14:00 (Europe/London)

## Purpose
Laravel Horizon manages ping and traceroute job queues via Redis, running under Supervisor.

## Key Files
- `config/horizon.php` - queue supervisors config
- `app/Jobs/PingJob.php` - ping execution
- `app/Jobs/TracerouteJob.php` - traceroute execution

## Related
- `/etc/supervisor/conf.d/horizon.conf` - Supervisor unit
- Redis on localhost:6379

## Agent Commands
```bash
php artisan horizon:terminate
sudo supervisorctl restart horizon
sudo supervisorctl status horizon
tail -f storage/logs/horizon.log
```

## Notes
- Dashboard at `/horizon` (web middleware only)
- Traceroute needs CAP_NET_RAW on `/usr/sbin/traceroute`

## Intentional Behavior
- Separate queues for ping/traceroute prevent hourly traceroute backlog from delaying per-minute pings
```

**32 lines. Agent knows what exists, where to look, and has ready commands.**

---

## Bad Example

```markdown
# Horizon Queue Management Guide

DATETIME of last agent review: 15 Dec 2025 14:00 (Europe/London)

## Overview
Laravel Horizon is a queue manager that provides a beautiful dashboard and code-driven configuration for your Laravel powered Redis queues. It allows you to easily monitor key metrics of your queue system such as job throughput, runtime, and job failures.

## Installation
1. First, install Horizon via Composer:
   composer require laravel/horizon

2. Publish the configuration:
   php artisan horizon:publish

3. Configure your supervisors in config/horizon.php...

[continues for 100+ lines with full setup guide]
```

**Why it fails:**
- 100+ lines ingested at startup wastes context
- Installation instructions belong in README
- Overview paragraph adds no value for agents
- Full guide duplicates README content

---

## What Goes Where

| Content Type | Location |
|--------------|----------|
| First-time setup, installation | README |
| Service config examples | README |
| Troubleshooting procedures | README |
| Component file locations | ops/ |
| Agent-ready commands | ops/ |
| Design decisions | ops/ |

---

## Section Rules

### Purpose
- One sentence only
- What it does, not how it works

### Key Files
- 3-8 files max
- Only files agent would need to read/modify
- Include brief role (3-5 words)

### Agent Commands
- Must work as-is (no placeholders)
- Common operations only
- 4-6 commands max

### Notes
- Only include if critical and not obvious from code
- Max 3 bullets
- Delete section if nothing qualifies

### Intentional Behavior
- Design decisions that look like bugs/oversights
- Prevents agents from "fixing" correct behavior
- Delete section if nothing qualifies