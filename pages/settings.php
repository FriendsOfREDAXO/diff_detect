<?php

use FriendsOfRedaxo\DiffDetect\Url;

/**
 * @var rex_addon $this
 * @psalm-scope-this rex_addon
 */

if ('update' === rex_request('func', 'string')) {
    $timeOut = rex_request('diff_detect_timeout', 'int', Url::DefaultTimeOut);
    if ($timeOut < 5 || $timeOut > 300) {
        $timeOut = Url::DefaultTimeOut;
    }
    $this->setConfig('timeout', $timeOut);
    $this->setConfig('proxy', trim(rex_request('diff_detect_proxy', 'string', '')));
    $this->setConfig('cleanup_interval', rex_request('diff_detect_cleanup_interval', 'int', null));
    $userAgent = rex_request('diff_detect_user_agent', 'string', Url::DefaultUserAgent);
    if (empty($userAgent)) {
        $userAgent = Url::DefaultUserAgent;
    }
    $this->setConfig('user_agent', $userAgent);
    echo rex_view::success($this->i18n('settings_updated'));
}

$formElements = [];

$selActive = new rex_select();
$selActive->setId('diff_detect_cleanup_interval');
$selActive->setName('diff_detect_cleanup_interval');
$selActive->setSize(1);
$selActive->setAttribute('class', 'form-control selectpicker');
$selActive->setSelected($this->getConfig('cleanup_interval'));
foreach ([
    0 => $this->i18n('no_cleanup'),
    10080 => $this->i18n('interval_in_min_10080'),
    20160 => $this->i18n('interval_in_min_20160'),
    43200 => $this->i18n('interval_in_min_43200'),
    129600 => $this->i18n('interval_in_min_129600'),
    259200 => $this->i18n('interval_in_min_259200'),
    518400 => $this->i18n('interval_in_min_518400'),
] as $i => $type) {
    $selActive->addOption($type, $i);
}

$selTimeout = new rex_select();
$selTimeout->setId('diff_detect_timeout');
$selTimeout->setName('diff_detect_timeout');
$selTimeout->setSize(1);
$selTimeout->setAttribute('class', 'form-control selectpicker');
$selTimeout->setSelected($this->getConfig('timeout'));
foreach ([
    5 => $this->i18n('seconds', 5),
    10 => $this->i18n('seconds', 10),
    60 => $this->i18n('seconds', 60),
    120 => $this->i18n('seconds', 120),
    300 => $this->i18n('seconds', 300),
] as $i => $type) {
    $selTimeout->addOption($type, $i);
}

$n = [];
$n['label'] = '<label for="diff_detect_cleanup_interval">' . rex_escape($this->i18n('cleanup_interval')) . '</label>';
$n['field'] = $selActive->get();
$formElements[] = $n;

$n = [];
$n['label'] = '<label for="diff_detect_timeout">' . rex_escape($this->i18n('timeout')) . '</label>';
$n['field'] = $selTimeout->get();
$formElements[] = $n;

$n = [];
$n['label'] = '<label for="diff_detect_user_agent">' . rex_escape($this->i18n('user_agent')) . '</label>';
$n['field'] = '<input type="text" class="form-control" id="diff_detect_user_agent" name="diff_detect_user_agent" value="' . rex_escape($this->getConfig('user_agent') ?? Url::DefaultUserAgent) . '" />';
$formElements[] = $n;

$n = [];
$n['label'] = '<label for="diff_detect_proxy">' . rex_escape($this->i18n('proxy')) . '</label>';
$n['field'] = '<input type="text" class="form-control" id="diff_detect_proxy" name="diff_detect_proxy" value="' . rex_escape($this->getConfig('proxy') ?? '') . '" />
<small>' . rex_escape($this->i18n('proxy_notice')) . '</small>
';
$formElements[] = $n;

$n = [];
$n['label'] = '<label for="diff_detect_submit"></label>';
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
