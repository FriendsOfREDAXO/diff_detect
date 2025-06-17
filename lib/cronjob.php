<?php

use FriendsOfRedaxo\DiffDetect\Index;
use FriendsOfRedaxo\DiffDetect\Url;

class rex_cronjob_diff_detect extends rex_cronjob
{
    public function execute()
    {
        $sql = rex_sql::factory();
        $URLs = $sql->getArray(
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
            LIMIT 5
        ',
            [
                'datetime' => date(rex_sql::FORMAT_DATETIME),
            ],
        );

        $messages = [];

        foreach ($URLs as $URLArray) {
            $Url = Url::get($URLArray['id']);
            try {
                if (Index::createSnapshot($Url)) {
                    $messages[] = 'snapshot created for ' . $Url->getName() . ' [' . $Url->getId() . ']';
                } else {
                    $messages[] = 'snapshot not created for ' . $Url->getName() . ' [' . $Url->getId() . ']';
                }
            } catch (rex_exception $e) {
                $messages[] = 'snapshot error for ' . $Url->getName() . ' [' . $Url->getId() . ']';
                break;
            }
        }

        if (0 === count($URLs)) {
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
