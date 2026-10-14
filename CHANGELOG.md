# Changelog

The Plugin Store polls this file to show release notes, and its own developer
docs ask for a heading in exactly this form:
`## <version> - <YYYY-MM-DD>`. Keep that shape — it's what the automated
Craft Console listing parses, not a style preference.

## 1.0.0 - 2026-10-14

- Initial release. Adds the Pulse Analytics tracking script to every front-end page whose layout calls `{{ head() }}`.
- Settings: the site's domain (empty means auto-detect; a mistyped domain is rejected at save), the optional companion script for clicks, copies and form submits, and an optional custom API origin.
- The control panel never carries the script.
