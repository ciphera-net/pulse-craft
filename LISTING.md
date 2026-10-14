# Craft Plugin Store listing — Pulse Analytics

The copy for the Plugin Store's "Add a plugin" form (Short Description, Long
Description, Categories, Keywords) and the screenshots to prepare before
submitting. Not yet submitted — see RELEASING.md for the submission sequence
and the accounts it needs.

## Name

**Pulse Analytics**

## Short description

Keep this to a single line; the Plugin Store truncates long ones on card
views.

> Privacy-first web analytics. No cookies, no personal data, and the script it adds to your pages is under 3 KB.

## Long description

```markdown
Pulse Analytics adds privacy-first web analytics to a Craft CMS site with one
plugin. It sets no cookies, stores no personal data, and the script it adds to
your pages is under 3 KB.

## How it works

1. `composer require ciphera/craft-pulse-analytics` and
   `php craft plugin/install pulse-analytics`.
2. Go to Settings → Plugins → Pulse Analytics and enter the domain your site
   is registered under in Pulse — or leave it empty and the tracker uses the
   browser's own hostname, correct for a site served on one domain.
3. Make sure your layout calls `{{ head() }}` — Craft only outputs
   head-registered scripts there, same as any other asset this plugin's
   mechanism registers.

Your dashboard at pulse.ciphera.net shows visitors as they arrive.

## What it measures

Pageviews, referrers, countries, devices, time on page and scroll depth, plus
the goals, funnels and campaigns you set up, in one dashboard hosted in
Switzerland. Visitors whose browser sends Do Not Track or Global Privacy
Control are not counted at all. The control panel is never tagged — that is
not a setting, it is how the plugin works.

## Options

A companion script for clicks, copies and form submits, loaded as a second
request so you only pay for it if you want it, and a custom API origin if you
route events through your own proxy.

## You need

Craft CMS 5, PHP 8.2+, and a Pulse Analytics account with a site registered
for the same domain. The free plan is enough to start: one site, 5,000
pageviews a month, no card. Business plans start from €11/month, with every
feature on every plan.
```

## Categories and keywords

🔴 **The Plugin Store has no dedicated "Analytics" category** — checked
against the live category navigation at `plugins.craftcms.com` on
14-10-2026: *SEO & Accessibility, AI, Localization, Ecommerce, Forms, Fields,
Development, Security, Templating, Performance, Control Panel, Widgets,
Assets, Utilities, Mailer Adapters, Social Networking, Integrations.* An
existing Google Analytics widget plugin and a multi-source analytics plugin
(Metrix) are both live in the store, so analytics plugins clearly aren't
excluded — they just sit under a broader category.

**Recommendation:** primary category **Integrations** (this plugin's whole job
is wiring a third-party service into Craft); if the submission form allows a
second category, add **Utilities**. Flagged as an open question for whoever
submits — this was not test-submitted, so the form's actual available choices
were not observed first-hand.

**Keywords:** `analytics`, `privacy`, `cookieless`, `tracking`, `statistics`,
`pulse`, `ciphera`

## Screenshots to prepare before submitting

None exist yet. What to capture, at a size consistent with other current
Plugin Store listings (checked a handful of live entries; card images read
roughly 16:9, no exact pixel spec found in the docs — measure one of the three
reference plugins' live images before finalising):

1. **Settings → Plugins → Pulse Analytics**, the settings screen this plugin
   ships (`src/templates/settings.twig`) — domain field, the companion
   lightswitch, custom API origin, all visible with the domain field showing a
   real-looking example value.
2. **A view-source or dev-tools network panel** on a front-end page, showing
   the literal `<script src="https://js.ciphera.net/script.js" …>` tag in the
   rendered `<head>` — the strongest single piece of evidence that this does
   exactly what it says, and the shape every one of this family's other
   platform listings leads with.
3. *(Optional, if a Pulse account is available for the capture)* the Pulse
   dashboard itself, showing live data from a Craft site — not required, and
   not fabricated if unavailable.

## Links

- Website: `https://pulse.ciphera.net`
- Documentation: `https://docs.ciphera.net/pulse`
- Changelog path: `CHANGELOG.md`
- Repository: `https://github.com/ciphera-net/pulse-craft`
- Support: `https://github.com/ciphera-net/pulse-craft/issues`

## Licence

Apache-2.0, free (not commercial) — see README.md and RELEASING.md for why
this platform, unlike Drupal/Joomla/TYPO3, is not forced into GPL, and for the
`LICENSE.md` naming finding.

## Icon

`src/icon.svg` — the Pulse mark alone, the same asset already used for the
Framer Marketplace listing. No Craft/Pixel & Tonic mark appears anywhere on
the listing: nothing in Craft's plugin-store documentation was found granting
a co-brand icon or logo pairing, so none is used, following the same
"logo only where a policy permits it in words" rule applied to every other
platform in this family.

## Independence

The listing copy should carry no claim of Pixel & Tonic affiliation,
sponsorship, or endorsement. "Craft CMS" is Pixel & Tonic, Inc.'s trademark;
this is an independent, unaffiliated integration.
