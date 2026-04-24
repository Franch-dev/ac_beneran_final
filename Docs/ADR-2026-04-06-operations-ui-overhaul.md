# ADR 2026-04-06: Operations UI Overhaul

## Status

Accepted

## Context

The dashboard and monitoring flows had grown organically:

- UI hierarchy was flat and visually inconsistent across cards, tables, and popups.
- Monitoring status badges were present but weak as operational signals.
- Dynamic popup rendering relied on mixed inline styles and duplicated behavior.
- Mobile monitoring UX depended mainly on horizontal table scrolling.
- Shared interaction code allowed duplicated handlers and unsafe HTML injection paths.

## Decision

Introduce a dedicated operations UI layer built on top of the existing Blade frontend.

### Design system

- Use Glassmorphism surfaces for shells and popups.
- Keep Google Material-inspired color semantics for status, emphasis, and elevation.
- Use a distinct display face for operational headings while preserving the existing app font for body copy.

### Structure

- Keep page ownership in Blade templates.
- Add `public/css/operations-ui-overhaul.css` and `public/css/ui-overhaul.css` as isolated override layers rather than expanding the legacy stylesheet further.
- Keep shared interaction behavior in `public/js/app.js`, with shared formatting and escaping helpers exposed through `resources/js/ui/runtime.js`.
- Keep page-specific workflow logic in `public/js/dashboard.js`, `public/js/monitoring.js`, and `public/js/workflow.js`.

### Safety and maintainability

- Escape server-provided strings before injecting them into HTML templates.
- Centralize popup state transitions through shared helpers.
- Remove duplicate mobile navigation behavior and rely on a single sidebar controller.
- Use class-based toast rendering instead of inline styles and raw HTML strings.

## Consequences

### Positive

- Dashboard cards now present clearer operational summaries and contact context.
- Monitoring now supports richer queue scanning on desktop and dedicated card views on mobile.
- Workflow and audit popups fit the same visual language as the main surfaces.
- Sidebar badges behave as meaningful notification indicators instead of static counters.
- The overhaul remains reversible because the new CSS is mostly isolated in one file.

### Tradeoffs

- The app still carries some legacy CSS and imperative DOM rendering in page scripts.
- There is temporary overlap between older patterns and the new override layer until a deeper refactor is done.
- Further cleanup should remove redundant legacy functions where later safe implementations now override them.

## Follow-Up

- Convert duplicated legacy render helpers into a single source of truth.
- Add more DOM-level tests for popup rendering and badge refresh behavior.
- Gradually move repeated UI fragments into reusable Blade partials.
