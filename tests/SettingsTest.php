<?php

declare(strict_types=1);

namespace ciphera\pulseanalytics\tests;

use ciphera\pulseanalytics\models\Settings;
use PHPUnit\Framework\TestCase;

/**
 * Tests the Settings model's validation and normalisation.
 *
 * Runs against the real craft\base\Model and real Yii validators (via
 * tests/bootstrap.php), with no Craft application, database or HTTP request —
 * craft\base\Model doesn't need one, and Craft::t() degrades to plain
 * placeholder substitution when Craft::$app is unset. What this cannot prove
 * is that the control panel settings screen actually surfaces these errors,
 * or that a value saved here is the value Plugin::attachTrackerTag() reads
 * back — that is scripts/verify.sh's job, against a real install.
 */
final class SettingsTest extends TestCase
{
    public function testDefaultsAreEmptyAndAutoDetecting(): void
    {
        $settings = new Settings();

        self::assertTrue($settings->validate());
        self::assertSame('', $settings->domain);
        self::assertSame('', $settings->apiOrigin);
    }

    public function testAValidDomainIsNormalisedOnConstruction(): void
    {
        $settings = new Settings(['domain' => 'HTTPS://Example.COM/some/path']);

        self::assertTrue($settings->validate());
        self::assertSame('example.com', $settings->domain);
    }

    /**
     * The one failure worth being noisy about: the tag still loads, the site
     * still works, and the dashboard stays empty forever with nothing to see.
     * So it is rejected at validation time rather than quietly stored.
     */
    public function testAnInvalidDomainFailsValidationWithTheRawValueInTheMessage(): void
    {
        $settings = new Settings(['domain' => 'bad" onload="alert(1)']);

        self::assertFalse($settings->validate());
        $errors = $settings->getErrors('domain');
        self::assertCount(1, $errors);
        self::assertStringContainsString('bad" onload="alert(1)', $errors[0]);
        self::assertStringContainsString('is not a valid domain', $errors[0]);
    }

    public function testAnEmptyOrWhitespaceDomainIsValid(): void
    {
        foreach (['', '   '] as $value) {
            $settings = new Settings(['domain' => $value]);
            self::assertTrue($settings->validate(), "domain '$value' should validate");
            self::assertSame('', $settings->domain);
        }
    }

    /**
     * Craft loads saved settings with setAttributes($values, false), which
     * ignores keys the model does not define. A project that still carries a
     * `companion` value from 1.0.x must keep working.
     */
    public function testAStoredCompanionValueFromAnOlderVersionIsIgnored(): void
    {
        $settings = new Settings();
        $settings->setAttributes(['domain' => 'example.com', 'companion' => true], false);

        self::assertTrue($settings->validate());
        self::assertSame('example.com', $settings->domain);
        self::assertFalse(property_exists($settings, 'companion'));
    }

    public function testApiOriginIsNormalisedAndTrailingSlashesStripped(): void
    {
        $settings = new Settings(['apiOrigin' => '  https://proxy.example.com//  ']);

        self::assertTrue($settings->validate());
        self::assertSame('https://proxy.example.com', $settings->apiOrigin);
    }

    public function testApiOriginWithoutASchemeFailsValidation(): void
    {
        $settings = new Settings(['apiOrigin' => 'proxy.example.com']);

        self::assertFalse($settings->validate());
        self::assertNotEmpty($settings->getErrors('apiOrigin'));
    }

    public function testAnEmptyApiOriginIsValid(): void
    {
        $settings = new Settings(['apiOrigin' => '']);

        self::assertTrue($settings->validate());
    }

    public function testAValidFullConfigurationHasNoErrors(): void
    {
        $settings = new Settings([
            'domain' => 'chosen.dev',
            'apiOrigin' => 'https://proxy.example.com',
        ]);

        self::assertTrue($settings->validate());
        self::assertSame([], $settings->getErrors());
    }
}
