<?php

$finder = PhpCsFixer\Finder::create()
    ->exclude('app/var/cache/test/')
    ->in(__DIR__.'/src')
;

return PhpCsFixer\Config::create()
    ->setRules([
        '@PSR2' => true,
        '@Symfony' => true,
    ])
    ->setUsingCache(false)
    ->setFinder($finder)
;
