# The Documentation System Itself

How the `/documentation` portal (this thing) works, so it can be kept accurate and
extended.

## Source

Plain Markdown, one file per section:
- `resources/docs/business/*.md` — Business Documentation.
- `resources/docs/developer/*.md` — Developer Documentation (this file included).

Filenames are numerically prefixed to control reading/navigation order
(`00-welcome.md`, `01-getting-started.md`, ...). The **section manifest** — which
files exist, in what order, under what title/slug — is defined in
`App\Services\Concrete\Admin\DocumentationService::businessSections()` /
`developerSections()`. Adding a new section means adding both the `.md` file and an
entry in the matching manifest method.

## Rendering

`DocumentationService` reads a `.md` file with `File::get(resource_path(...))` and
converts it to HTML with `League\CommonMark\CommonMarkConverter` (already a vendor
dependency via Laravel's Mail Markdown support — no new package was added for
this). `render($audience, $slug)` returns one section (used by the reader view);
`renderAll($audience)` returns every section in order (used for PDF export).

## Routes & Controller

`App\Http\Controllers\Admin\DocumentationController` — `index()` (portal
landing), `business()`/`developer()` (the reader view, optional `{section?}` route
parameter), `businessPdf()`/`developerPdf()` (combined PDF, one per audience).
Each action is gated by its own permission
(`documentation.view`, `documentation.business.view`, `documentation.business.pdf`,
`documentation.developer.view`, `documentation.developer.pdf` — see
[Permissions & Access Control](05-permissions-access-control.md)). Routes live
under `admin/documentation/**`; a top-level `GET /documentation` redirects to the
portal landing as a memorable shortcut.

## Views

`resources/views/admin/documentation/index.blade.php` (landing — hero cards +
section index for both audiences), `reader.blade.php` (shared two-pane reader,
parameterized by `$audience`, used by both `business()` and `developer()`),
`pdf.blade.php` (standalone dompdf template — cover page, table of contents,
one page-break-before section per Markdown file).

## PDF Export

Follows the same `Pdf::loadView(...)->setPaper('a4', 'portrait')->stream(...)`
pattern as every report (see
[Reports Infrastructure](06-reports-infrastructure.md)) — generated **on demand**
from the current Markdown source each time it's requested. There is no separate
"build" or "regenerate" step: editing a `.md` file changes both the in-app reader
and the next PDF download immediately.

## Keeping This In Sync — Mandatory

Per `CLAUDE.md`'s "Documentation" section: any change to a feature, module,
table/column, route, permission, report, setting, or business rule must update the
relevant Business and/or Developer Markdown file(s) in the **same task** that makes
the change. Check existing docs before implementing changes; correct rather than
duplicate; never leave docs describing something that no longer matches the code.
