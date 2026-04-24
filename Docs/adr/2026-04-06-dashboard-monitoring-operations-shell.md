# ADR: Dashboard and Monitoring Operations Shell

## Status

Accepted

## Context

The dashboard and monitoring pages had already accumulated several UI layers:

- legacy global CSS in `public/css/style.css`
- extra enhancement stylesheets
- page-specific Blade markup with dense operational content
- imperative frontend scripts for popups and workflow actions

The result was visually inconsistent and difficult to evolve. Monitoring also had paginated data without visible pagination controls, and several counters were page-local instead of system-wide.

## Decision

Introduce a dedicated operations-shell styling layer for the two operational pages:

- keep the legacy global CSS as the base contract
- keep `public/css/ui-overhaul.css` for broad glass/material primitives
- add `public/css/operations-ui-overhaul.css` for page-level hero, pagination, dense action layouts, mobile monitoring cards, and final operational refinements
- expose system-wide metrics from controllers instead of deriving key KPIs from the current paginator page only
- keep the Blade templates server-rendered and progressively enhance with lightweight JS

## Consequences

Positive:

- dashboard and monitoring now share a coherent glass/material system
- pagination is visible for both service orders and urgency/location lists
- KPI cards represent global operational state instead of page-local slices
- the missing stylesheet reference in the layout is now satisfied by a concrete file

Tradeoffs:

- there are now multiple CSS layers to reason about
- new work should stay scoped to the operations stylesheets instead of re-expanding `style.css`

## Follow-up

- move more repeated hero and toolbar markup into reusable Blade partials if the pattern spreads beyond these two pages
- add visual regression checks if the team standardizes screenshot testing
