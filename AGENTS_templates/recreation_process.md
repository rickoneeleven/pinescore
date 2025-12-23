# Doc Recreation Process v2.0

## Trigger

Any README.md or ops/*.md file with `DATETIME of last agent review` older than 30 days, or missing entirely.

## Why Rebuild vs Patch

Patching stale docs creates inconsistent Frankenstein files. Rebuilding from scratch using templates ensures docs match current code with no legacy cruft.

## Documentation Philosophy

**README** = HOW to deploy (for humans, NOT ingested by agent)
- First-time setup, installation, configuration
- Troubleshooting procedures
- Can be detailed (~175 lines max with section budgets)

**ops/** = WHAT exists (for agents, ingested at startup)
- Component awareness and file locations
- Copy-paste ready commands
- Must be concise (40 lines max per doc)

## Documentation Structure

```
README.md              <- deployment guide for humans
ops/
  COMPONENT.md         <- agent awareness docs
  TESTING.md           <- if tests need special setup
  ...
```

**Rules:**
- ONE README.md at project root only
- NO README.md files in subfolders
- ALL component awareness docs in ops/
- Vendor/node_modules/dist/build/venv excluded from all operations

## Process

### 1. Context Gathering
- Read existing README.md and all ops/*.md files
- Note what topics they covered
- Identify project-specific terminology

### 2. Codebase Crawl

| Target | What to Extract |
|--------|-----------------|
| `package.json` / `composer.json` | Stack, scripts |
| `.nvmrc` / `.tool-versions` | Runtime versions |
| `.env.example` | Required config vars |
| Service configs (systemd, supervisor) | Service management |
| `src/` or `app/` | Component structure |
| `database/migrations/` | Schema context |
| `tests/` | Test framework, complexity |

### 3. Delete ALL Documentation
```bash
find . -name "*.md" \
  -not -path "./node_modules/*" \
  -not -path "./vendor/*" \
  -not -path "./.git/*" \
  -not -path "./dist/*" \
  -not -path "./build/*" \
  -not -path "./venv/*" \
  -not -path "./AGENTS_templates/*" \
  -delete
```

### 4. Recreate README
Use `AGENTS_templates/reed_me.md` template.

Include sections as needed:
- Title + Purpose (required)
- Stack (required)
- Quick Start (required)
- First-Time Server Setup (if applicable)
- Configuration (if applicable)
- Common Operations (if applicable)
- Troubleshooting (if applicable)

**Preserve operational knowledge** from old README - setup procedures, troubleshooting solutions, config examples. This content belongs here, not in ops/.

### 5. Recreate ops/ Docs
Use `AGENTS_templates/ops_doc.md` template.

Create docs for components that exist:

| If You Find | Create |
|-------------|--------|
| Service management (systemd/supervisor) | `ops/SERVICES.md` |
| Queue workers, background jobs | `ops/WORKERS.md` or `ops/QUEUES.md` |
| Database with complex schema | `ops/DB.md` |
| External API integrations | `ops/INTEGRATIONS.md` |
| Complex test setup | `ops/TESTING.md` |

**Max 40 lines per doc.** Focus on file locations and agent commands, not procedures.

### 6. Verify
- [ ] README has all operational knowledge from original
- [ ] README respects section budgets
- [ ] Each ops/ doc under 40 lines
- [ ] All file paths in ops/ docs exist
- [ ] All timestamps updated

## Content Placement Guide

| Content Type | Goes In |
|--------------|---------|
| How to install dependencies | README |
| Service config examples | README |
| Crontab entries | README |
| System tweaks (sysctl, caps) | README |
| Troubleshooting procedures | README |
| Component file locations | ops/ |
| Agent commands | ops/ |
| Design decisions | ops/ |

## What NOT to Do

- Don't lose operational knowledge from old README
- Don't put setup procedures in ops/ docs
- Don't exceed 40 lines in ops/ docs
- Don't create ops/ docs for components that don't exist
- Don't include marketing copy or badges
- Don't leave placeholder sections