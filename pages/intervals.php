<?php

switch ($func = rex_request('func')) {
    case 'add':
    case 'edit':
        include __DIR__ . '/intervals.form.php';
        break;

    default:
        include __DIR__ . '/intervals.list.php';
}
