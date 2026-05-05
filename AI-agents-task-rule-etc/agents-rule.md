# AI Agent Execution Rules

## Core Principles
- Always understand before modifying code
- Work step-by-step, never all at once
- Stay within defined scope

## File Safety
- Always create checkpoint before modifying files
- Prefer minimal changes over large edits
- Never delete code unless necessary

## Debugging Rules
- Identify root cause, not symptoms
- Validate after every change
- Rollback immediately if validation fails

## Blade + JS Rules
- Never inject raw Blade variables into JS
- Always use @json() for data passing
- Avoid inline onclick when possible (optional improvement)

## JavaScript Rules
- Functions used in onclick must be attached to window
- Ensure scripts are loaded correctly (defer or end of body)

## Laravel Rules
- Do not modify routes or controllers unless explicitly required
- Do not change database structure

## Failure Handling
- Retry only once after self-improvement
- If still failing, stop and report clearly

## Output
- Always report:
  - files changed
  - reason
  - validation result

## Validation Priority Rule

- A fix is NOT considered successful until:
  - No backend errors
  - No frontend JS errors
  - UI behaves correctly

- If validation is incomplete:
  → Treat as failure

  
