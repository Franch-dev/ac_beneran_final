# AI Debug System

## Purpose
Provide a structured, self-correcting AI system to debug Laravel + JS applications.

---

## System Layers

### 1. Instruction Layer
File: .AI-agents-task-rule-etc/agents-rule.md

Defines strict behavior rules for all agents.

---

### 2. Execution Layer
File: .AI-agents-task-rule-etc/workflow.json

Defines:
- agents
- steps
- validation
- rollback logic

---

### 3. Memory Layer
File: .AI-agents-task-rule-etc/ai-context/debug.log

Stores:
- past bugs
- fix patterns
- lessons learned

---

### 4. Hint Layer
File: .AI-agents-task-rule-etc/system-hint.md

Provides:
- known patterns
- common pitfalls

---

## Agent Types

### Core Agents
- UI_LOGIC_AGENT
- DATA_BINDING_AGENT
- JS_EXECUTION_AGENT

### Validation Agents
- BACKEND_TEST_AGENT
- FRONTEND_VALIDATION_AGENT

---

## Execution Flow

1. Fix UI logic
2. Fix data binding
3. Fix JS execution
4. Run backend tests
5. Validate frontend behavior

---

## Checkpoint System

Before every modification:
- git commit OR backup file

On failure:
- rollback
- retry once
- stop if still failing

---

## Self-Improvement Loop

Agents must:
- analyze failure
- adjust approach
- retry once

---

## Design Philosophy

- Minimal changes
- High reliability
- No unnecessary refactor
- Controlled autonomy

---

## Future Expansion

- Add E2E testing agent (Playwright)
- Replace inline JS with event listeners
- Centralize state management
