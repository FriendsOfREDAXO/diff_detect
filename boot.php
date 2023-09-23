<?php

// Boot code

/** @var rex_addon $this */

if (rex::isBackend()) {
    if (!defined('') && file_exists(__DIR__ . '/vendor/ezyang/htmlpurifier/library/HTMLPurifier.composer.php')) {
        include_once __DIR__ . '/vendor/ezyang/htmlpurifier/library/HTMLPurifier.composer.php';
    }

    if (rex_be_controller::getCurrentPagePart(1) === 'diff_detect') {
        rex_view::addJsFile($this->getAssetsUrl('tagsinput.js'));
        rex_view::addCssFile($this->getAssetsUrl('tagsinput.css'));
        rex_view::addCssFile($this->getAssetsUrl('diff-table.css'));
        rex_view::addJsFile($this->getAssetsUrl('backend.js'));
        rex_view::addCssFile($this->getAssetsUrl('backend.css'));
        rex_view::addCssFile($this->getAssetsUrl('table.css'));
    }
}

if (rex_addon::get('cronjob')->isAvailable() && !rex::isSafeMode()) {
    rex_cronjob_manager::registerType(rex_cronjob_diff_detect::class);
}
