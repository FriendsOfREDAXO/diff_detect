<?php

/**
 * @var rex_addon $this
 * @psalm-scope-this rex_addon
 */

// urls
rex_sql_table::get(
    rex::getTable('diff_detect_url'))
    ->ensurePrimaryIdColumn()
    ->ensureColumn(new rex_sql_column('name', 'VARCHAR(255)', true))
    ->ensureColumn(new rex_sql_column('url', 'text', false, ''))
    ->ensureColumn(new rex_sql_column('type', 'ENUM("HTML","RSS")', true))
    ->ensureColumn(new rex_sql_column('categories', 'text', false, ''))
    ->ensureColumn(new rex_sql_column('status', 'tinyint', false, '0'))
    ->ensureColumn(new rex_sql_column('interval', 'int(10) unsigned', false, '1440'))
    ->ensureColumn(new rex_sql_column('last_message', 'text', false, ''))
    ->ensureColumn(new rex_sql_column('http_auth_login', 'VARCHAR(100)', true))
    ->ensureColumn(new rex_sql_column('http_auth_password', 'VARCHAR(100)', true))
    ->ensureColumn(new rex_sql_column('last_scan', 'datetime', true))
    ->ensureGlobalColumns()
    ->ensure();

rex_sql_table::get(
    rex::getTable('diff_detect_index'))
    ->ensurePrimaryIdColumn()
    ->ensureColumn(new rex_sql_column('url_id', 'int', false))
    ->ensureColumn(new rex_sql_column('content', 'longtext', false, ''))
    ->ensureColumn(new rex_sql_column('hash', 'VARCHAR(32)', false, ''))
    ->ensureColumn(new rex_sql_column('header', 'longtext', false, ''))
    ->ensureColumn(new rex_sql_column('statusCode', 'int', true))
    ->ensureColumn(new rex_sql_column('statusMessage', 'text', true))
    ->ensureColumn(new rex_sql_column('checked', 'tinyint', false, '0'))
    ->ensureGlobalColumns()
    // ->ensureForeignKey(new rex_sql_foreign_key('fk_url', \rex::getTable('diff_detect_url'), ['url_id' => 'id'],
    //     rex_sql_foreign_key::CASCADE, rex_sql_foreign_key::CASCADE))
    ->ensure();
