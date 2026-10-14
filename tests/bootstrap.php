<?php

declare(strict_types=1);

/**
 * Test bootstrap.
 *
 * Deliberately NOT a Craft application bootstrap: no config, no database, no
 * HTTP request, no console entry script. craft\base\Model (which Settings
 * extends) and Craft::t() (which Settings' validators call) both work without
 * one — Craft::t() falls through to yii\BaseYii::t(), which does plain
 * strtr() placeholder substitution when Craft::$app is null, and Yii's
 * validators only need Yii::$container, set up by requiring Yii.php below.
 *
 * What this CANNOT prove: Plugin::attachTrackerTag()'s request-type guard
 * (getIsConsoleRequest() / getIsCpRequest()) and the exact rendered HTML
 * craft\web\View::registerJsFile() produces — both need a real Craft
 * application and a real request, which is scripts/verify.sh's job against
 * a real install, not this harness's.
 */
require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../vendor/yiisoft/yii2/Yii.php';
require __DIR__ . '/../vendor/craftcms/cms/src/Craft.php';
