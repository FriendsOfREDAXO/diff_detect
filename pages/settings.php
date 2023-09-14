<?php

/**
 * @var rex_addon $this
 * @psalm-scope-this rex_addon
 */

$addon = rex_addon::get('diff_detect');

if ('update' === rex_request('func', 'string')) {
    $this->setConfig('cleanup_interval', rex_request('diff_detect_cleanup_interval', 'int', null));
    echo rex_view::success($this->i18n('settings_updated'));
}

$formElements = [];

$selActive = new rex_select();
$selActive->setId('diff_detect_cleanup_interval');
$selActive->setName('diff_detect_cleanup_interval');
$selActive->setSize(1);
$selActive->setAttribute('class', 'form-control selectpicker');
$selActive->setSelected($addon->getConfig('cleanup_interval'));
foreach ([
    0 => $addon->i18n('no_cleanup'),
    10080 => $addon->i18n('interval_in_min_10080'),
    20160 => $addon->i18n('interval_in_min_20160'),
    43200 => $addon->i18n('interval_in_min_43200'),
    129600 => $addon->i18n('interval_in_min_129600'),
    259200 => $addon->i18n('interval_in_min_259200'),
    518400 => $addon->i18n('interval_in_min_518400'),
] as $i => $type) {
    $selActive->addOption($type, $i);
}

$n = [];
$n['label'] = '<label for="diff_detect_cleanup_interval">' . rex_escape($addon->i18n('cleanup_interval')) . '</label>';
$n['field'] = $selActive->get();
$formElements[] = $n;

$n = [];
$n['label'] = '<label for="fe_access_submit"></label>';
$n['field'] = '<button class="btn btn-save right" type="submit" name="config-submit" value="1" title="' . $this->i18n('config_save') . '">' . $this->i18n('config_save') . '</button>';
$formElements[] = $n;

$fragment = new rex_fragment();
$fragment->setVar('elements', $formElements, false);
$formElementsView = $fragment->parse('core/form/form.php');

$content = '
<form action="' . rex_url::currentBackendPage() . '" method="post">
    <input type="hidden" name="func" value="update" />
	<fieldset>
		' . $formElementsView . '
    </fieldset>
	</form>
  ';

$fragment = new rex_fragment();
$fragment->setVar('class', 'edit');
$fragment->setVar('title', $this->i18n('settings'));
$fragment->setVar('body', $content, false);
echo $fragment->parse('core/page/section.php');
