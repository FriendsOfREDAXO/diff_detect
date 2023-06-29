<?php

/**
 * MetaForm Addon.
 *
 * @author markus[dot]staab[at]redaxo[dot]de Markus Staab
 *
 * @package redaxo5
 */

$Basedir = __DIR__;
$subpage = rex_be_controller::getCurrentPagePart(2);

echo rex_view::title('DiffDetect');

require_once $subpage . '.php';
