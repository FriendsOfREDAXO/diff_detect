<?php

use FriendsOfRedaxo\DiffDetect\Index;
use FriendsOfRedaxo\DiffDetect\Url;

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
            $Url = Url::get($id);
            try {
                if (Index::createSnapshot($Url)) {
                    echo rex_view::success(rex_i18n::msg('diff_detect_snapshot_created', $Url->getName()));
                } else {
                    echo rex_view::error(rex_i18n::msg('diff_detect_snapshot_not_created', $Url->getName()));
                }
            } catch (rex_exception $e) {
                echo rex_view::error(rex_i18n::msg('diff_detect_snapshot_error', $Url->getName(), $e->getMessage()));
                break;
            }
        }
        break;
}

$list = rex_list::factory(
    '
SELECT      u.id, u.name, u.url, u.`type`, u.categories, u.status, s.snapshot
FROM        '.rex::getTable('diff_detect_url').' u
LEFT JOIN   (SELECT url_id, MAX(updatedate) AS snapshot FROM '.rex::getTable('diff_detect_index').' GROUP BY url_id) s
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

$list->setColumnFormat('type', 'custom', static function ($params) {
    switch ($params['value']) {
        case 'HTML':
            return '<span title="HTML" class="label label-primary">&lt;HTML&gt;</span>';
        case 'RSS':
            return '<span title="RSS"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 256 256"><defs><linearGradient x1=".085" y1=".085" x2=".915" y2=".915" id="a"><stop offset="0" stop-color="#E3702D"/><stop offset=".107" stop-color="#EA7D31"/><stop offset=".35" stop-color="#F69537"/><stop offset=".5" stop-color="#FB9E3A"/><stop offset=".702" stop-color="#EA7C31"/><stop offset=".887" stop-color="#DE642B"/><stop offset="1" stop-color="#D95B29"/></linearGradient></defs><rect width="256" height="256" rx="55" ry="55" fill="#CC5D15"/><rect width="246" height="246" rx="50" ry="50" x="5" y="5" fill="#F49C52"/><rect width="236" height="236" rx="47" ry="47" x="10" y="10" fill="url(#a)"/><circle cx="68" cy="189" r="24" fill="#FFF"/><path d="M160 213h-34a82 82 0 0 0-82-82V97a116 116 0 0 1 116 116z" fill="#FFF"/><path d="M184 213A140 140 0 0 0 44 73V38a175 175 0 0 1 175 175z" fill="#FFF"/></svg> RSS</span>';
    }

    return $params['value'];
});

$list->setColumnFormat('categories', 'custom', static function ($params) {
    return implode(' ', array_map(static fn ($item) => '<span class="label label-default">' . $item . '</span>', explode(',', $params['value'])));
});

$list->setColumnFormat('status', 'custom', static function ($params) {
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

    return '<div><a href="'.$list->getUrl(
        $urlParams
    ).'" title="'.$addon->i18n(
        $list->getValue('status')
                ? 'active_title'
                : 'inactive_title'
    ).'" class="diff-status-'.($list->getValue(
        'status'
    ) ? 'green' : 'red').'">'.$addon->i18n($list->getValue('status') ? 'active' : 'inactive').'</a></div>';
});

$list->setColumnFormat('snapshot', 'custom', static function ($params) {
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
    return '<div class="snapshot-action">'.$timestamp.'
    <a
    href="'.$list->getUrl($urlParams).'"
    title="'.$addon->i18n('get_snapshot').'"
    >
        <i class="rex-icon fa-rotate-right"></i>
    </a>
</div>';
});

$list->addColumn($this->i18n('snapshots_show'), $this->i18n('snapshots_show'));
$list->setColumnParams($this->i18n('snapshots_show'), ['func' => 'snapshots', 'id' => '###id###']);

$fragment = new rex_fragment();
$fragment->setVar('title', $this->i18n('title_list'), false);
$fragment->setVar('content', $list->get(), false);
echo $fragment->parse('core/page/section.php');
