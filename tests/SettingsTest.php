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
        self::assertFalse($settings->companion);
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

    public function testCompanionIsTypecastFromAStringLikeAFormPosts(): void
    {
        self::assertTrue((new Settings(['companion' => '1']))->companion);
        self::assertFalse((new Settings(['companion' => '0']))->companion);
        self::assertTrue((new Settings(['companion' => true]))->companion);
        self::assertFalse((new Settings())->companion);
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
            'companion' => true,
            'apiOrigin' => 'https://proxy.example.com',
        ]);

        self::assertTrue($settings->validate());
        self::assertSame([], $settings->getErrors());
    }
}
