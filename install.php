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
    ->ensureColumn(new rex_sql_column('status', 'tinyint', false, 0))
    ->ensureColumn(new rex_sql_column('interval_ids', 'text', true))
    ->ensureColumn(new rex_sql_column('filter_ids', 'text', true))
    ->ensureColumn(new rex_sql_column('http_auth_login', 'VARCHAR(100)', true))
    ->ensureColumn(new rex_sql_column('http_auth_password', 'VARCHAR(100)', true))
    ->ensureGlobalColumns()
    ->ensure();

// intervals
rex_sql_table::get(
    rex::getTable('diff_detect_interval'))
    ->ensurePrimaryIdColumn()
    ->ensureColumn(new rex_sql_column('name', 'VARCHAR(255)', false, ''))
    ->ensureColumn(new rex_sql_column('interval', 'text'))
    ->ensureColumn(new rex_sql_column('nexttime', 'datetime', true))
    ->ensureGlobalColumns()
    ->ensure();

// intervals
rex_sql_table::get(
    rex::getTable('diff_detect_filter'))
    ->ensurePrimaryIdColumn()
    ->ensureColumn(new rex_sql_column('name', 'VARCHAR(255)', false, ''))
    ->ensureColumn(new rex_sql_column('type', 'ENUM("strip_tags","CSS","RegEx")', true))
    ->ensureColumn(new rex_sql_column('mode', 'ENUM("remain","remove")', true))
    ->ensureColumn(new rex_sql_column('params', 'text', false, ''))
    ->ensureGlobalColumns()
    ->ensure();

// diff datasets
rex_sql_table::get(
    rex::getTable('diff_detect_index'))
    ->ensurePrimaryIdColumn()
    ->ensureColumn(new rex_sql_column('url_id', 'int', false))
    ->ensureColumn(new rex_sql_column('content', 'longtext', false, ''))
    ->ensureColumn(new rex_sql_column('hash', 'VARCHAR(32)', false, ''))
    ->ensureColumn(new rex_sql_column('onepage', 'longtext', false, ''))
    ->ensureColumn(new rex_sql_column('header', 'longtext', false, ''))
    ->ensureColumn(new rex_sql_column('statusCode', 'int', true))
    ->ensureColumn(new rex_sql_column('statusMessage', 'text', true))
    ->ensureGlobalColumns()
    // ->ensureForeignKey(new rex_sql_foreign_key('fk_url', \rex::getTable('diff_detect_url'), ['url_id' => 'id'],
    //     rex_sql_foreign_key::CASCADE, rex_sql_foreign_key::CASCADE))
    ->ensure();

/*\rex_sql_table::get(
    \rex::getTable('diff_detect_category'))
    ->ensurePrimaryIdColumn()
    ->ensureColumn(new \rex_sql_column('name', 'VARCHAR(255)', true))
    ->ensure();*/

/*\rex_sql_table::get(
    \rex::getTable('diff_detect_url2category'))
    ->ensureColumn(new \rex_sql_column('url_id', 'int', false))
    ->ensureColumn(new \rex_sql_column('category_id', 'int', false))
    ->ensureForeignKey(new rex_sql_foreign_key('url', \rex::getTable('diff_detect_url'), ['url_id' => 'id'],
        rex_sql_foreign_key::CASCADE, rex_sql_foreign_key::CASCADE))
    ->ensureForeignKey(new rex_sql_foreign_key('category', \rex::getTable('diff_detect_category'), ['category_id' => 'id'],
        rex_sql_foreign_key::CASCADE, rex_sql_foreign_key::CASCADE))
    ->ensure();*/
