# System Hint

## Known Frontend Patterns
- Blade + inline JS is fragile
- Always use @json() when passing data

## Common Failures
- Undefined JS variables → scope or escaping issue
- Function not defined → script loading issue

## Preferred Fix Style
- Minimal changes
- No architecture refactor
- Add guards instead of rewriting logic
