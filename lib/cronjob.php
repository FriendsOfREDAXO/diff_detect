<?php

use FriendsOfRedaxo\DiffDetect\Index;

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
            ],
        );

        $messages = [];

        for ($i = 0; $i < $sql->getRows(); ++$i) {
            $Url = \FriendsOfRedaxo\DiffDetect\Url::get((int) $sql->getValue('id'));
            try {
                if (Index::createSnapshot($Url)) {
                    $messages[] = 'snapshot created for ' . $Url->getName() . ' [' . $Url->getId() . ']';
                } else {
                    $messages[] = 'snapshot not created for ' . $Url->getName() . ' [' . $Url->getId() . ']';
                }
            } catch (Exception $e) {
                $messages[] = 'snapshot error for ' . $Url->getName() . ' [' . $Url->getId() . ']';
                break;
            }
            $sql->next();
        }

        if (0 === $sql->getRows()) {
            $messages[] = 'no snapshots';
        }

        $this->setMessage(implode("\n", $messages));

        Index::cleanUpSnapshots();

        return true;
    }

    public function getTypeName()
    {
        return rex_i18n::msg('diff_detect_create_snapshots');
    }
}
