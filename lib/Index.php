<?php

namespace FriendsOfRedaxo\DiffDetect;

use Html2Text\Html2Text;
use InvalidArgumentException;
use rex;
use rex_addon;
use rex_exception;
use rex_instance_pool_trait;
use rex_sql;
use rex_sql_exception;
use voku\helper\HtmlDomParser;

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
     * @return null|static
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
        $response = $url->getContent();
        $content = $response->getBody();

        if ('HTML' === $url->getType()) {
            $content = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $content);
            $content = preg_replace('/<style\b[^>]*>(.*?)<\/style>/is', '', $content);
            $content = preg_replace('/<noscript\b[^>]*>(.*?)<\/noscript>/is', '', $content);
            $content = strip_tags($content, ['img', 'video']);
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
        $sql->setValue('content', $response->getBody());
        $sql->setValue('hash', $hash);
        $sql->setValue('header', $response->getHeader());
        $sql->setValue('statusCode', $response->getStatusCode());
        $sql->setValue('statusMessage', $response->getStatusMessage());
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

        $sql = rex_sql::factory();
        $sql->setTable(rex::getTable('diff_detect_index'));
        $sql->setWhere('createdate < DATE_SUB(:datetime, INTERVAL :interval SECOND)', [
            'datetime' => date(rex_sql::FORMAT_DATETIME),
            'interval' => $cleanup_interval,
        ]);
        $sql->delete();
        if (null !== $sql->getError()) {
            throw new rex_exception($sql->getError());
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
        if ('RSS' === $this->url?->getType()) {
            return $this->getValue('content');
        }

        $content = $this->getValue('content');
        // $content = HtmlDomParser::str_get_html($content)->findOne('#content')->innerHtml();
        $content = (new Html2Text($content))->getText();
        return $content;
    }
}
