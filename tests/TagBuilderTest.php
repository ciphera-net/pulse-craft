<?php

declare(strict_types=1);

namespace ciphera\pulseanalytics\tests;

use ciphera\pulseanalytics\TagBuilder;
use PHPUnit\Framework\TestCase;

/**
 * Tests the tag builder.
 *
 * TagBuilder touches no Craft or Yii service, so these assertions run without
 * a Craft bootstrap — no database, no config, no application. What they
 * cannot prove is what craft\web\View::registerJsFile() and
 * yii\helpers\Html::jsFile() turn the returned [$url, $options] pair into once
 * Craft actually renders a page: whether 'defer' => true comes out as a bare
 * `defer`, and in what attribute ORDER. That is scripts/verify.sh's job,
 * against a real install, and it is the check that actually matters.
 *
 * (Measured directly against the installed yii2 vendor code while writing
 * this plugin, not assumed: yii\helpers\BaseHtml::renderTagAttributes()
 * reorders by a fixed $attributeOrder list that includes 'src' but not
 * 'defer' or any 'data-*' key, and Html::jsFile() sets 'src' on $options
 * AFTER this class returns it — so the shipped tag reads
 * `src="..." defer data-domain="..." data-api="..."`, not
 * `defer data-domain="..." src="..."`. Attribute order carries no meaning in
 * HTML; see README.md.)
 */
final class TagBuilderTest extends TestCase
{
    public function testNormalizeDomainStripsEverythingButTheHost(): void
    {
        self::assertSame('example.com', TagBuilder::normalizeDomain('https://example.com/docs?a=1#b'));
        self::assertSame('example.com', TagBuilder::normalizeDomain('http://example.com:8080'));
        self::assertSame('example.com', TagBuilder::normalizeDomain('  ExAmPle.COM  '));
        self::assertSame('www.example.com', TagBuilder::normalizeDomain('www.example.com.'));
    }

    public function testIsValidDomainAcceptsRegistrableHostnames(): void
    {
        self::assertTrue(TagBuilder::isValidDomain('example.com'));
        self::assertTrue(TagBuilder::isValidDomain('docs.example.co.uk'));
        self::assertTrue(TagBuilder::isValidDomain('my-site.dev'));
    }

    public function testIsValidDomainRejectsEverythingElse(): void
    {
        // A bare label, so "localhost" can never be registered as a site.
        self::assertFalse(TagBuilder::isValidDomain('localhost'));
        self::assertFalse(TagBuilder::isValidDomain('-bad.com'));
        self::assertFalse(TagBuilder::isValidDomain('example.123'));
        self::assertFalse(TagBuilder::isValidDomain(''));
        // Anything that could break out of an HTML attribute.
        self::assertFalse(TagBuilder::isValidDomain('example.com" onload="x'));
        self::assertFalse(TagBuilder::isValidDomain('example.com x'));
        self::assertFalse(TagBuilder::isValidDomain('example.com>'));
    }

    public function testTrackerTagCarriesTheDomain(): void
    {
        [$url, $options] = TagBuilder::trackerTag('example.com');

        self::assertSame(TagBuilder::SCRIPT_URL, $url);
        self::assertSame(['defer' => true, 'data-domain' => 'example.com'], $options);
    }

    public function testDeferIsBooleanTrueNotTheString(): void
    {
        [, $options] = TagBuilder::trackerTag('example.com');

        self::assertTrue($options['defer']);
        self::assertNotSame('defer', $options['defer']);
        self::assertIsBool($options['defer']);
    }

    public function testNormalisesTheDomainOnTheWayIn(): void
    {
        [, $options] = TagBuilder::trackerTag('HTTPS://Example.COM/some/path');

        self::assertSame('example.com', $options['data-domain']);
    }

    public function testOmitsDataDomainWhenThereIsNoneToUse(): void
    {
        foreach (['', '   '] as $empty) {
            [, $options] = TagBuilder::trackerTag($empty);
            self::assertArrayNotHasKey('data-domain', $options);
        }
    }

    /**
     * A domain that survived whatever set it (an imported project config, a
     * hand-edited config/pulse-analytics.php) must never reach the attribute.
     * Dropping it leaves a working install that auto-detects, which is
     * strictly better than injecting rubbish.
     */
    public function testDropsInvalidDomainRatherThanInjectingIt(): void
    {
        [$url, $options] = TagBuilder::trackerTag('bad" onload="alert(1)');

        self::assertArrayNotHasKey('data-domain', $options);
        self::assertSame(TagBuilder::SCRIPT_URL, $url);
    }

    public function testCarriesCustomApiOriginWithTrailingSlashesStripped(): void
    {
        [, $options] = TagBuilder::trackerTag('example.com', 'https://proxy.example.com//');

        self::assertSame('https://proxy.example.com', $options['data-api']);
    }

    public function testOmitsDataApiByDefault(): void
    {
        [, $options] = TagBuilder::trackerTag('example.com');

        self::assertArrayNotHasKey('data-api', $options);
    }

    public function testDropsAnApiOriginWithNoScheme(): void
    {
        [, $options] = TagBuilder::trackerTag('example.com', 'proxy.example.com');

        self::assertArrayNotHasKey('data-api', $options);
    }

    public function testCompanionTagCarriesNoDomain(): void
    {
        [$url, $options] = TagBuilder::companionTag();

        self::assertSame(TagBuilder::COMPANION_URL, $url);
        self::assertSame(['defer' => true], $options);
    }

    public function testPointsAtJsCipheraNetAndNowhereElse(): void
    {
        self::assertSame('https://js.ciphera.net/script.js', TagBuilder::SCRIPT_URL);
        self::assertSame('https://js.ciphera.net/script.interactions.js', TagBuilder::COMPANION_URL);
    }

    public function testNormalizeApiOriginStripsTrailingSlashesOnly(): void
    {
        self::assertSame('https://example.com', TagBuilder::normalizeApiOrigin('  https://example.com///  '));
        self::assertSame('', TagBuilder::normalizeApiOrigin('   '));
    }

    public function testIsValidApiOriginAcceptsEmptyAndHttpSchemes(): void
    {
        self::assertTrue(TagBuilder::isValidApiOrigin(''));
        self::assertTrue(TagBuilder::isValidApiOrigin('https://example.com'));
        self::assertTrue(TagBuilder::isValidApiOrigin('http://example.com'));
        self::assertFalse(TagBuilder::isValidApiOrigin('ftp://example.com'));
        self::assertFalse(TagBuilder::isValidApiOrigin('example.com'));
    }
}
