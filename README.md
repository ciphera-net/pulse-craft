# Pulse Analytics for Craft CMS

This plugin adds privacy-first analytics to a Craft CMS site. Pulse Analytics
sets no cookies and never stores IP addresses, and the script the plugin adds
to your pages is under 3 KB.

[Pulse Analytics](https://pulse.ciphera.net) is built by
[Ciphera](https://ciphera.net), a company in Belgium, and hosted in
Switzerland.

## Requirements

The plugin requires **Craft CMS 5**, the current major as of October 2026 (the
latest 5.x release is 5.11.x, and Craft 6 is alpha-only and not yet a supported
target). It needs PHP 8.2 or later and the MySQL or PostgreSQL database that
Craft itself already requires. Craft 5 does not support SQLite. The plugin has
no other Composer dependencies or external libraries. It creates no database
tables of its own. You need a Pulse Analytics account with a site registered
for the same domain. The free plan is enough to start.

## Install

```bash
composer require ciphera/craft-pulse-analytics
php craft plugin/install pulse-analytics
```

Then go to **Settings → Plugins → Pulse Analytics** and enter the domain your
site is registered under in Pulse.

You can leave the domain empty. The tracker falls back to the browser's own
hostname, which is correct for a site served on one domain. Set it explicitly
when the site answers on several domains. On a multi-site install where
different sites answer on different domains, leave it empty: an empty domain
auto-detects correctly for each site individually.

## Settings

| Setting | Default | What it does |
|---|---|---|
| Domain | empty | The domain this site is registered under in Pulse. Empty means auto-detect. |
| Record clicks, copies and form submits | off | Loads the companion script as a second, separate request (see below). |
| Custom API origin | empty | Route events through your own proxy origin. Enter the origin only, without a path. |

Settings are stored in Craft's project config, so they travel with
`project.yaml` the way any other plugin's settings do. They can also be set
per environment in `config/pulse-analytics.php`, which wins over whatever is
saved in the control panel. This is standard Craft plugin-settings behaviour.

## Four things worth knowing

**What lands in your pages is the literal tag.** The plugin registers it with
`craft\web\View::registerJsFile()`, the same mechanism Craft uses for every
script it emits itself:

```html
<script src="https://js.ciphera.net/script.js" defer data-domain="example.com"></script>
```

The plugin adds no inline JavaScript, so **a strict Content-Security-Policy
does not need a nonce or `unsafe-inline` for this plugin**. The policy only has
to allow `script-src https://js.ciphera.net` and
`connect-src https://pulse-api.ciphera.net`.

The rendered tag puts `src` first, then `defer`, then `data-domain`. The
install panel shows a different order. The order comes from Craft's own HTML
renderer (`yii\helpers\Html::renderTagAttributes()`), which re-sorts a fixed
set of attributes: `src` is one of them, and `defer` and `data-*` are not. The
behaviour was checked directly against the installed framework code. Attribute
order carries no meaning in HTML; it is the same tag either way.
`scripts/verify.sh` asserts the exact bytes a real install produces.

**It only reaches the page where the layout calls `{{ head() }}`.** Craft only
outputs `POS_HEAD` registrations at that call site. The starter project's
`_layout.twig` has the call, and most real sites inherit from something that
does. A from-scratch layout that never calls `{{ head() }}` registers the tag
and never renders it anywhere. This limitation comes from Craft's own
head-registration mechanism. If you installed the plugin and nothing happened,
check this first.

**It never runs on the control panel or in a console request, and no setting
can change that.** Unlike the Drupal and Joomla integrations, this plugin has
no "also track administration pages" toggle, because Craft's control panel is a
separate application from the front end, and the two share one install.
Treating the control panel as part of the site's audience makes no sense for
Craft, although it arguably does for a themed admin area on another platform.
The guard is unconditional, and `scripts/verify.sh` proves against a real
install that the control panel carries no tag.

**A mistyped domain is rejected at the settings form** instead of being saved
and quietly dropped. An analytics install that reports nothing while looking
fine is the worst outcome this plugin can produce, so the form shows an error
message. The same is true of a custom API origin that doesn't start with
`http://` or `https://`.

## The interaction capture is a second request on purpose

The core script's size is a published claim, and nothing is folded into it to
make a feature look free. Turning on "Record clicks, copies and form submits"
registers a second `<script>` pointed at a different URL
(`script.interactions.js`); it carries no `data-domain` of its own, because the
tracker resolves the domain once, from the core script.

## Why `registerJsFile()` instead of a hand-built `<script>` string

Craft's own renderer escapes and deduplicates whatever it is given, the same
way it does for every other asset on the page. `'defer' => true` is a PHP
boolean, and `yii\helpers\Html::renderTagAttributes()` renders a bare `defer`
for `true` and omits the attribute entirely for `false`. It never writes
`defer="1"`. Other platforms in this family behave differently: TYPO3's
attribute renderer has no bare-attribute path at all and writes
`defer="defer"`. For that reason `scripts/verify.sh` asserts the shipped bytes
on every platform separately.

## Licence

The licence is **Apache-2.0**. Unlike the Drupal, Joomla and TYPO3
integrations, this one is not forced into GPL. The Craft Plugin Store's own
developer documentation lists the open-source licences it accepts for a free
plugin: *"Apache-2.0, GPL-2.0, GPL-3.0, MIT"*
(`craftcms.com/docs/5.x/extend/plugin-store.html`). It describes MIT as
"generally recommended, unless you have a good reason to use something else",
but does not require it. Apache-2.0 is the licence most other public Pulse
integrations use, so this plugin has no divergence to explain, unlike those
three.

The licence file is named **`LICENSE.md`** instead of `LICENSE.txt` or
`LICENSE`. The Plugin Store's own approval checklist is specific about this: it
requires the plugin to designate "a license in `composer.json` expressed by
`LICENSE.md`" verbatim. That is a platform naming requirement. The other public
Pulse repos that use a bare `LICENSE` file would fail this specific check if
submitted as-is.

## Composer package name

This package is `ciphera/craft-pulse-analytics`. `ciphera/pulse-analytics` is
the Packagist name of the TYPO3 extension. Packagist names are global and unique across every ecosystem
that reads from it, including Craft's Plugin Store, so the two could not share
one name. The plugin's **handle**, which is what you see in the control panel
URL and pass to `php craft plugin/install`, stays the short `pulse-analytics`,
matching the other platforms. Only the Composer package name differs.

## Independence

This plugin is an independent integration built by Ciphera and is not
affiliated with, endorsed by, or sponsored by Pixel & Tonic, Inc. "Craft CMS"
is a trademark of Pixel & Tonic, Inc.
