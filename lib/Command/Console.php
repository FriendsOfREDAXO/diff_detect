<?php

namespace FriendsOfRedaxo\DiffDetect\Command;

use FriendsOfRedaxo\DiffDetect\Index;
use FriendsOfRedaxo\DiffDetect\Url;
use rex;
use rex_console_command;
use rex_exception;
use rex_sql;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use function count;

class Console extends rex_console_command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = $this->getStyle($input, $output);
        $io->title('DiffDetect Console Command');

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

        foreach ($URLs as $URLArray) {
            $Url = Url::get($URLArray['id']);
            try {
                if (Index::createSnapshot($Url)) {
                    $io->success('Snapshot created for URL: ' . $Url->getName() . ' / ' . $Url->getUrl() . ' [' . $Url->getId() . ']');
                } else {
                    $io->success('Snapshot NOT created for URL: ' . $Url->getName() . ' / ' . $Url->getUrl() . ' [' . $Url->getId() . ']');
                }
            } catch (rex_exception $e) {
                $io->error('Snapshot error for URL: ' . $Url->getName() . ' / ' . $Url->getUrl() . ' [' . $Url->getId() . ']');
                break;
            }
        }

        if (0 === count($URLs)) {
            $io->info('no snapshots');
        }

        $io->text('Total URLs processed: ' . count($URLs));
        $io->text('');

        Index::cleanUpSnapshots();

        return 1;
    }
}
