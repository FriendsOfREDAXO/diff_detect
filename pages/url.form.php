<?php

$func = rex_request('func', 'string', '');

$form = rex_form::factory(rex::getTable('diff_detect_url'), '', 'id = ' . rex_request('id', 'int', 0));
$form->setEditMode('edit' === $func);

$field = $form->addTextField('name');
$field->setLabel($this->i18n('name'));
$field->setAttribute('maxlength', 255);

$field = $form->addTextField('url');
$field->setLabel($this->i18n('url'));
$field->getValidator()
    ->add('notEmpty', $this->i18n('empty_url'))
    ->add('url', $this->i18n('invalid_url'));

$field = $form->addTextField('categories');
$field->setLabel($this->i18n('categories'));
$field->setAttribute('type', 'tags');

$field = $form->addSelectField('status');
$field->setLabel($this->i18n('status'));
$select = new rex_select();
$select->addOptions([
    $this->i18n('status_inactive'),
    $this->i18n('status_active'),
]);
$field->setSelect($select);

$field = $form->addFieldset($this->i18n('http_auth_legend'));

$field = $form->addTextField('http_auth_login');
$field->setLabel($this->i18n('http_auth_login'));

$field = $form->addTextField('http_auth_password');
$field->setLabel($this->i18n('http_auth_password'));
$field->setAttribute('type', 'password');

$content = $form->get();


$fragment = new rex_fragment();
$fragment->setVar('title', $this->i18n('title_' . rex_request('func', 'string', 'add')), false);
$fragment->setVar('body', $content, false);
echo $fragment->parse('core/page/section.php');
