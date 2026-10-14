# Pulse Analytics for Craft CMS

Privacy-first analytics for a Craft CMS site, in one plugin. No cookies, no
personal data, and the script it adds to your pages is under 3 KB.

[Pulse Analytics](https://pulse.ciphera.net) is built by
[Ciphera](https://ciphera.net), a company in Belgium, and hosted in
Switzerland.

## Requirements

**Craft CMS 5** — the current major as of October 2026 (the latest 5.x release
is 5.11.x; Craft 6 is alpha-only and not yet a supported target). PHP 8.2 or
later, and the MySQL or PostgreSQL database Craft itself already requires —
Craft 5 does not support SQLite. No other Composer dependencies, no external
libraries, no database tables of its own. You need a Pulse Analytics account
with a site registered for the same domain; the free plan is enough to start.

## Install

```bash
composer require ciphera/craft-pulse-analytics
php craft plugin/install pulse-analytics
```

Then go to **Settings → Plugins → Pulse Analytics** and enter the domain your
site is registered under in Pulse.

You can leave the domain empty. The tracker falls back to the browser's own
hostname, which is correct for a site served on one domain — set it explicitly
when the site answers on several, and leave it empty on a multi-site install
where different sites answer on different domains, since an empty domain
auto-detects correctly for each one individually.

## Settings

| Setting | Default | What it does |
|---|---|---|
| Domain | empty | The domain this site is registered under in Pulse. Empty means auto-detect. |
| Record clicks, copies and form submits | off | Loads the companion script. A second, separate request — see below. |
| Custom API origin | empty | Route events through your own proxy origin. A bare origin, not a full URL. |

Settings are stored in Craft's project config, so they travel with
`project.yaml` the way any other plugin's settings do. They can also be set
per environment in `config/pulse-analytics.php`, which wins over whatever is
saved in the control panel — standard Craft plugin-settings behaviour.

## Four things worth knowing

**What lands in your pages is the literal tag**, registered with
`craft\web\View::registerJsFile()` — the same mechanism Craft uses for every
script it emits itself, not a hand-built string:

```html
<script src="https://js.ciphera.net/script.js" defer data-domain="example.com"></script>
```

No inline JavaScript at all, which means **a strict Content-Security-Policy
needs no nonce and no `unsafe-inline` for this plugin** — only
`script-src https://js.ciphera.net` and `connect-src https://pulse-api.ciphera.net`.

Note the attribute order: `src` before `defer` before `data-domain`, not the
order the install panel shows it in. That is Craft's own HTML renderer
(`yii\helpers\Html::renderTagAttributes()`) re-sorting a fixed set of
attributes — `src` is one of them, `defer` and `data-*` are not — and it is
measured directly against the installed framework code, not assumed.
Attribute order carries no meaning in HTML; it is the same tag either way.
`scripts/verify.sh` asserts the exact bytes a real install produces.

**It only reaches the page where the layout calls `{{ head() }}`.** Craft only
outputs `POS_HEAD` registrations at that call site — the starter project's
`_layout.twig` has it, and most real sites inherit from something that does,
but a from-scratch layout that never calls `{{ head() }}` will register the
tag and never render it anywhere. That is a real limitation of Craft's own
head-registration mechanism, not a bug in this plugin, and it is the first
thing worth checking if "I installed it and nothing happened."

**It never runs on the control panel or in a console request, and that is not
a setting.** Unlike the Drupal and Joomla integrations, there is no "also
track administration pages" toggle here, because Craft's control panel is a
wholly separate application from the front end sharing one install — there is
no reading of "the CP is part of the site's audience" that makes sense for
Craft the way it arguably does for a themed admin area on another platform.
The guard is unconditional, and `scripts/verify.sh` proves the control panel
carries no tag against a real install.

**A mistyped domain is rejected at the settings form**, rather than saved and
quietly dropped. An analytics install that reports nothing while looking fine
is the worst outcome this plugin can produce, so it is worth an error message.
The same is true of a custom API origin that doesn't start with `http://` or
`https://`.

## The interaction capture is a second request on purpose

The core script's size is a published claim, and nothing is folded into it to
make a feature look free. Turning on "Record clicks, copies and form submits"
registers a second `<script>` pointed at a different URL
(`script.interactions.js`); it carries no `data-domain` of its own, because the
tracker resolves the domain once, from the core script.

## Why `registerJsFile()`, not a hand-built `<script>` string

Craft's own renderer escapes and deduplicates whatever it is given, the same
way it does for every other asset on the page — `'defer' => true` is a PHP
boolean, and `yii\helpers\Html::renderTagAttributes()` renders a bare `defer`
for `true` and omits the attribute entirely for `false`. It never writes
`defer="1"`. (Other platforms in this family disagree on this exact point —
TYPO3's attribute renderer has no bare-attribute path at all and writes
`defer="defer"` — which is why nothing here is assumed and `scripts/verify.sh`
asserts the shipped bytes on every platform separately.)

## Licence

**Apache-2.0** — unlike the Drupal, Joomla and TYPO3 integrations, this one is
not forced into GPL. The Craft Plugin Store's own developer documentation
lists the open-source licences it accepts for a free plugin: *"Apache-2.0,
GPL-2.0, GPL-3.0, MIT"* (`craftcms.com/docs/5.x/extend/plugin-store.html`),
with MIT merely "generally recommended, unless you have a good reason to use
something else" — not required. Apache-2.0 is the licence every other public
Pulse integration uses, so there is no divergence to explain here, unlike
those three.

The licence file is named **`LICENSE.md`**, not `LICENSE.txt` or `LICENSE` —
the Plugin Store's own approval checklist is specific about this: it requires
the plugin to designate "a license in `composer.json` expressed by
`LICENSE.md`" verbatim. That is a platform naming requirement, not a style
choice; the other public Pulse repos that use a bare `LICENSE` file would fail
this specific check if submitted as-is.

## Composer package name

This package is `ciphera/craft-pulse-analytics`, not `ciphera/pulse-analytics`
— the latter is the Packagist name reserved for the (separately built, not yet
published) TYPO3 extension. Packagist names are global and unique across every
ecosystem that reads from it, including Craft's Plugin Store, so the two could
not share one name. The plugin's **handle** — what you see in the control
panel URL and pass to `php craft plugin/install` — stays the short
`pulse-analytics`, matching the other platforms; only the Composer package
name differs.

## Independence

This plugin is an independent integration built by Ciphera and is not
affiliated with, endorsed by, or sponsored by Pixel & Tonic, Inc. "Craft CMS"
is a trademark of Pixel & Tonic, Inc.
