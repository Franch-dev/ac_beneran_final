Act as a Senior Software Architect, Codebase Refactoring Specialist, and Risk-Controlled AI Engineering Agent.

Your task is to analyze my entire codebase and create a highly detailed, safe, phased refactoring plan that will make the project:

- cleaner
- more maintainable
- more consistent
- easier to navigate
- less fragmented where appropriate
- less repetitive
- better organized

The goal is to improve the internal structure of the codebase without changing, breaking, removing, or weakening any existing feature, workflow, endpoint, UI behavior, business logic, permission rule, or integration.

IMPORTANT HARD RULE:
Do NOT execute any refactor.
Do NOT modify files.
Do NOT delete files.
Do NOT rename files.
Do NOT move files.
Do NOT generate replacement code.
Do NOT apply patches.
Do NOT perform any implementation yet.

At this stage, you are only allowed to:
1. inspect
2. analyze
3. map dependencies
4. identify safe refactor opportunities
5. create a detailed execution-ready refactor plan

The actual execution will only happen after I explicitly approve the plan in a later message.

---

# Primary Objective

Create a complete “Safe Refactor Master Plan” for the entire codebase.

The plan must aim to:

1. Reduce unnecessary file fragmentation only when consolidation is genuinely beneficial.
2. Reduce duplicated logic, duplicated helpers, duplicated UI patterns, and repeated backend logic.
3. Improve naming consistency.
4. Improve directory organization.
5. Improve separation of concerns.
6. Improve code readability and maintainability.
7. Identify dead code, obsolete files, unused imports, unused methods, and abandoned components — but do not remove anything yet.
8. Identify oversized files that may need splitting.
9. Identify overly fragmented files that may be safely merged.
10. Preserve the current application behavior exactly unless I later approve specific behavior changes.

Do not blindly minimize file count. Fewer files is not automatically better.  
Your recommendations must preserve modularity, readability, framework conventions, and maintainability.

---

# Required Analysis Process

Perform your analysis in the following order:

## Phase 1 — Codebase Inventory

Create a structured overview of:

- top-level folders
- major modules
- frontend areas
- backend areas
- routes
- controllers
- services
- models
- views/templates/components
- utilities/helpers
- assets
- configuration files
- tests if present
- docs if present

Explain the purpose of each major area.

---

## Phase 2 — Architecture Understanding

Infer and document:

- current application architecture
- project style/patterns being used
- whether the code follows MVC, modular monolith, service-layer pattern, feature-based modules, or mixed style
- dependencies between major folders/modules
- important business workflows
- sensitive or high-risk areas that should not be casually refactored

Highlight the parts of the codebase that appear central to system stability.

---

## Phase 3 — Dependency and Risk Mapping

Before proposing refactors, identify:

- files with many inbound dependencies
- files used across multiple modules
- central shared services
- shared database models
- shared Blade/UI components or templates if relevant
- global utilities
- middleware/auth/permission logic
- API endpoints that may be touched indirectly
- fragile workflow logic
- transaction-sensitive flows
- anything that could break multiple features if refactored incorrectly

Classify risk levels:

- Low Risk
- Medium Risk
- High Risk
- Critical / Requires extra validation

Explain why each risky area has that classification.

---

## Phase 4 — Refactor Opportunity Audit

Create a detailed list of refactor opportunities, grouped by category:

### A. File and Folder Organization
- folders that may need restructuring
- misplaced files
- inconsistent folder naming
- modules that should be grouped better

### B. File Count Optimization
- overly fragmented files that may be merged safely
- very small files that add complexity without real value
- redundant files with overlapping purpose
- warnings where merging is NOT recommended

### C. Large File Cleanup
- files that are too large and should possibly be split
- large controller/service/view files
- files mixing unrelated responsibilities

### D. Duplicate Logic
- repeated logic across controllers/services/views/scripts
- repeated query logic
- repeated validation logic
- repeated formatting logic
- duplicated frontend scripts/components

### E. Naming and Consistency
- inconsistent naming conventions
- confusing class/function/file names
- inconsistent route or module naming

### F. Dead or Potentially Unused Code
- unused imports
- unreferenced files
- outdated comments
- obsolete helper methods
- possible dead routes/features

Do not recommend deletion unless you provide evidence and mark it as “requires confirmation before execution.”

### G. Maintainability Improvements
- reduce coupling
- introduce clearer boundaries
- extract reusable services/helpers/components where beneficial
- simplify logic without changing behavior

---

## Phase 5 — Safe Refactor Strategy

Create a phased implementation roadmap that could be executed later.

Each phase must include:

1. Phase name
2. Goal
3. Exact scope
4. Files or folders likely involved
5. Why the phase is safe or risky
6. Preconditions before execution
7. Refactor actions that would be performed
8. Validation/tests required after the phase
9. Rollback strategy if issues occur
10. Whether the phase should be:
   - required
   - recommended
   - optional

The roadmap should be ordered from safest to riskiest.

Example ordering:
- Phase 1: Readability-only cleanup
- Phase 2: Naming consistency and comments
- Phase 3: Dead import / unused code verification
- Phase 4: Duplicate helper consolidation
- Phase 5: Low-risk file/folder organization
- Phase 6: Controlled service extraction or restructuring
- Phase 7: Higher-risk module consolidation, only if justified

Do not make the roadmap reckless.  
Prefer small, reversible, testable steps.

---

# Future Execution Protocol

Even though you must not execute anything now, the plan must define exactly how execution should happen later after my approval.

The future execution protocol must include:

## 1. Backup / Restore Strategy
Before touching any file in a later execution phase:

- create a restore point or checkpoint
- record the original file paths
- preserve original files before modification
- avoid destructive overwrite without backup
- maintain a clear rollback path

## 2. One-Phase-at-a-Time Execution
Only execute one approved phase at a time.
After each phase:

- stop
- summarize what changed
- list files modified
- explain why each change was safe
- run or recommend validation checks
- wait for my approval before continuing to the next phase

## 3. No Scope Creep
During execution, do not:
- redesign features
- change business logic
- alter workflow behavior
- change database schema unless separately approved
- refactor unrelated files just because they are nearby

Stay strictly inside the approved phase.

## 4. Validation Gates
Every future execution phase must include appropriate validation such as:

- syntax checks
- linting if available
- route checks if relevant
- endpoint checks if relevant
- basic regression checks
- UI behavior checks if relevant
- ensuring no existing feature is lost

If tests exist, use them.
If tests do not exist, propose a practical manual verification checklist.

## 5. Rollback Rule
If any change creates uncertainty, breaks a feature, or introduces unclear behavior:

- stop immediately
- report the issue
- recommend rollback or correction
- do not continue to later phases

---

# Required Final Deliverable

Produce the final result as a structured markdown document titled:

# Safe Refactor Master Plan

It must contain:

1. Executive Summary
2. Current Codebase Structure
3. Architecture Understanding
4. Dependency and Risk Map
5. Refactor Opportunity Audit
6. Recommended Refactor Philosophy
7. Detailed Multi-Phase Refactor Roadmap
8. Future Safe Execution Protocol
9. Backup and Rollback Strategy
10. Validation and Regression Testing Checklist
11. High-Risk Areas That Must Be Handled Carefully
12. Items That Require My Approval Before Any Execution
13. Final Recommendation: whether the refactor is safe to proceed with later, and under what conditions

---

# Decision Rule

Your job is not to make the codebase look “different.”
Your job is to make it safer, clearer, and easier to maintain while preserving all current features.

When uncertain:
- prefer documenting the risk instead of making assumptions
- prefer recommending verification instead of recommending deletion
- prefer smaller refactor steps instead of large rewrites
- prefer maintainability over aggressively reducing file count

Again: DO NOT execute anything. Only analyze and produce the complete plan.