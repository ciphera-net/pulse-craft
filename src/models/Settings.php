<?php

declare(strict_types=1);

namespace ciphera\pulseanalytics\models;

use Craft;
use craft\base\Model;
use ciphera\pulseanalytics\TagBuilder;

/**
 * Pulse Analytics settings.
 *
 * Three fields, matching the Drupal, Joomla and TYPO3 integrations: a domain
 * (empty means auto-detect), the companion-script toggle, and a custom API
 * origin. There is no "also track administration pages" toggle here — unlike
 * those platforms, Craft's control panel is a wholly separate application
 * from the front end, and the plugin never attaches the tag there at all
 * (see Plugin::attachTrackerTag()), so there is nothing for a toggle to turn
 * on.
 *
 * Craft merges this model's attributes with `config/pulse-analytics.php` at
 * read time (per-environment overrides), so a value set in version control
 * always wins over whatever is saved in the control panel — standard Craft
 * plugin-settings behaviour, not something this class implements itself.
 */
class Settings extends Model
{
    /**
     * The domain this site is registered under in Pulse, or '' to auto-detect.
     */
    public string $domain = '';

    /**
     * Whether to also load the companion script (clicks, copies, form
     * submits). Off by default: it is a second, separate request, and
     * nothing is folded into the core script to make the feature look free.
     */
    public bool $companion = false;

    /**
     * A custom API origin (a bare origin, not a full URL), or '' for the
     * default.
     */
    public string $apiOrigin = '';

    protected function defineRules(): array
    {
        return [
            [['domain', 'apiOrigin'], 'trim'],
            [['domain'], 'filter', 'filter' => [TagBuilder::class, 'normalizeDomain'], 'skipOnEmpty' => true],
            [['apiOrigin'], 'filter', 'filter' => [TagBuilder::class, 'normalizeApiOrigin'], 'skipOnEmpty' => true],
            [['domain'], 'validateDomainAttribute'],
            [['apiOrigin'], 'validateApiOriginAttribute'],
            [['companion'], 'boolean'],
            [['companion'], 'default', 'value' => false],
        ];
    }

    /**
     * A mistyped domain is the one failure worth being noisy about: the tag
     * still loads, the site still works, and the dashboard stays empty
     * forever with nothing to see. So it is rejected here, at save time,
     * rather than quietly stored and silently dropped when the tag is built.
     */
    public function validateDomainAttribute(string $attribute): void
    {
        $value = (string) $this->$attribute;
        if ($value === '') {
            return;
        }

        if (!TagBuilder::isValidDomain($value)) {
            $this->addError($attribute, Craft::t(
                'pulse-analytics',
                '{value} is not a valid domain. Enter the hostname this site is registered under in Pulse, for example example.com.',
                ['value' => $value]
            ));
        }
    }

    public function validateApiOriginAttribute(string $attribute): void
    {
        $value = (string) $this->$attribute;
        if ($value === '' || TagBuilder::isValidApiOrigin($value)) {
            return;
        }

        $this->addError($attribute, Craft::t(
            'pulse-analytics',
            'The API origin must start with http:// or https://.'
        ));
    }
}
