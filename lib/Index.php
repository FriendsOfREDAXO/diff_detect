<?php

namespace FriendsOfRedaxo\DiffDetect;

use Exception;
use Html2Text\Html2Text;
use InvalidArgumentException;
use rex;
use rex_addon;
use rex_exception;
use rex_instance_pool_trait;
use rex_sql;
use rex_sql_exception;

use function is_array;
use function sprintf;

final class Index
{
    use rex_instance_pool_trait;

    protected ?Url $url;
    protected ?int $id = null;
    /** @var array<string, mixed> */
    protected $data = [];

    private function __construct(int $id)
    {
        $this->id = $id;
    }

    /**
     * @return static|null
     */
    public static function get(int $id): ?self
    {
        if ($id <= 0) {
            throw new InvalidArgumentException(sprintf('$id has to be an integer greater than 0, but "%s" given', (string) $id));
        }

        $sql = rex_sql::factory();
        $sql->setTable(rex::getTable('diff_detect_index'));
        $sql->setWhere('id = ?', [$id]);
        $sql->select();

        if (null === $sql->getRows()) {
            return null;
        }

        $data = [];
        foreach ($sql->getFieldnames() as $key) {
            $data[$key] = $sql->getValue($key);
        }

        $index = self::fromSqlData($data);
        $index->setUrl(Url::get($index->getValue('url_id')));

        return $index;
    }

    /** @api */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return $this
     * @api
     */
    public function setValue(string $key, mixed $value): self
    {
        $this->data[$key] = $value;

        return $this;
    }

    public function getValue(string $key): mixed
    {
        if ('id' === $key) {
            return $this->id;
        }

        return $this->data[$key];
    }

    /**
     * @param array<string, mixed> $data
     */
    private static function fromSqlData(array $data): self
    {
        $id = (int) $data['id'];

        /** @var static $dataset */
        $dataset = new static($id);
        self::addInstance([$id], $dataset);

        $dataset->data = $data;

        return $dataset;
    }

    /**
     * @throws rex_sql_exception
     */
    public static function createSnapshot(Url $url): bool
    {
        $url->setLastScan();
        $content = '';
        $headers = [];
        try {
            $response = $url->getResponse();
            $content = $response['Content'] ?? '';
            $headers = $response['Headers'] ?? [];
            $statusCode = $response['StatusCode'] ?? 0;
            $statusMessage = '[' . $statusCode . '] OK';
        } catch (Exception $e) {
            $statusCode = $e->getCode();
            $statusMessage = '[' . $statusCode . '] ' . $e->getMessage();
        }

        $url->setLastMessage($statusMessage);

        $headers = self::flattenArray($headers);

        if ('HTML' === $url->getType()) {
            $content = (new Html2Text($content))->getText();
        }

        $hash = md5($content);
        $sql = rex_sql::factory();
        $sql->setTable(rex::getTable('diff_detect_index'));
        $sql->setWhere('url_id = ? ORDER BY createdate DESC LIMIT 1', [$url->getId()]);
        $sql->select('id,`hash`');

        if (0 !== ($sql->getRows() ?? 0) && $sql->getValue('hash') === $hash) {
            $sql->setTable(rex::getTable('diff_detect_index'));
            $sql->setValue('updatedate', date(rex_sql::FORMAT_DATETIME));
            $sql->setWhere('id = :id', ['id' => $sql->getValue('id')]);
            $sql->update();
            return false;
        }

        $sql->setTable(rex::getTable('diff_detect_index'));
        $sql->addGlobalCreateFields();
        $sql->addGlobalUpdateFields();
        $sql->setValue('url_id', $url->getId());
        $sql->setValue('content', $content);
        $sql->setValue('hash', $hash);
        $sql->setValue('header', implode(',', $headers));
        $sql->setValue('statusCode', $statusCode);
        $sql->setValue('statusMessage', $statusMessage);
        $sql->insert();

        return true;
    }

    public static function cleanUpSnapshots(): void
    {
        $addon = rex_addon::get('diff_detect');
        $cleanup_interval = $addon->getConfig('cleanup_interval');

        if (null === $cleanup_interval || 0 === $cleanup_interval) {
            return;
        }

        $cleanup_interval = (int) $cleanup_interval;

        foreach (Url::getAll() as $URL) {
            $indeces = rex_sql::factory()->getArray('
                SELECT *
                FROM ' . rex::getTable('diff_detect_index') . ' as t1
                JOIN (
                    SELECT id
                    FROM ' . rex::getTable('diff_detect_index') . '
                    WHERE url_id = :url_id
                    ORDER BY createdate DESC
                    LIMIT 2,100000
                ) as t2 ON t1.id = t2.id
                WHERE
                url_id = :url_id
                AND createdate < DATE_SUB(:datetime, INTERVAL :interval MINUTE)
                ORDER BY createdate ASC
                LIMIT 100
            ', [
                'url_id' => $URL->getId(),
                'datetime' => date(rex_sql::FORMAT_DATETIME),
                'interval' => $cleanup_interval,
            ]);

            foreach ($indeces as $Index) {
                $Index = self::fromSqlData($Index);
                $Index->delete();
                echo ' Deleted index with ID: ' . $Index->getId() . ' for URL: ' . $URL->getName() . "\n";
            }
        }
    }

    public function setUrl(Url $url): self
    {
        $this->url = $url;
        return $this;
    }

    /** @api */
    public function getUrl(): ?Url
    {
        return $this->url;
    }

    public function getContent(): string
    {
        return $this->getValue('content');
    }

    public function delete(): void
    {
        $sql = rex_sql::factory()->setQuery(
            '
        DELETE from ' . rex::getTable('diff_detect_index') . '
        WHERE id = :id
        ',
            [
                'id' => $this->getId(),
            ],
        );

        if (null !== $sql->getError()) {
            throw new rex_exception($sql->getError());
        }
    }

    private static function flattenArray($array, $prefix = '')
    {
        $result = [];

        foreach ($array as $key => $value) {
            $newKey = '' === $prefix ? $key : $prefix . '.' . $key;

            if (is_array($value)) {
                $result = array_merge($result, self::flattenArray($value, $newKey));
            } else {
                $result[$newKey] = $value;
            }
        }

        return $result;
    }
}
