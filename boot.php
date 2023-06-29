<?php // Boot code

if (rex::isBackend()) {
    if (rex_be_controller::getCurrentPagePart(1) === 'diff_detect') {
        rex_view::addJsFile($this->getAssetsUrl('tagsinput.js'));
        rex_view::addCssFile($this->getAssetsUrl('tagsinput.css'));
        rex_view::addCssFile($this->getAssetsUrl('diff-table.css'));
        rex_view::addJsFile($this->getAssetsUrl('cookie.js'));
        rex_view::addJsFile($this->getAssetsUrl('backend.js'));
        rex_view::addCssFile($this->getAssetsUrl('backend.css'));
    }

    rex_extension::register('PACKAGES_INCLUDED', static function ($params) {

    });
}
