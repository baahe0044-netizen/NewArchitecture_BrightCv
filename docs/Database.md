# Database

database/schema.sql is the canonical idempotent schema.

## Tables

- users: accounts, roles, plan, locale, sign-in metadata, and session version
- resume_templates: available visual layouts and presentation metadata
- resumes: owned CV documents, settings, completion, score, and soft deletion
- resume_versions: periodic immutable content snapshots
- resume_ats_scores: historical ATS reports and keyword scores
- resume_generations: print, PDF, and JSON export audit
- resume_ai_history: smart-writing actions and outputs
- user_activity: concise dashboard event history
- password_reset_tokens: hashed, expiring, one-time reset tokens
- rate_limits: cross-session authentication throttling keyed by a private hash

## Ownership

resumes.user_id is the primary ownership relationship. Repository methods never
load a CV by identifier alone for user-facing workflows. Related ATS, assistant,
version, and export rows cascade when a CV is permanently removed.

## Backups

Back up the complete database and private storage directory together. Test
restoration regularly. JSON exports are convenient per-CV portability but are
not a replacement for database backups.

## Legacy databases

The rebuilt schema uses resumes.content_json as the canonical document. Back up
any earlier LunettiStar database before switching. For the safest transition,
import database/schema.sql into a new database, update DB_DATABASE, create a
test account, and verify exports before migrating legacy content.
