<?php
declare(strict_types=1);

include __DIR__ . '/vendor/autoload.php';

return Paysera\PhpCsFixerConfig\Config\PayseraConventionsConfig::create()
    ->setDefaultFinder(['src', 'tests'], [])
    ->setRiskyRules()
;
