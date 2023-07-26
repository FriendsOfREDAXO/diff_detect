<?php

class rex_cronjob_diff_detect extends rex_cronjob
{
    public function execute()
    {
        $sql = rex_sql::factory();
        $sql->setQuery(
            '
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
                u.last_scan IS NULL
                OR  u.last_scan < DATE_SUB(:datetime, INTERVAL u.interval MINUTE)
            )
            order by u.last_scan
        ',
            [
                'datetime' => date(rex_sql::FORMAT_DATETIME),
            ]
        );

        for ($i = 0; $i < $sql->getRows(); ++$i) {
            $Url = \FriendsOfRedaxo\DiffDetect\Url::get($sql->getValue('id'));
            try {
                if (\FriendsOfRedaxo\DiffDetect\Index::createSnapshot($Url)) {
                    $this->setMessage('snapshot created for '.$Url->getName().' ['.$Url->getId().']');
                } else {
                    $this->setMessage('snapshot not created for '.$Url->getName().' ['.$Url->getId().']');
                }
            } catch (Exception $e) {
                $this->setMessage('snapshot error for '.$Url->getName().' ['.$Url->getId().']');
                break;
            }
            $sql->next();
        }

        if (0 === $sql->getRows()) {
            $this->setMessage('no snapshots');
        }

        return true;
    }

    public function getTypeName()
    {
        return rex_i18n::msg('diff_detect_create_snapshots');
    }
}
