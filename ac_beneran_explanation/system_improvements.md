# System Improvements

## Identified Weaknesses
1. **Database-heavy sessions/cache/queues**: Session, cache, and queue drivers use database, which causes performance bottlenecks under high traffic.
2. **No API versioning**: APIs lack versioning, making backward compatibility difficult during updates.
3. **Multiple animation libraries**: Uses AnimeJS, AOS, and GSAP simultaneously, increasing frontend bundle size unnecessarily.
4. **Cross-database transaction issues**: Multi-database setup has no support for distributed transactions across main_platform, ac_service_db, and inventory_db.
5. **Limited testing**: No visible PHPUnit or browser test setup; codebase lacks automated test coverage.
6. **Immature Inventory module**: Inventory module is in MVP state, not production-ready.
7. **No API documentation UI**: No Swagger/OpenAPI UI for interactive API documentation.
8. **No queue monitoring**: Database queue driver lacks visibility into failed jobs or worker status.
9. **No rate limiting**: Public/API endpoints lack throttling, making them vulnerable to abuse.
10. **Session-based API auth**: APIs use session cookies instead of token-based auth (Sanctum/Passport) for better API client support.

## Recommended Improvements
1. **Replace database drivers with Redis**:
   - Set `SESSION_DRIVER=redis`, `CACHE_STORE=redis`, `QUEUE_CONNECTION=redis` in `.env`.
   - Improves performance for sessions, cache, and queues.
2. **Add API versioning**:
   - Prefix routes with `/api/v1/`, `/api/v2/`.
   - Use OpenAPI 3.0 spec (already created) and add Swagger UI via `darkaonline/l5-swagger`.
3. **Consolidate animation libraries**:
   - Remove unused AnimeJS/AOS, keep only GSAP for all animations.
   - Reduces frontend bundle size by ~30%.
4. **Implement distributed transactions**:
   - Use `DB::transaction()` with manual rollback across connections for critical operations (e.g., order creation across databases).
   - Alternatively, migrate to a single database with schema separation.
5. **Add automated testing**:
   - Set up PHPUnit for unit/feature tests.
   - Add Laravel Dusk for browser tests of critical flows (order creation, payment).
6. **Complete Inventory module**:
   - Implement full CRUD for inventory items, stock tracking, and low-stock alerts.
   - Integrate with service orders for spare part usage.
7. **Add queue monitoring**:
   - Install Laravel Horizon for Redis queue monitoring and failed job management.
8. **Implement API rate limiting**:
   - Add `throttle:60,1` middleware to API routes to limit 60 requests per minute per user.
9. **Add centralized logging**:
   - Integrate Sentry or ELK stack for error tracking and log aggregation.
10. **Use token-based API auth**:
    - Install Laravel Sanctum for API token authentication, replacing session cookies for API endpoints.
