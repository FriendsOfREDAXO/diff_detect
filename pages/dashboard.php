<?php

switch ($func = rex_request('func')) {
    case 'add':
    case 'edit':
        include __DIR__.'/url.form.php';
        break;

    case 'snapshots':
    case 'diff':
        include __DIR__.'/url.'.$func.'.php';
        break;

    default:
        include __DIR__.'/url.list.php';
}
