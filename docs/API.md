# Application API

All API routes require an authenticated session. State-changing requests also
require the X-CSRF-Token header. JSON responses use this shape:

    {
      "success": true,
      "message": "Changes saved.",
      "data": {}
    }

Validation and other expected failures return success false, a human-readable
message, and an errors object where relevant.

## Application shell

GET /manifest.webmanifest

The web app manifest. Generated per request so `start_url` and `scope` match
the directory the app is installed under. No authentication required.

The service worker is served as a static file at `/sw.js`, which scopes it to
the application directory. It caches static assets and an offline page only:
HTML pages and every `/api/` response stay uncached because they carry the
signed-in person's CV content. Signing out posts `clear-caches` to the worker.

## Dashboard

GET /api/dashboard

Returns account statistics, recent CVs, ATS trend data, and recent activity.

## CVs

GET /api/resumes

Lists the authenticated user’s non-deleted CVs.

POST /api/resumes

Accepted fields:

- name
- template_key

GET /api/resumes/{id}

Returns one owned CV and its normalized content document.

PUT /api/resumes/{id}

Accepted top-level fields:

- name
- template_key
- language
- accent_color
- font_family
- job_description
- content
- version

version is required for optimistic locking. A stale version returns HTTP 409
without overwriting the newer CV; the browser keeps the local draft for
reconciliation.

DELETE /api/resumes/{id}

Soft-deletes an owned CV.

POST /api/resumes/{id}/duplicate

Creates an owned copy and returns its builder URL.

POST /api/resumes/{id}/import

Reads an existing CV and returns editable content. Send multipart/form-data with
either field:

- cv_file: a PDF, Word (.docx), plain text, or BrightCV JSON backup, up to 5 MB
- cv_text: the CV text pasted directly

The response carries the parsed content document, the source that was read, and
a detected summary (names, contact details, and per-section counts). Nothing is
written to the CV: the builder shows the summary and the writer confirms before
the content is applied and saved through PUT /api/resumes/{id}.

## Guidance

POST /api/resumes/{id}/ats

Accepts job_description and returns score, grade, category scores, strengths,
recommendations, word count, and matched or missing keywords.

POST /api/resumes/{id}/assistant

Accepted actions:

- summary
- bullet
- keywords
- tips

The input object varies by action. Suggestions are recorded for audit purposes
but are never applied without a separate user action in the builder.

## Export

POST /api/resumes/{id}/export

Records a successful pdf, print, or json export. The visual PDF workflow uses
the browser print engine so users can select Save as PDF without a server-side
binary dependency.
