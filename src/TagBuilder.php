<?php

declare(strict_types=1);

namespace ciphera\pulseanalytics;

/**
 * Builds the Pulse Analytics tag.
 *
 * Pure logic: no Craft services, no Yii container, no plugin settings object —
 * everything comes in as arguments and a [$url, $options] pair comes out, ready
 * for craft\web\View::registerJsFile(). That is deliberate, so the part that
 * decides what the tag says is unit-testable without a Craft bootstrap, and so
 * it can be read side by side with the same decisions in the Drupal, Joomla,
 * TYPO3, Astro, Framer and Docusaurus integrations.
 *
 * The URLs, the domain grammar and the rule that the companion script is a
 * SECOND request are copied from those integrations rather than re-derived.
 * Pulse install surfaces disagreeing about what the tag looks like is a drift
 * bug waiting to happen.
 */
final class TagBuilder
{
    /**
     * The Pulse tracker.
     */
    public const SCRIPT_URL = 'https://js.ciphera.net/script.js';

    /**
     * The companion script: clicks, copies and form submits.
     */
    public const COMPANION_URL = 'https://js.ciphera.net/script.interactions.js';

    /**
     * A registrable hostname.
     *
     * Labels of letters, digits and hyphens, at least one dot, a letter-only
     * TLD. Deliberately strict — the value lands inside an HTML attribute that
     * Craft's own Html helper will escape, so this is the second of two guards
     * rather than the only one; it is kept anyway because a domain that cannot
     * be a hostname is a typo, and a typo is an install that reports nothing
     * and looks fine.
     */
    private const DOMAIN_PATTERN = '/^(?=.{1,253}$)(?!-)[a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?(\.[a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?)*\.[a-z]{2,63}$/';

    private function __construct()
    {
        // Static-only. No instance ever carries state.
    }

    /**
     * Lower-cases a domain and strips a scheme, path, port and trailing dot.
     *
     * Keeps "www." — the Pulse site may be registered either way and the site
     * owner can edit it.
     */
    public static function normalizeDomain(string $input): string
    {
        $value = mb_strtolower(trim($input));
        $value = preg_replace('#^[a-z][a-z0-9+.-]*://#', '', $value) ?? $value;
        $value = preg_replace('#[/?\#].*$#', '', $value) ?? $value;
        $value = preg_replace('/:.*$/', '', $value) ?? $value;

        return rtrim($value, '.');
    }

    /**
     * Whether a normalised domain is a registrable hostname.
     */
    public static function isValidDomain(string $domain): bool
    {
        return $domain !== '' && preg_match(self::DOMAIN_PATTERN, $domain) === 1;
    }

    /**
     * Strips trailing slashes from a bare origin.
     */
    public static function normalizeApiOrigin(string $input): string
    {
        return rtrim(trim($input), '/');
    }

    /**
     * Whether a normalised API origin is a bare http(s) origin.
     */
    public static function isValidApiOrigin(string $origin): bool
    {
        return $origin === '' || preg_match('#^https?://#', $origin) === 1;
    }

    /**
     * Builds the [$url, $options] pair for the core tracker tag.
     *
     * @param string $domain The domain this site is registered under in
     *   Pulse, or '' to let the tracker auto-detect the browser's hostname.
     *   An invalid non-empty domain is dropped rather than injected, and the
     *   tag falls back to auto-detection — the same working install, one
     *   attribute short.
     * @param string $api A custom API origin, or '' for the default.
     *
     * @return array{0: string, 1: array<string, bool|string>} The URL and the
     *   HTML attribute options, in the shape craft\web\View::registerJsFile()
     *   and yii\helpers\Html::jsFile() expect. 'defer' is a PHP bool on
     *   purpose: Html::renderTagAttributes() renders a bare attribute name for
     *   a true boolean value and omits the attribute entirely for false — it
     *   never writes `defer="1"`.
     */
    public static function trackerTag(string $domain, string $api = ''): array
    {
        $options = ['defer' => true];

        $domain = self::normalizeDomain($domain);
        if (self::isValidDomain($domain)) {
            $options['data-domain'] = $domain;
        }

        $api = self::normalizeApiOrigin($api);
        if ($api !== '' && self::isValidApiOrigin($api)) {
            $options['data-api'] = $api;
        }

        return [self::SCRIPT_URL, $options];
    }

    /**
     * Builds the [$url, $options] pair for the companion (interactions) tag.
     *
     * No data-domain on the companion: the tracker resolves the domain once,
     * from the core script, and a second copy is a second thing to keep in
     * step.
     *
     * @return array{0: string, 1: array<string, bool>}
     */
    public static function companionTag(): array
    {
        return [self::COMPANION_URL, ['defer' => true]];
    }
}
