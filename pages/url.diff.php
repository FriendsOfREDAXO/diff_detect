<?php

/** @var rex_addon $this */

$urlId = rex_request('id', 'int', null);
$idBefore = rex_request('before', 'int', null);
$idAfter = rex_request('after', 'int', null);

if (null === $urlId || null === $idBefore || null === $idAfter) {
    echo rex_view::error($this->i18n('diff_error'));
    $title = '';
    $content = '';
} else {
    $url = \FriendsOfRedaxo\DiffDetect\Url::get($urlId);
    $indexBefore = \FriendsOfRedaxo\DiffDetect\Index::get($idBefore);
    $indexAfter = \FriendsOfRedaxo\DiffDetect\Index::get($idAfter);

    $title = \rex_i18n::rawMsg(
        'diff_title',
        $url->getValue('url'),
        rex_formatter::intlDateTime($indexBefore->getValue('createdate')),
        rex_formatter::intlDateTime($indexAfter->getValue('createdate')),
    );

    $first_detect = '';
    if ('RSS' === $url->getType()) {
        $content = (new \FriendsOfRedaxo\DiffDetect\RssDiff($indexBefore->getContent(), $indexAfter->getContent()))->calculate();
    } else {
        $content = \Jfcherng\Diff\DiffHelper::calculate(
            $indexAfter->getContent(),
            $indexBefore->getContent(),
            'Combined',
            [
                'context' => \Jfcherng\Diff\Differ::CONTEXT_ALL,
                'ignoreLineEnding' => true,
                'ignoreWhitespace' => true,
            ],
            [
                'detailLevel' => 'line',
                'language' => 'deu',
            ],
        );

        if ('' === $content) {
            $content = '<table class="diff-wrapper diff diff-html diff-combined">
                            <thead><tr><th>Keine Unterschiede</th></tr></thead>
                            <tbody class="change change-eq"><tr data-type=" "><td class="new">' . $indexAfter->getContent() . '</td></tr></tbody>
                        </table>';
        } else {
            // HTML Div gefunden
            $content = preg_replace('/data-type="([\+\-])"/is', 'data-type="$1" id="diff_detect_first_hit"', $content, 1);
            $first_detect = ' <a href="#diff_detect_first_hit" class="">'.$this->i18n('hitme').'</a>';
        }
    }

    $fragment = new rex_fragment();
    $fragment->setVar('title', $title.$first_detect, false);
    $fragment->setVar('content', $content, false);
    echo $fragment->parse('core/page/section.php');

    echo rex_view::info('<a href="' . rex_url::currentBackendPage([
        'func' => 'snapshots',
        'before' => $idBefore,
        'after' => $idAfter,
        'id' => $urlId, ]) . '">' . $this->i18n('back_to_snapshots') . '</a>');
}
