<?php

/** @var rex_addon $this */

$func = rex_request('func', 'string', '');
$fieldset = 'edit' === $func ? $this->i18n('title_filter_edit') : $this->i18n('title_filter_add');

$form = rex_diff_detect_form::factory(rex::getTable('diff_detect_filter'), $fieldset, 'id = ' . rex_request('id', 'int', 0));
$form->setEditMode('edit' === $func);

$field = $form->addTextField('name');
$field->setLabel($this->i18n('name'));
$field->setAttribute('maxlength', 255);
$field->setAttribute('placeholder', $this->i18n('filter_name_placeholder'));

$field = $form->addSelectField('type');
$field->setLabel($this->i18n('type'));
$select = new rex_select();
$select->addOptions([
    'strip_tags' => $this->i18n('filter_type_strip_tags'),
    'CSS' => $this->i18n('filter_type_css'),
    'RegEx' => $this->i18n('filter_type_regex'),
]);
$field->setSelect($select);

$field = $form->addSelectField('mode');
$field->setLabel($this->i18n('mode'));
$select = new rex_select();
$select->addOptions([
    'remain' => $this->i18n('filter_mode_remain'),
    'remove' => $this->i18n('filter_mode_remove'),
]);
$field->setSelect($select);

$field = $form->addTextAreaField('params');
$field->setLabel($this->i18n('filter_params'));
$field->setNotice($this->i18n('filter_params_notice'));

$content = $form->get();

$fragment = new rex_fragment();
$fragment->setVar('title', $this->i18n('title_filter_' . rex_request('func', 'string', 'add')), false);
$fragment->setVar('body', $content, false);
echo $fragment->parse('core/page/section.php');
