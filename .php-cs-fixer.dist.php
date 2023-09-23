<?php

declare(strict_types=1);

$finder = (new PhpCsFixer\Finder())
    ->in(__DIR__)
    ->filter(static function (SplFileInfo $file) {
        return !str_contains('vendor', $file->getRealPath());
    })
;

return (new Redaxo\PhpCsFixerConfig\Config())
    ->setFinder($finder)
    ;
