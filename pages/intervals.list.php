<?php

/** @var rex_addon $this */

$list = rex_list::factory(
    '
SELECT      id, name
FROM        '.rex::getTable('diff_detect_interval').'
ORDER BY    name ASC'
);

$list->addTableAttribute('class', 'table-striped table-hover');

$list->removeColumn('id');

// set column labels
foreach ($list->getColumnNames() as $columnName) {
    $list->setColumnLabel($columnName, $this->i18n($columnName));
}

$thIcon = '<a class="rex-link-expanded" href="'.$list->getUrl(['func' => 'add']).'" title="'.$this->i18n(
        'add'
    ).'"><i class="rex-icon rex-icon-add"></i></a>';
$list->addColumn(
    $thIcon,
    '',
    0,
    ['<th class="rex-table-icon">###VALUE###</th>', '<td class="rex-table-icon">###VALUE###</td>']
);
$list->setColumnParams($thIcon, ['func' => 'edit', 'id' => '###id###']);
$list->setColumnFormat($thIcon, 'custom', static function () use ($list, $thIcon) {
    $tdIcon = '<i class="rex-icon rex-icon-edit"></i>';
    return $list->getColumnLink($thIcon, $tdIcon);
});

$list->setColumnFormat('id', 'url');

$content = $list->get();

$fragment = new rex_fragment();
$fragment->setVar('title', $this->i18n('title_intervals_list'), false);
$fragment->setVar('content', $content, false);
echo $fragment->parse('core/page/section.php');
