<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\SetList;
use Rector\Set\ValueObject\LevelSetList;
use Rector\CodeQuality\Rector\Class_\InlineConstructorDefaultToPropertyRector;
use Rector\Php80\Rector\Class_\ClassPropertyAssignToConstructorPromotionRector;
use Rector\Php81\Rector\Property\ReadOnlyPropertyRector;
use Rector\DeadCode\Rector\ClassMethod\RemoveUnusedPrivateMethodRector;
use Rector\DeadCode\Rector\Property\RemoveUnusedPrivatePropertyRector;
use Rector\CodingStyle\Rector\ClassMethod\MakeInheritedMethodVisibilitySameAsParentRector;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->paths([
        __DIR__ . '/src',
    ]);

    $rectorConfig->skip([
        __DIR__ . '/vendor',
        __DIR__ . '/node_modules',
        __DIR__ . '/tests',

        // Skip files with WordPress hooks that might break
        __DIR__ . '/includes/apollo-social-functions.php',
    ]);

    // PHP 8.0 level set
    $rectorConfig->sets([
        LevelSetList::UP_TO_PHP_80,
        SetList::CODE_QUALITY,
        SetList::CODING_STYLE,
        SetList::DEAD_CODE,
        SetList::TYPE_DECLARATION,
    ]);

    // Register single rules
    $rectorConfig->rules([
        InlineConstructorDefaultToPropertyRector::class,
        ClassPropertyAssignToConstructorPromotionRector::class,
        RemoveUnusedPrivateMethodRector::class,
        RemoveUnusedPrivatePropertyRector::class,
        MakeInheritedMethodVisibilitySameAsParentRector::class,
    ]);

    // Skip rules that might break WordPress compatibility
    $rectorConfig->skip([
        // Don't add readonly to properties - WordPress meta/options use magic
        ReadOnlyPropertyRector::class,
    ]);

    // Parallel processing
    $rectorConfig->parallel();

    // Cache for faster re-runs
    $rectorConfig->cacheDirectory(__DIR__ . '/.rector-cache');
};
