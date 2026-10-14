<?php

declare(strict_types=1);

namespace ciphera\pulseanalytics;

use Craft;
use craft\base\Model;
use craft\base\Plugin as BasePlugin;
use craft\web\View;
use ciphera\pulseanalytics\models\Settings;

/**
 * Pulse Analytics for Craft CMS.
 *
 * Adds the Pulse tracker tag to every front-end page with
 * craft\web\View::registerJsFile(), the same mechanism Craft itself uses for
 * every other `<script>` it emits — not a hand-built string, so the tag is
 * escaped and deduplicated the way Craft escapes and deduplicates its own
 * assets.
 *
 * Craft's View only ever OUTPUTS POS_HEAD registrations where the layout
 * calls `{{ head() }}` — Craft's starter project layout (`_layout.twig`) does
 * this, but a hand-rolled layout that never calls it will build the tag and
 * never render it. That is a real limitation of this mechanism, not a bug in
 * this plugin, and it is the one thing worth checking first if "I installed
 * it and nothing happened."
 */
class Plugin extends BasePlugin
{
    public bool $hasCpSettings = true;
    public bool $hasCpSection = false;
    public string $schemaVersion = '1.0.0';

    public function init(): void
    {
        parent::init();

        // Deferred to onInit(): inside a plugin's own init(), Craft has not
        // finished booting every component yet (and other plugins have not
        // finished registering theirs). onInit() runs once the application is
        // fully initialised — immediately, if it already is by the time this
        // call happens — which is the documented place to register anything
        // that depends on other services, including the request component
        // this plugin reads below.
        Craft::$app->onInit(function (): void {
            $this->attachTrackerTag();
        });
    }

    /**
     * Registers the Pulse tag for POS_HEAD, on a front-end "site" request
     * only.
     *
     * Never on the control panel or a console request:
     *
     * - A console request (a `php craft ...` command, a queue job) has no
     *   HTTP response to attach a head script to at all.
     * - The control panel is not the site's audience, and counting CP traffic
     *   would inflate every number the site owner looks at on the dashboard.
     *
     * Unlike the Drupal and Joomla integrations, this is not a setting a site
     * owner can turn on. Craft's control panel and front end are two
     * different applications sharing one install and one codebase — there is
     * no reading of "the CP is part of the site's audience" that makes sense
     * here, so there is nothing for a toggle to turn on. The guard is
     * unconditional, and scripts/verify.sh proves the control panel carries
     * no tag against a real install rather than trusting this comment.
     */
    private function attachTrackerTag(): void
    {
        $request = Craft::$app->getRequest();
        if ($request->getIsConsoleRequest() || $request->getIsCpRequest()) {
            return;
        }

        /** @var Settings $settings */
        $settings = $this->getSettings();
        $view = Craft::$app->getView();

        [$url, $options] = TagBuilder::trackerTag($settings->domain, $settings->apiOrigin);
        $options['position'] = View::POS_HEAD;
        $view->registerJsFile($url, $options, 'pulse-analytics-tracker');

        if ($settings->companion) {
            [$companionUrl, $companionOptions] = TagBuilder::companionTag();
            $companionOptions['position'] = View::POS_HEAD;
            $view->registerJsFile($companionUrl, $companionOptions, 'pulse-analytics-companion');
        }
    }

    protected function createSettingsModel(): ?Model
    {
        return Craft::createObject(Settings::class);
    }

    /**
     * Renders src/templates/settings.twig at
     * Settings → Plugins → Pulse Analytics, under the plugin's own handle —
     * Craft resolves `<handle>/settings` against this plugin's own
     * `templates/` directory automatically; no explicit registration needed.
     */
    protected function settingsHtml(): ?string
    {
        /** @var Settings $settings */
        $settings = $this->getSettings();

        return Craft::$app->getView()->renderTemplate('pulse-analytics/settings', [
            'settings' => $settings,
        ]);
    }
}
