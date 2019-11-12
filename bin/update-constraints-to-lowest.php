#!/usr/bin/env php
<?php
declare(strict_types=1);

if (!file_exists('composer.json')) {
    throw new Exception('No composer.json found');
}
$contents = file_get_contents('composer.json');

$result = preg_replace('@("symfony/[^"]+":\s*"[^|]+)\|[^"]+"@m', '$1"', $contents);

file_put_contents('composer.json', $result);
