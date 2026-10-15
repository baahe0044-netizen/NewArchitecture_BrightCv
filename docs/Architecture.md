# Architecture

## Request lifecycle

1. Apache routes every non-file request inside public to public/index.php.
2. config/app.php loads environment settings, the fallback autoloader, secure
   session settings, and the global exception boundary.
3. App captures the request and dispatches it through Router.
4. Router matches the HTTP method and parameterized path, then applies the
   declared middleware.
5. A controller delegates business rules to a service.
6. Services use repositories for parameterized database access.
7. The controller returns either an HTML View response or a consistent JSON
   response.

## Layer responsibilities

### Core

Router, Request, Response, View, Auth, and Csrf contain framework-level
behaviour only. They do not contain CV or account business rules.

### Controllers

Controllers translate HTTP input into service calls and select the appropriate
response. They do not issue SQL directly.

### Services

Services validate and normalize business data. ResumeService controls the CV
document shape and completion score. AtsService provides deterministic analysis.
AIService provides privacy-friendly writing assistance. AuthService and
AccountService own credential workflows.

### Repositories

Repositories are the database boundary. Every CV read and write includes both
the CV identifier and authenticated user identifier. This prevents insecure
direct object references.

### Views and assets

PHP views render the initial secure document shell. The builder receives a
JSON-safe initial payload and uses renderer.js as the single rendering engine
for live preview and print output.

## CV document

The canonical content JSON contains:

- personal
- summary
- experience
- education
- skills
- projects
- certifications
- languages
- references
- interests
- settings

The server whitelists keys, strips markup, enforces maximum lengths, limits
dynamic collections, and normalizes identifiers before persistence.

## Autosave and recovery

The browser debounces edits before sending a PUT request. The server saves the
canonical document transactionally and increments its version. A version
snapshot is retained at most once every five minutes to prevent autosave from
creating unbounded history.

Unsaved browser changes are also written to localStorage. A newer local draft
is restored after a crash, lost connection, or accidental refresh, then queued
for server synchronization.

## ATS and smart writing

AtsService is deterministic and testable. It measures contact completeness,
summary quality, work-history impact, skills, education, readability, and
target-job keywords.

AIService currently uses a local, deterministic writing engine. This preserves
privacy and requires no external AI provider. A hosted provider can be added
behind the service without changing controllers or the builder API.
