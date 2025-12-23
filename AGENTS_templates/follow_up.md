# Follow Up (Template)

## Purpose (keep this section)
`follow_up.md` is a short-lived document used to:
1. Define a mini PRD for a new feature (requirements + acceptance criteria).
2. Track a post-deploy validation checklist that gets ticked off across sessions.

This is not a forever-growing document. When a feature is validated, delete its entire section.

## Session Protocol (agent + human)
- Each session, read `follow_up.md` early.
- Prioritize completing unchecked validation items and removing finished sections.
- Every validation checkbox must include:
  - The exact command/URL/query to run
  - The expected success signal
  - A place to paste evidence (response snippet, rows, log lines)
- Update `Last validation run:` when validation work is performed.

## Conventions
- Use `- [ ]` for unchecked and `- [x]` for complete.
- Keep each feature section small. Prefer links to code paths over large explanations.
- If the file is empty, keep only `# Follow Up` and the next section heading for the first feature.

---

# Feature Section Template (copy per feature)

## Feature: <short name>
Status: Planning | Implementing | Validating | Done
Target env: prod | staging | local
Owner:
Created:
Deployed:
Last touched:

### Problem
- What is broken or missing
- Who it affects
- Why it matters

### Proposal
- What is changing at a high level
- Non-goals (explicit exclusions)

### Acceptance Criteria
- [ ] AC1:
- [ ] AC2:

### Implementation Plan (staged)
- [ ] Stage 1:
- [ ] Stage 2:
- [ ] Stage 3:

### Rollout
- [ ] Deployment steps:
- [ ] Config/env changes:

### Validation Checklist (post-deploy)
- [ ] Check: <name>
  - Command/URL:
  - Expect:
  - Evidence:

- [ ] Check: <name>
  - Command/URL:
  - Expect:
  - Evidence:

### Monitoring Window
- [ ] Monitor for <duration>:
  - Metric/log:
  - Expect:
  - Evidence:

### Rollback Plan
- Trigger:
- Action:
- Verification:

### Cleanup (delete section when complete)
- [ ] All validation items done
- [ ] Monitoring window complete
- [ ] No open incidents