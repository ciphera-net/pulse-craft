# Craft Plugin Store listing — Pulse Analytics

The copy for the Plugin Store's "Add a plugin" form (Short Description, Long
Description, Categories, Keywords) and the screenshots to prepare before
submitting. Not yet submitted — see RELEASING.md for the submission sequence
and the accounts it needs.

**Status (09-11-2026):** not submitted. The plugin is on Packagist
(`ciphera/craft-pulse-analytics`) and the GitHub repository is public, and the
screenshots are ready (below). The Plugin Store listing still needs a Craft
Console account, which does not exist yet.

## Name

**Pulse Analytics**

## Short description

Keep this to a single line; the Plugin Store truncates long ones on card
views.

> Pulse Analytics counts visitors without cookies or stored IP addresses. The script this plugin adds to your pages is under 3 KB.

## Long description

```markdown
Pulse Analytics adds privacy-first web analytics to a Craft CMS site with one plugin. It sets no cookies and never stores IP addresses. The script it adds to your pages is under 3 KB.

## How it works

1. Run `composer require ciphera/craft-pulse-analytics` and `php craft plugin/install pulse-analytics`.
2. Go to Settings → Plugins → Pulse Analytics and enter the domain your site is registered under in Pulse. If you leave it empty, the tracker uses the browser's own hostname, which is right for a site served on one domain.
3. Make sure your layout calls `{{ head() }}`. Craft outputs head-registered scripts only at that call, and this plugin registers its script that way.

Your dashboard at pulse.ciphera.net shows visitors as they arrive.

## What it measures

Pulse counts pageviews, referrers, countries, devices, time on page and scroll depth, plus the goals, funnels and campaigns you set up, in one dashboard hosted in Switzerland. It does not count visitors whose browser sends Do Not Track or Global Privacy Control. The plugin never tags the control panel, and no setting changes that.

## Options

You can add a companion script for clicks, copies and form submits. It loads as a second request, so you only pay for it if you want it. You can also set a custom API origin if you route events through your own proxy.

## You need

Craft CMS 5, PHP 8.2+, and a Pulse Analytics account with a site registered for the same domain. The free plan covers one site and 10,000 events a month, and you do not need a card. Business starts at €9 a month with VAT. Every plan has every feature.
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

## Screenshots

Two screenshots were captured on 09-11-2026 from a real Craft 5.12 install running plugin 1.0.2. They are kept
outside this repository.

1. **Settings → Plugins → Pulse Analytics** (2560 x 1600), saved with the domain `example.com`.
2. **The page source** of a front-end page (2700 x 1686), with the tag the plugin adds on line 7, inside `<head>`.

The Plugin Store scales screenshots to 2200 px wide, and the listed plugins we measured use ratios between 4:3 and
about 16:9. A third screenshot, of the Pulse dashboard, is optional.

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

`src/icon.svg` is the Pulse mark in white layers on the brand orange `#FD5E0F`,
the same mark as Ciphera's profile pictures: a 512 × 512 vector made of one
square and two paths, with no embedded raster. It is copied byte for byte from
the brand library (`https://cdn.ciphera.net/brand/v12/kit/craft/pulse/icon.svg`,
SHA-256 `2255d21f32933e8f33a919943cc8423dda260343a12088347e5ab183930e0abe`).
Craft's plugin guide asks for a square `icon.svg` in the root of the source
directory, and Craft shows it on Settings → Plugins. An optional
`icon-mask.svg` styles the nav item of a plugin that has a control panel
section; this plugin has none (`hasCpSection` is `false`), so there is no mask
file.

On Settings → Plugins, Craft passes the icon through its SVG sanitizer.
Measured against Craft 5.11.5.1 on 19-10-2026 with the earlier dark icon, the
sanitizer removed the `<title>` element and nothing else. The orange icon of
10-11-2026 uses only `svg`, `rect`, `path` and `title` elements and the `fill`,
`fill-opacity`, `rx`, `viewBox`, `x`, `y`, `width`, `height` and `d`
attributes, all on the allow lists of the sanitizer library Craft uses
(`enshrined/svg-sanitize`). It has not been rendered in Craft yet.

No Craft/Pixel & Tonic mark appears anywhere on the listing: nothing in
Craft's plugin-store documentation was found granting a co-brand icon or logo
pairing, so none is used, following the same "logo only where a policy permits
it in words" rule applied to every other platform in this family.

## Independence

The listing copy should carry no claim of Pixel & Tonic affiliation,
sponsorship, or endorsement. "Craft CMS" is Pixel & Tonic, Inc.'s trademark;
this is an independent, unaffiliated integration.
