# ADR: Safe Client Rendering for Workflow Modals

## Status

Accepted

## Context

Several operational popups and timeline panels were built by concatenating HTML strings with data coming from the backend:

- service-order detail modal
- workflow timeline modal
- technician assignment modal
- dashboard detail and edit helpers

That created avoidable XSS risk and brittle quoting behavior, especially around inline `onclick` handlers and freeform notes.

## Decision

Adopt a shared client-side escaping strategy:

- define `window.escapeHtml` in `public/js/app.js`
- use escaped values before dynamic text is interpolated into HTML strings
- replace fragile `addslashes(...)` Blade bindings with `@js(...)` for inline action arguments
- move live status-badge polling into `public/js/app.js` so the monitoring counters are refreshed from a single shared path

## Consequences

Positive:

- workflow and detail popups no longer trust raw backend strings
- inline button bindings are materially safer and less error-prone
- status badges refresh consistently outside the monitoring page

Tradeoffs:

- legacy imperative rendering remains in place, so the escaping discipline must be preserved in future changes
- some files now intentionally override older helper implementations to keep the migration incremental

## Follow-up

- migrate the remaining popup builders to DOM-node construction where practical
- add targeted frontend tests for escaped rendering of notes and actor names
