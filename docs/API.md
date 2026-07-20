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
