<?php

class rex_cronjob_diff_detect extends rex_cronjob
{
    public function execute()
    {
        $sql = rex_sql::factory();
        $sql->setQuery('
            SELECT      u.*
                        , i.createdate AS last_index_date
            FROM        ' . rex::getTable('diff_detect_url') . ' u
            LEFT JOIN   (
                SELECT  url_id, MAX(createdate) createdate
                FROM    ' . rex::getTable('diff_detect_index') . '
                GROUP BY url_id
                ) i
            ON          u.id = i.url_id
            WHERE       u.status = 1
            AND         (
                i.createdate IS NULL
                OR  i.createdate < DATE_SUB(NOW(), INTERVAL u.interval MINUTE)
            )
        ');

        for ($i = 0; $i < $sql->getRows(); $i++) {
            \FriendsOfRedaxo\DiffDetect\Index::createSnapshot(\FriendsOfRedaxo\DiffDetect\Url::get($sql->getValue('id')));
            $sql->next();
        }

        return true;
    }

    public function getTypeName()
    {
        return rex_i18n::msg('diff_detect_create_snapshots');
    }
}
