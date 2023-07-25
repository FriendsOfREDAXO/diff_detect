<?php

/** @var rex_addon $this */

use FriendsOfRedaxo\DiffDetect\Index;
use FriendsOfRedaxo\DiffDetect\Url;

$func = rex_request('func', 'string', '');
$id = rex_get('id', 'int');

$form = rex_diff_detect_form::factory(rex::getTable('diff_detect_url'), '', 'id = ' . $id);
$form->setFormAttribute('autocomplete', 'off');

if ('edit' === $func && $id) {
    $form->setEditMode(true);
    $form->addParam('id', $id);
}

$field = $form->addTextField('name');
$field->setLabel($this->i18n('name'));
$field->setAttribute('maxlength', 255);

$field = $form->addTextField('url');
$field->setLabel($this->i18n('url'));
$field->getValidator()
    ->add('notEmpty', $this->i18n('empty_url'))
    ->add('url', $this->i18n('invalid_url'));

$field = $form->addSelectField('type');
$field->setLabel($this->i18n('type'));
$select = new rex_select();
$select->addOptions([
    'HTML' => $this->i18n('type_html'),
    'RSS' => $this->i18n('type_rss'),
]);
$field->setSelect($select);

$field = $form->addTextField('categories');
$field->setLabel($this->i18n('categories'));
$field->setAttribute('type', 'tags');

$field = $form->addSelectField('status');
$field->setLabel($this->i18n('status'));
$select = new rex_select();
$select->addOptions([
    0 => $this->i18n('status_inactive'),
    1 => $this->i18n('status_active'),
]);
$field->setSelect($select);

$field = $form->addSelectField('interval');
$field->setLabel($this->i18n('interval'));

$intervall_options = new rex_select();

foreach ([5, 15, 30, 60, 180, 360, 1440, 4320, 10080, 20160, 43200] as $interval) {
    $intervall_options->addOption($this->i18n('interval_in_min_' . $interval), $interval);
}
$field->setSelect($intervall_options);

/*$field = $form->addSelectField('filter_ids');
$field->setLabel($this->i18n('filter'));
$field->setAttribute('class', 'form-control selectpicker');
$field->setAttribute('data-live-search', 'true');
$select = new rex_select();
$select->setMultiple();
$sql = rex_sql::factory();
$select->addSqlOptions(
    '
    SELECT
        IF(`name`, `name`, CONCAT(
            CASE WHEN `type` = "strip_tags" THEN "'.$this->i18n('filter_type_strip_tags_select').'"
                 WHEN `type` = "CSS" THEN "'.$this->i18n('filter_type_css').'"
                 ELSE "'.$this->i18n('filter_type_regex').'" END,
            CASE WHEN (`type` = "CSS" OR `type` = "RegEx") AND `mode` = "remain" THEN CONCAT(", ", "'.$this->i18n('filter_mode_remain').'")
                 WHEN (`type` = "CSS" OR `type` = "RegEx") AND `mode` = "remove" THEN CONCAT(", ", "'.$this->i18n('filter_mode_remove').'")
                 WHEN `type` = "strip_tags" AND LENGTH(`params`) THEN CONCAT(", ", "'.$this->i18n('filter_mode_remain').'")
                 ELSE "" END,
            IF(LENGTH(`params`) > 0, CONCAT(": ", `params`), "")
        )) `name`,
        `id`
    FROM        `'.rex::getTable('diff_detect_filter').'`
    ORDER BY    `name` ASC
'
);
$field->setSelect($select);*/

$form->addFieldset($this->i18n('http_auth_legend'));

$field = $form->addTextField('http_auth_login');
$field->setLabel($this->i18n('http_auth_login'));
$field->setAttribute('autocomplete', 'off');

$field = $form->addTextField('http_auth_password');
$field->setLabel($this->i18n('http_auth_password'));
$field->setAttribute('autocomplete', 'off');

rex_extension::register('REX_FORM_SAVED', static function ($ep) {
    $params = $ep->getParams();
    $id = $params['sql']->getLastId();
    if ($id && $id > 0) {
        $Url = Url::get($id);
        Index::createSnapshot($Url);
    }
});

rex_extension::register('REX_FORM_DELETED', static function ($ep) {

    /** @var rex_extension_point $ep */
    $params = $ep->getParams();

    if ('rex_diff_detect_form' == get_class($params['form'])) {
        /** @var rex_diff_detect_form $form */
        $form = $params['form'];
        $form_params = $form->getParams();

        rex_sql::factory()->setQuery('delete from `'.rex::getTable('diff_detect_index').'` where url_id=:url_id', [
            'url_id' => $form_params['id'],
        ]);

    }
});

$content = $form->get();

$fragment = new rex_fragment();
$fragment->setVar('title', $this->i18n('title_' . rex_request('func', 'string', 'add')), false);
$fragment->setVar('body', $content, false);
echo $fragment->parse('core/page/section.php');
