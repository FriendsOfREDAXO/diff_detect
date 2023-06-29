<?php

$urlId = rex_request('id', 'int');
$idBefore = rex_cookie('diff_detect_before', 'int');
$idAfter = rex_cookie('diff_detect_after', 'int');

switch (rex_get('func')) {
    case 'diff':

        break;
}

$list = rex_list::factory(
    '
SELECT      i.id, i.createdate, i.createuser, LENGTH(i.content) size
FROM        '.rex::getTable('diff_detect_index').' i
WHERE       i.url_id = '.$urlId.'
ORDER BY    i.createdate DESC'
);

$list->addTableAttribute('class', 'table-striped table-hover');

// set column labels
foreach ($list->getColumnNames() as $columnName) {
    $list->setColumnLabel($columnName, $this->i18n($columnName));
}

$list->setColumnLabel('id', $this->i18n('compare'));
$list->setColumnFormat('id', 'custom', function ($params) use ($idBefore, $idAfter) {
    $checkedBefore = '';
    $checkedAfter = '';
    if ($params['list']->getValue('id') === $idBefore) {
        $checkedBefore = ' checked';
    }
    if ($params['list']->getValue('id') === $idAfter) {
        $checkedAfter = ' checked';
    }
    
    return '<div class="diff"><input type="radio" name="before" value="###id###"'.$checkedBefore.'><input type="radio" name="after" value="###id###"'.$checkedAfter.'></div>';
});

$list->setColumnFormat('size', 'bytes');
$list->setColumnFormat('createdate', 'intlDateTime');

$content = $list->get();

// buttons
$formElements = [];
$n = [];
$n['field'] = '<a class="btn btn-primary" href="'.rex_url::currentBackendPage([
        'func' => 'diff',
        'id' => $urlId,
    ]).'">'.$this->i18n('compare').'</a>';
$formElements[] = $n;

$fragment = new rex_fragment();
$fragment->setVar('elements', $formElements, false);
$buttons = $fragment->parse('core/form/submit.php');
//$content = str_replace('</form>', '<footer class="panel-footer">' . $buttons . '</footer></form>', $content);

$fragment = new rex_fragment();
$fragment->setVar('title', $this->i18n('snapshots_title_list'), false);
$fragment->setVar('content', $content, false);
$fragment->setVar('buttons', $buttons, false);
echo $fragment->parse('core/page/section.php');
