# Changelog

The Plugin Store polls this file to show release notes, and its own developer
docs ask for a heading in exactly this form:
`## <version> - <YYYY-MM-DD>`. Keep that shape — it's what the automated
Craft Console listing parses, not a style preference.

## 1.1.0 - 2026-11-14

- The "Record clicks, copies and form submits" setting is gone. The site's settings in Pulse (Settings > General) now decide scroll depth, outbound links, file downloads, and clicks, copies and form submits, so the plugin has nothing to switch. The plugin adds the same single tag as before. A value saved by an earlier version is ignored.
- The listing and settings screen say the script is about 3 KB.

## 1.0.3 - 2026-11-09

- The plugin description in the control panel is written in full sentences, and the changelog link now points at this file. The plugin's code did not change.

## 1.0.2 - 2026-11-09

- The help text on the settings screen is shorter and clearer. Nothing else changed.

## 1.0.1 - 2026-11-02

- The plugin and listing descriptions say what Pulse never stores (IP addresses) instead of "no personal data".

## 1.0.0 - 2026-10-14

- Initial release. Adds the Pulse Analytics tracking script to every front-end page whose layout calls `{{ head() }}`.
- Settings: the site's domain (empty means auto-detect; a mistyped domain is rejected at save), the optional companion script for clicks, copies and form submits, and an optional custom API origin.
- The control panel never carries the script.
