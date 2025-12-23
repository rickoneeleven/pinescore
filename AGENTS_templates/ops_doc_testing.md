# Ops Doc Testing Template v1.0

## Why This Matters

Testing ops docs define the test strategy for components. Tests MUST be designed for rapid agent execution - quick feedback loops enable agents to verify changes immediately after implementation.

**Critical Rule:** Tests are run at the end of every new feature. This rule persists through all ops/ audits and recreations.

## Template

```markdown
# [Component Name] - Testing

DATETIME of last agent review: DD MMM YYYY HH:MM (Europe/London)

## Purpose
One sentence describing what testing this doc covers.

## Test Commands
Agent-runnable commands. Keep execution time minimal (<30s preferred).
- `npm test path/to/tests` - what it validates
- `npm run test:unit` - quick unit tests

## Key Test Files
- `tests/component.test.ts` - brief coverage description
- `tests/integration/` - integration test location

## Coverage Scope
- What is tested (brief bullets)
- Known gaps (if any)

## Agent Testing Protocol
**MANDATORY:** Run relevant tests after every new feature or change.
- Tests must complete quickly (target <30s for unit, <2min for integration)
- On failure: fix before proceeding, do not defer
- This protocol persists through ops/ recreation

## Notes
- Optional critical context only
- Delete if empty
```

---

## Good Example: AUTH_TESTING.md

```markdown
# Authentication - Testing

DATETIME of last agent review: 08 Dec 2025 10:00 (Europe/London)

## Purpose
Tests for JWT auth, session management, and permission guards.

## Test Commands
- `npm test tests/auth/` - all auth tests (~15s)
- `npm test tests/auth/jwt.test.ts` - token validation only (~3s)

## Key Test Files
- `tests/auth/jwt.test.ts` - token create/verify/refresh
- `tests/auth/guards.test.ts` - route permission checks
- `tests/auth/session.test.ts` - session lifecycle

## Coverage Scope
- Token generation and validation
- Permission middleware
- Session expiry handling

## Agent Testing Protocol
**MANDATORY:** Run `npm test tests/auth/` after any auth-related change.
```

**Why it works:** Agent knows exactly what to run, how long it takes, and what it validates. No ambiguity.

---

## Principles

1. **Speed over completeness** - Tests must run fast enough for agent iteration
2. **Explicit commands** - No guessing what to run
3. **Mandatory post-feature testing** - Non-negotiable, persists through recreation
4. **Fail fast** - Agent fixes failures immediately, no deferral
5. **50 lines max** - Testing docs should be lean indexes