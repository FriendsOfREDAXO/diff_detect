<?php

use \Jfcherng\Diff\DiffHelper;

/** @var rex_addon $this */

$urlId = rex_request('id', 'int');
$idBefore = rex_request('before', 'int', rex_cookie('diff_detect_before', 'int'));
$idAfter = rex_request('after', 'int', rex_cookie('diff_detect_after', 'int'));

if (!$urlId or !$idBefore or !$idAfter or $idBefore === $idAfter) {
    $content = rex_view::error($this->i18n('diff_error'));
    $title = '';
    $content = '';
}
else {
    $url = \DiffDetect\Url::get($urlId);
    $indexBefore = \DiffDetect\Index::get($idBefore);
    $indexAfter = \DiffDetect\Index::get($idAfter);

    $title = \rex_i18n::rawMsg('diff_title',
        $url->getValue('url'),
        rex_formatter::intlDateTime($indexBefore->getValue('createdate')),
        rex_formatter::intlDateTime($indexAfter->getValue('createdate'))
    );

    if ($url->getType() === 'RSS') {
        $content = (new \DiffDetect\RssDiff($indexBefore->getContent(), $indexAfter->getContent()))->calculate();
    }
    else {
        $content = \Jfcherng\Diff\DiffHelper::calculate(
            $indexAfter->getContent(),
            $indexBefore->getContent(),
            'Inline',
            [
                //'context' => \Jfcherng\Diff\Differ::CONTEXT_ALL,
                'ignoreLineEnding' => true,
                'ignoreWhitespace' => true,
            ],
            [
                'detailLevel' => 'line',
                'language' => 'deu',
            ]
        );
    }
}

$fragment = new rex_fragment();
$fragment->setVar('title', $title, false);
$fragment->setVar('content', $content, false);
echo $fragment->parse('core/page/section.php');
