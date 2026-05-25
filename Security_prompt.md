# 🔒 Security Prompt Pack for AI Agents

*Copy-paste prompts to audit and harden AI-generated code.*

---

## **1. Frontend-Only Validation**

1. **Check for backend validation**:
  > Review every form and user input in this project. For each one, tell me: (1) Is there frontend validation? (2) Is there backend validation? (3) What specific checks does each one do? List any inputs where the backend accepts data without validating it first.
2. **Add backend validation**:
  > Add backend validation for every field: check required fields, data types, string lengths, valid emails, and numeric ranges. Reject invalid data with a clear 400 error.
3. **Full input sanitization**:
  > For every user input (forms, URL params, uploads), ensure it’s sanitized before storage/display. Treat all input as plain text. Use parameterized queries for databases and escape HTML before rendering.
4. **Validate data types/ranges**:
  > For every API endpoint, verify backend checks for: (1) data types, (2) string length limits, (3) numeric ranges, (4) valid dates, (5) allowed enum values, (6) array lengths. Reject invalid data with 400 errors.
5. **Rate-aware validation**:
  > Add validation to: (1) block >10 submissions/minute from the same user/IP, (2) log/reject inputs with SQL keywords/script tags, (3) add a honeypot field to catch bots.

---

## **2. Hardcoded Secrets & API Keys**

1. **Scan for hardcoded secrets**:
  > Scan the entire project for hardcoded API keys, passwords, tokens, or database URLs. For each, report: (1) file location, (2) frontend/backend, (3) how to move to environment variables.
2. **Set up environment variables**:
  > Move all secrets to `.env` with descriptive names (e.g., `OPENAI_API_KEY`). Update code to use `process.env.VAR_NAME`. Add `.env` to `.gitignore` and create a `.env.example` with placeholders.
3. **Check frontend for secrets**:
  > Search all frontend files for API keys/tokens. Move them to backend API routes immediately.
4. **Verify `.gitignore` and Git history**:
  > Check if `.env` is in `.gitignore`. Audit Git history for committed secrets using:  
  >  List any exposed secrets for rotation.
5. **Separate public vs. secret keys**:
  > For each API key: (1) identify the service, (2) classify as public/secret, (3) ensure secret keys are **never** in frontend code.
6. **Safe AI tool usage**:
  > When adding [service name], use environment variables for API keys. Never paste actual keys into the chat. Show where to add the real value in hosting settings.

---

## **3. Authentication & Session Security**

1. **Full authentication audit**:
  > Review: (1) password storage (bcrypt/argon2?), (2) token expiration, (3) secure storage (httpOnly cookies?), (4) server-side logout invalidation, (5) brute-force protection, (6) single-use/time-limited password reset tokens.
2. **Fix token/session management**:
  > Ensure: (1) access tokens expire in 15–30 mins, (2) refresh tokens expire in 7–30 days, (3) tokens use `httpOnly`, `secure`, `sameSite` cookies, (4) logout invalidates server-side tokens, (5) password changes invalidate all sessions.
3. **Add password security**:
  > Enforce: (1) minimum 8 characters, (2) check against breached passwords (e.g., HaveIBeenPwned), (3) no arbitrary complexity rules, (4) rate-limit to 5 attempts/minute, (5) never log/display plaintext passwords.
4. **Secure password reset flow**:
  > Verify: (1) random, long reset tokens, (2) 15–30 min expiration, (3) single-use tokens, (4) invalidate other sessions post-reset, (5) generic "if account exists" message, (6) token validation before showing reset form.
5. **Protect against session fixation/hijacking**:
  > Add: (1) new session ID after login, (2) bind sessions to IP/user agent, (3) "log out everywhere" feature, (4) `secure` flag on cookies, (5) `sameSite=strict/lax` on auth cookies.

---

## **4. Missing Permission Checks**

1. **Full permission audit**:
  > For every API endpoint: (1) Is authentication checked? (2) Is authorization (resource ownership) checked? (3) What happens if unauthenticated/unauthorized users access it? Flag missing checks.
2. **Add ownership checks**:
  > For [orders/recipes/documents], verify the user’s ID matches the resource owner’s ID before returning/updating/deleting. Return `403 Forbidden` if not.
3. **Protect admin-only features**:
  > Add role checks on backend for admin actions. Return `403` if the user lacks admin role.
4. **Test for URL tampering**:
  > For routes with ID params (e.g., `/orders/123`), verify the backend checks if the logged-in user can access that specific ID.
5. **Universal permission middleware**:
  > Create reusable middleware to: (1) verify authentication, (2) accept an authorization function, (3) return `401`/`403` as needed, (4) log unauthorized access attempts.
6. **Use non-guessable IDs**:
  > Replace sequential IDs (1, 2, 3) with UUIDs for any user-accessible resource to prevent enumeration.

---

## **5. Sensitive Error Messages & Data Leaks**

1. **Fix error messages for production**:
  > Replace technical error details (stack traces, file paths, DB info) with generic user-friendly messages. Log full errors server-side.
2. **Clean up `console.log` statements**:
  > Remove or replace all `console.log/error/warn` statements that output sensitive data (tokens, passwords, PII, DB queries).
3. **Add global error handling**:
  > Frontend: Add an error boundary for unhandled errors. Backend: Add a catch-all handler returning generic errors while logging details server-side.
4. **Check failed request responses**:
  > Test each API endpoint with bad data (invalid IDs, missing fields, expired tokens). Flag responses exposing internal details.
5. **Prevent data leaks in API responses**:
  > For every endpoint: (1) send only necessary fields, (2) exclude password hashes/internal IDs, (3) strip other users’ PII from list responses. Use explicit allowlists.

---

## **6. Injection Attacks (SQL, XSS, CSRF)**

1. **Check for SQL injection**:
  > Review all DB queries. Rewrite any using string concatenation with user input to use **parameterized queries**.
2. **Check for XSS vulnerabilities**:
  > For all user-generated content displayed in the UI (comments, usernames, etc.), ensure it’s escaped/sanitized. Flag any use of `innerHTML`/`dangerouslySetInnerHTML`.
3. **Add CSRF protection**:
  > Verify: (1) CSRF tokens protect state-changing requests (POST/PUT/DELETE), (2) auth cookies use `sameSite=strict/lax`, (3) server checks `Origin`/`Referer` headers.
4. **Full injection audit**:
  > Check: (1) all DB queries for SQL injection, (2) all rendered content for XSS, (3) URL params for injection, (4) file uploads for path traversal, (5) API endpoints for command injection.
5. **Protect against DOM-based XSS**:
  > Review client-side JS for: (1) reading from `window.location`/`URLSearchParams` and writing to DOM with `innerHTML`, (2) `eval()` with user input.
6. **Protect against open redirects**:
  > For all redirects, whitelist allowed destinations. Reject URLs not matching the list (e.g., `?redirect=https://evil-site.com`).

---

## **7. File Upload Security**

1. **Audit file upload endpoints**:
  > For each upload feature: (1) allowed file types, (2) validation method (extension vs. MIME type), (3) size limits, (4) storage location, (5) filename sanitization.
2. **Add proper file validation**:
  > For all uploads: (1) validate actual content type (magic bytes), (2) set size limits (e.g., 5MB images), (3) allow only specific types (jpg, png, pdf), (4) reject double extensions (e.g., `file.php.jpg`), (5) strip metadata, (6) generate random filenames.
3. **Secure file storage**:
  > Ensure: (1) files stored outside web root, (2) use cloud storage (S3/Supabase) with access controls, (3) serve files via proxy endpoint with permission checks, (4) set `Content-Disposition: attachment` for downloads.
4. **Scan uploads for malicious content**:
  > For images: re-encode with libraries like `sharp` (Node.js) or `Pillow` (Python). For documents: check for macros/scripts. Log suspicious uploads with user ID/IP.
5. **Prevent DoS via uploads**:
  > Add: (1) server-side size limits, (2) rate limits (e.g., 5 uploads/minute/user), (3) total storage limits per user, (4) async processing, (5) request timeouts.

---

## **8. Rate Limiting & Brute Force**

1. **Add rate limiting to login**:
  > After 5 failed attempts from the same IP, block for 15 mins. After 10 failed attempts for an account, lock it and require email verification. Always return generic "Invalid email or password" errors.
2. **Add rate limiting to API endpoints**:
  > Set limits: (1) auth endpoints: 5–10/min/IP, (2) read endpoints: 60–100/min/user, (3) write endpoints: 20–30/min/user, (4) file uploads: 5–10/min/user. Return `429 Too Many Requests` with `Retry-After` header.
3. **Protect signup/password reset**:
  > Signup: (1) 3 accounts/hour/IP, (2) email verification, (3) honeypot field. Password reset: (1) 3 requests/hour/email, (2) generic "if account exists" message, (3) rate-limit reset endpoint.
4. **Add account lockout with notification**:
  > After too many failed attempts, lock the account and email the owner with: (1) attempt time, (2) approximate location (IP-based), (3) password change link. Auto-unlock after 30 mins or via email link.
5. **Protect expensive operations**:
  > Identify endpoints triggering costly actions (emails, external APIs, AI calls). Add specific rate limits (e.g., 10 emails/hour/user).

---

## **9. HTTPS & Transport Security**

1. **Check for HTTPS enforcement**:
  > Verify: (1) HTTP → HTTPS redirects, (2) all API calls use `https://`, (3) external resources (images/scripts) loaded over HTTPS, (4) HSTS header set, (5) WebSockets use `wss://`.
2. **Fix mixed content issues**:
  > Scan all frontend code for `http://` resources. Replace with `https://` or protocol-relative URLs (`//`).
3. **Add security headers**:
  > Add to all responses:
4. **Secure cookie transport**:
  > Ensure all cookies have: (1) `secure` flag, (2) `httpOnly` for session cookies, (3) `sameSite=strict/lax`, (4) reasonable expiration. Avoid storing sensitive data in cookies.

---

## **10. Data Privacy & PII Handling**

1. **Map all personal data**:
  > Inventory all PII: (1) data type (name, email, etc.), (2) collection point, (3) storage location, (4) access controls, (5) encryption at rest, (6) secure transmission, (7) deletion mechanism.
2. **Minimize data collection**:
  > Remove unnecessary fields. For required fields, store less sensitive versions (e.g., last 4 digits of phone number).
3. **Encrypt sensitive data at rest**:
  > Verify: (1) passwords hashed with bcrypt/argon2, (2) other sensitive fields encrypted (AES-256), (3) encryption keys stored separately from DB, (4) DB encrypted at storage level.
4. **Add user data deletion endpoint**:
  > Build a feature to: (1) delete all user records (cascade through relationships), (2) remove files from storage, (3) purge logs/analytics, (4) send confirmation email, (5) return deletion confirmation.
5. **Add privacy-aware data export**:
  > Create an endpoint to export all user data (JSON/CSV): profile info, content, activity logs. Exclude other users’ data, internal fields, or shared context data.

---

## **11. Insecure Configuration & Defaults**

1. **Production readiness check**:
  > Verify: (1) debug mode off, (2) CORS restricted to app domain, (3) DB not publicly accessible, (4) no default credentials, (5) verbose errors disabled.
2. **Lock down CORS**:
  > Restrict API to app domain only (not `*`). Allow only necessary HTTP methods. Disable credentials with wildcard origins.
3. **Database security review**:
  > Check: (1) DB accessibility (app server only), (2) no default/weak credentials, (3) SSL/TLS for connections, (4) row-level security, (5) encrypted backups.
4. **Remove development artifacts**:
  > Scan for: (1) test accounts/dummy data, (2) debug flags/verbose logging, (3) unprotected API docs (e.g., `/swagger`), (4) TODO comments about security, (5) test API keys, (6) dev-only middleware/routes.
5. **Review third-party services**:
  > For each service (DB, auth, email, etc.): (1) production config (not sandbox), (2) least-privilege API keys, (3) verified webhook signatures, (4) no default/overly permissive settings.

---

## **12. Outdated & Vulnerable Dependencies**

1. **Run a security audit**:
  > Use `npm audit` (JS) or `pip audit` (Python). List all vulnerabilities: severity, package, fixed version. Apply safe fixes.
2. **Check for abandoned packages**:
  > Flag packages: (1) no updates in 12+ months, (2) archived GitHub repos, (3) unpatched vulnerabilities. Suggest alternatives.
3. **Safe update strategy**:
  > Update dependencies one at a time, starting with critical vulnerabilities. After each update: (1) specify what to test, (2) provide rollback instructions.
4. **Lock down dependency versions**:
  > Pin all versions (e.g., `2.1.3`, not `^2.1.3`). Commit lock files (`package-lock.json`, `yarn.lock`). Avoid Git/tarball dependencies.
5. **Audit transitive dependencies**:
  > List all indirect dependencies. Flag any with vulnerabilities or unmaintained status.

---

## **13. Logging, Monitoring & Audit Trails**

1. **Add security event logging**:
  > Log: (1) all login attempts (success/failure) with timestamp/IP/user agent, (2) failed authorization attempts, (3) account changes, (4) admin actions, (5) data exports, (6) high-rate API errors. Store logs securely.
2. **Set up alerts for suspicious activity**:
  > Alert on: (1) >10 failed logins/5 mins for an account, (2) single IP accessing >50 accounts, (3) bulk data access, (4) login from new country/IP, (5) multiple password resets, (6) spikes in 403/401 errors.
3. **Create an audit trail for sensitive data**:
  > For all sensitive data, log: (1) who accessed/modified it, (2) when, (3) previous value (for modifications), (4) IP/session ID, (5) access method (UI/API). Store in append-only table.
4. **Add health monitoring and error tracking**:
  > Set up: (1) error rate alerts, (2) response time monitoring, (3) uptime tracking, (4) disk space/DB connection monitoring, (5) structured logging (info/warn/error/critical).
5. **Ensure logs don’t leak sensitive data**:
  > Verify logs exclude: (1) passwords/hashes, (2) full API keys/tokens, (3) credit card numbers/SSNs, (4) session tokens, (5) full request bodies with PII. Redact sensitive values (e.g., `sk_live_***1234`).

---

## **14. Master Security Review**

### **After Every Feature**

> I just finished building [feature]. Review **only the new code** for:
>
> 1. Permission checks on every endpoint (auth + authorization).
> 2. Hardcoded secrets/API keys/tokens.
> 3. Backend validation for all user input.
> 4. Safe error messages (no stack traces/file paths/DB details).
> 5. Sanitized user-generated content before storage/display.
> 6. Parameterized DB queries (no string concatenation).
> 7. Secure file uploads (validation + storage).
> 8. Rate limiting on abusable endpoints.
> 9. CSRF protection for state-changing operations.
> 10. Encrypted sensitive data + minimal exposure in API responses.
>
> **Fix all issues, then run this prompt again.**

### **Pre-Launch Checklist**

> Before deploying, verify:
>
> 1. All secrets in environment variables.
> 2. Backend validation for all user input.
> 3. Parameterized DB queries.
> 4. Auth + authorization checks on all endpoints.
> 5. Generic error messages (no internal details).
> 6. CORS locked to app domain.
> 7. Debug mode off.
> 8. All cookies have `secure`, `httpOnly`, `sameSite` flags.
> 9. HTTPS enforced everywhere.
> 10. Rate limiting on login/sensitive endpoints.
> 11. File uploads validated + stored securely.
> 12. No critical dependency vulnerabilities.
> 13. No test credentials/dummy data/dev artifacts.
>
> **Report pass/fail for each item and fix failures.**

---

## **6 Questions to Ask for Every Feature**

1. Who is allowed to use this — and does my code **actually check**?
2. What happens if someone types **something weird** into this field?
3. What sensitive data am I touching — and is it **stored, sent, and shown safely**?
4. Am I using **secure defaults**, or did I just keep the AI’s defaults?
5. If someone tried to **abuse this feature**, what would they do first?
6. Would I **know if something went wrong** — is there logging/monitoring?
