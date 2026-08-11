<?php
declare(strict_types=1);

use Rector\Config\RectorConfig;
use Ssch\TYPO3Rector\Set\Typo3LevelSetList;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->paths([
        __DIR__ . '/Classes',
        __DIR__ . '/Configuration',
        __DIR__ . '/ext_localconf.php',
        __DIR__ . '/ext_emconf.php',
    ]);

    $rectorConfig->sets([
        Typo3LevelSetList::UP_TO_TYPO3_14,
    ]);
};
