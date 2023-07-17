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

        for ($i = 0; $i < $sql->getRows(); ++$i) {
            $Url = \FriendsOfRedaxo\DiffDetect\Url::get($sql->getValue('id'));
            try {
                if (\FriendsOfRedaxo\DiffDetect\Index::createSnapshot($Url)) {
                    echo rex_view::success(rex_i18n::msg('diff_detect_snapshot_created', $Url->getName()));
                } else {
                    echo rex_view::success(rex_i18n::msg('diff_detect_snapshot_not_created', $Url->getName()));
                }
            } catch (rex_exception $e) {
                echo rex_view::error(rex_i18n::msg('diff_detect_snapshot_error', $Url->getName(), $e->getMessage()));
                break;
            }
            $sql->next();
        }

        return true;
    }

    public function getTypeName()
    {
        return rex_i18n::msg('diff_detect_create_snapshots');
    }
}
