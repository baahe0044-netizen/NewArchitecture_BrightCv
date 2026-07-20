# Database migrations

database/schema.sql is the canonical idempotent schema for fresh installations.
Run php database/migrate.php after copying .env.example to .env, or import the
SQL file through phpMyAdmin when using WAMP.

Existing legacy installations should be backed up before migration. The rebuilt
application stores the complete resume document in resumes.content_json and
keeps immutable snapshots in resume_versions.
