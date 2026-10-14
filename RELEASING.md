# Releasing Pulse Analytics for Craft CMS

**Status (14-10-2026):** `v1.0.0` is tagged and on Packagist
(`composer require ciphera/craft-pulse-analytics`; account `ciphera`, with a GitHub
push hook so new tags update the listing on their own). The Plugin Store listing
(step 2 below) still needs a Craft Console account. This file is the exact
sequence, the account each step needs, and what the Plugin Store actually reviews — assembled from Craft's
own current docs (`craftcms.com/docs/5.x/extend/plugin-store.html` and
`craftcms.com/knowledge-base/plugin-store-approval-process`) and cross-checked
against three real, free, MIT-licensed plugins already in the store
(`craftcms/redactor`, `craftcms/element-api`,
`putyourlightson/craft-amazon-ses`) — because, as it turns out, the two
official pages disagree with each other on one point (below).

## Three surfaces, three different credentials

1. **GitHub** — the canonical source. A public repo, same org as every other
   public Pulse integration (`ciphera-net/pulse-craft`). Needs the same GitHub
   push access already used for the rest of the estate's public repos — no new
   credential.
2. **Packagist** — makes `composer require ciphera/craft-pulse-analytics`
   resolve. The Plugin Store's own docs say this is *"not a requirement for
   listing in the Plugin Store, but it makes discovery and automation easier
   for your customers"* — do it anyway, the same way every other public Pulse
   package is on its registry. Needs a Packagist account that can submit under
   the `ciphera` vendor namespace (the same namespace the as-yet-unpublished
   TYPO3 extension is reserved under — see the naming note below) and, for
   auto-updates, either the Packagist GitHub App installed on the repo or a
   manually configured GitHub webhook pointed at
   `https://packagist.org/api/github?username=<user>`.
3. **Craft Console** (`console.craftcms.com`) — the Plugin Store listing
   itself. This is a **new account type this estate has not needed for any
   other Pulse integration** (Drupal, Joomla and TYPO3 each publish straight
   to their own platform's directory with no separate developer console).
   Needs:
   - A Craft Console account with GitHub connected.
   - "Plugin developer" features turned on under Account Settings — gated
     behind connecting GitHub, not behind anything payment-related for a free
     plugin.
   - An Organization set up in Craft Console (the docs tie this to payout
     eligibility for commercial plugins; required either way to list under).

None of this has been done yet — opening a Craft Console account is an
account-creation step for whoever holds the estate's developer identity on
that platform, not something to self-serve from here.

## Before submitting: the license file name

**Craft's own two official pages disagree, and the real plugins agree with the
one you'd expect to be the obsolete one.**

- `plugin-store.html` (the step-by-step publishing guide) says: for an open
  source license, *"Copy license text to `LICENSE.txt` with copyright
  notice."*
- `plugin-store-approval-process` (the review checklist) says, verbatim:
  *"Designates a license in `composer.json` expressed by `LICENSE.md`."*

Checked against three real, currently-listed, free MIT plugins —
`craftcms/redactor`, `craftcms/element-api`, and the community
`putyourlightson/craft-amazon-ses` — **all three ship `LICENSE.md` at their
repo root**, none ships `LICENSE.txt`. The approval checklist matches what is
actually enforced; the step-by-step guide is stale. This repo's `LICENSE.md`
(Apache-2.0) follows the artefacts, not the stale guide.

## Before submitting: the package-name / handle consistency check

The approval checklist also requires *"a matching Plugin Name, Package Name,
and Plugin Handle that's consistent in `composer.json`."* In this repo:

| | Value |
|---|---|
| Plugin Name (`extra.name`) | `Pulse Analytics` |
| Plugin Handle (`extra.handle`) | `pulse-analytics` |
| Composer package name | `ciphera/craft-pulse-analytics` |

These are not byte-identical, and are not meant to be — `craftcms/commerce`
(Name "Craft Commerce", handle `commerce`, package `craftcms/commerce`) and
`nystudio107/craft-seomatic` (handle `seomatic`, package
`nystudio107/craft-seomatic`) are both live in the store with exactly this
"handle is a short form of the package name's second segment" shape, which is
the same relationship as here. **Open risk:** this has not been test-submitted
and could still draw the "probably require follow-up" response the checklist
threatens for the MIT case — budget for one review round-trip.

## Why the package name is NOT `ciphera/pulse-analytics`

Packagist names are global and unique across every ecosystem that resolves
packages from it, including the Craft Plugin Store. `ciphera/pulse-analytics`
is the name the TYPO3 extension's `composer.json` already declares (see
`Pulse/pulse-typo3/composer.json` on `origin/main`) — reserved for it even
though that extension has not been tagged or published yet. Reusing the same
name here would collide the moment either one actually publishes. This plugin
is `ciphera/craft-pulse-analytics` instead; its Plugin Store **handle** stays
the short `pulse-analytics`, matching every other platform's public name.

## First publish

1. **Packagist first**, so the Plugin Store submission has something to point
   at for Composer installs:
   - Push this repository to `github.com/ciphera-net/pulse-craft`.
   - On packagist.org, submit the GitHub URL under the `ciphera` vendor.
   - Enable the GitHub webhook (or the Packagist GitHub App) so new tags
     republish automatically — the same pattern as every other Pulse package
     on a registry.
2. **Craft Console**, once the repo is public:
   - Sign in at `console.craftcms.com`, connect GitHub, enable plugin
     developer features, create/select an Organization.
   - Plugin Store → Plugins → **Add a plugin** → search for and select
     `ciphera-net/pulse-craft`.
   - Fill in: Short Description, Long Description (see `LISTING.md`),
     Categories and Keywords, the Documentation URL
     (`https://docs.ciphera.net/pulse`), the Changelog Path (`CHANGELOG.md`),
     and upload `src/icon.svg` as the plugin icon (square SVG, the Pulse mark
     — the same asset the Framer listing uses, not Craft's own logo: nothing
     in Craft's trademark guidance was found granting a co-brand icon, so this
     plugin's icon carries only the Pulse mark).
   - Pricing: **Free**. The docs are explicit that a plugin submitted free
     cannot later become commercial, so this is a one-way door — correct for
     an integration that exists to drive signups to the hosted product, not to
     be sold itself.
   - Click **Submit for approval**. The docs state typical review happens
     against the checklist above; no published SLA was found (unlike, say,
     the JED's "typically review and respond… within one business day" for
     Craft itself, which is a *different* page about Craft core, not plugins).
3. **First version**: tag `v1.0.0` (semver, `v`-prefixed per the docs) and
   push. *"The Plugin Store is automatically notified and changes typically
   show up in the Plugin Store within a minute or two."* No manual Craft
   Console step for the version itself — only the one-time plugin
   registration above.

## Every release after that

1. Add a `## <version> - <YYYY-MM-DD>` entry to `CHANGELOG.md` — the exact
   heading shape the Plugin Store's changelog parser expects.
2. Bump nothing else: this `composer.json` carries no `version` property (the
   docs only say to update one "if present" — Craft's own convention is to
   read the version from the git tag, not from a hard-coded file).
3. Tag (`vX.Y.Z` or `release-X.Y.Z`) and push. Packagist updates from its
   webhook; the Plugin Store picks up the new tag on its own poll, typically
   within a couple of minutes per the docs above — neither was measured
   against a real submission in this session, since nothing has been
   submitted yet.

Craft's docs also describe an optional
`.github/workflows/create-release.yml` that auto-creates a GitHub Release
from a pushed tag, which the Plugin Store can then read release notes from
directly. Not added here, since this estate's CI does not run on GitHub
Actions — an equivalent release step in whatever CI this repo is registered
with is future work, not part of this build.

## What the store reviews (verbatim, from the approval checklist)

- Matching Plugin Name, Package Name and Plugin Handle, consistent in
  `composer.json`.
- Does not have a name beginning with "Craft".
- Has its own unique Icon.
- Has appropriate Plugin Store Categories and Keywords.
- Has a working Documentation URL.
- Has a correct Changelog Path.
- Designates a license in `composer.json` expressed by `LICENSE.md`.
- Uses the Craft license if commercial (MIT accepted, "will probably require
  follow-up" — not applicable here; this plugin is Apache-2.0 and free).
- Has Short Description and Long Description text describing the plugin's
  purpose and value.

## Changing anything later

- **Repository URL change**: email `support@craftcms.com` — *"Until the
  listing has been updated, new release tags won't be picked up
  automatically."*
- **Composer package name change**: tag a release under the new name for
  every supported Craft major, submit the new package to Packagist, mark the
  old one abandoned pointing at the new one, then email
  `support@craftcms.com` for the listing update.
