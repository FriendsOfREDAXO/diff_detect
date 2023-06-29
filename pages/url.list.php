<?php

use DiffDetect\Url;
use DiffDetect\Index;

switch (rex_get('func')) {
    case 'status':
        if ($id = rex_request('id', 'int')) {
            $status = rex_get('status', 'bool');

            $sql = rex_sql::factory();
            $sql->setTable(rex::getTable('diff_detect_url'));
            $sql->setWhere('id = :id', ['id' => $id]);
            $sql->setValue('status', $status ? 1 : 0);
            $sql->addGlobalUpdateFields();
            $sql->update();
        }
        break;

    case 'snapshot':
        if ($id = rex_request('id', 'int')) {
            $url = Url::get($id);
            Index::createSnapshot($url);
        }
        break;
}

$list = rex_list::factory(
    '
SELECT      u.id, u.name, u.url, u.categories, u.status, s.snapshot
FROM        '.rex::getTable('diff_detect_url').' u
LEFT JOIN   (SELECT url_id, MAX(createdate) AS snapshot FROM '.rex::getTable('diff_detect_index').' GROUP BY url_id) s
ON          u.id = s.url_id
GROUP BY    u.id
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

$list->setColumnFormat('categories', 'custom', function ($params) {
    return implode('', array_map(fn($item) => '<span class="label label-default" style="float:left">' . $item . '</span>', explode(',', $params['value'])));
});

$list->setColumnFormat('status', 'custom', function ($params) {
    /** @var \rex_list $list */
    $list = $params['list'];
    $containerId = 'status-'.$list->getName().'-'.$list->getValue('id');
    $urlParams = [
        'func' => 'status',
        'id' => $list->getValue('id'),
        'status' => $list->getValue('status') ? '0' : '1',
    ];

    if ($start = rex_request($startKey = $list->getName().'_start')) {
        $urlParams[$startKey] = $start;
    }

    $addon = rex_addon::get('diff_detect');

    return '<div id="'.$containerId.'" data-pjax-container="#'.$containerId.'"><a href="'.$list->getUrl(
            $urlParams
        ).'" title="'.$addon->i18n(
            $list->getValue('status')
                ? 'active_title'
                : 'inactive_title'
        ).'" data-pjax-no-history="true" style="color:'.($list->getValue(
            'status'
        ) ? '#0a0' : '#a00').'">'.$addon->i18n($list->getValue('status') ? 'active' : 'inactive').'</a></div>';
});

$list->setColumnFormat('snapshot', 'custom', function ($params) {
    /** @var \rex_list $list */
    $list = $params['list'];
    $containerId = 'snapshot-'.$list->getName().'-'.$list->getValue('id');
    $urlParams = [
        'func' => 'snapshot',
        'id' => $list->getValue('id'),
    ];

    if ($start = rex_request($startKey = $list->getName().'_start')) {
        $urlParams[$startKey] = $start;
    }

    if ($list->getValue('snapshot')) {
        $timestamp = rex_formatter::intlDateTime($list->getValue('snapshot'));
    } else {
        $timestamp = '-';
    }

    $addon = rex_addon::get('diff_detect');
    return '<div class="snapshot-action" id="'.$containerId.'" data-pjax-container="#'.$containerId.'">'.$timestamp.'
    <a onclick="this.style.pointerEvents=\'none\';this.querySelectorAll(\'i\')[0].classList.add(\'rex-icon-loading\')"
    href="'.$list->getUrl($urlParams).'"
    title="'.$addon->i18n('get_snapshot').'"
    data-pjax-no-history="true">
        <i class="rex-icon fa-rotate-right"></i>
    </a>
</div>';
});

$list->addColumn($this->i18n('snapshots_show'), $this->i18n('snapshots_show'));
$list->setColumnParams($this->i18n('snapshots_show'), ['func' => 'snapshots', 'id' => '###id###']);
//$list->setColumnFormat($this->i18n('snapshots_show'), 'url');

$content = $list->get();

$fragment = new rex_fragment();
$fragment->setVar('title', $this->i18n('title_list'), false);
$fragment->setVar('content', $content, false);
echo $fragment->parse('core/page/section.php');
