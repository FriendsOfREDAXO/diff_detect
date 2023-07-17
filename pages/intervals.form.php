<?php

/** @var rex_addon $this */

$func = rex_request('func', 'string', '');
$fieldset = 'edit' === $func ? $this->i18n('title_interval_edit') : $this->i18n('title_interval_add');

$form = rex_cronjob_form::factory(rex::getTable('diff_detect_interval'), $fieldset, 'id = ' . rex_request('id', 'int', 0));
$form->setEditMode('edit' === $func);

$field = $form->addTextField('name');
$field->setLabel($this->i18n('name'));
$field->setAttribute('maxlength', 255);

$form->addHiddenField('nexttime');

$form->addFieldset($this->i18n('interval'));
$field = $form->addIntervalField('interval');
$field->getValidator()->add('custom', $this->i18n('error_interval_incomplete'), static function (string $interval) {
    /** @psalm-suppress MixedAssignment */
    foreach (json_decode($interval) as $value) {
        if ([] === $value) {
            return false;
        }
    }

    return true;
});

$content = $form->get();

$fragment = new rex_fragment();
$fragment->setVar('title', $this->i18n('title_interval_' . rex_request('func', 'string', 'add')), false);
$fragment->setVar('body', $content, false);
echo $fragment->parse('core/page/section.php');
