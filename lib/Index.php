<?php

namespace DiffDetect;

use Html2Text\Html2Text;

class Index
{
    use \rex_instance_pool_trait;

    protected ?Url $url;
    protected ?int $id = null;
    protected $data = [];

    private function __construct($id)
    {
        $this->id = $id;
    }

    /**
     * @return null|static
     */
    public static function get(int $id): ?self
    {
        if ($id <= 0) {
            throw new \InvalidArgumentException(sprintf('$id has to be an integer greater than 0, but "%s" given', $id));
        }

        $sql = \rex_sql::factory();
        $sql->setTable(\rex::getTable('diff_detect_index'));
        $sql->setWhere('id = ?', [$id]);
        $sql->select();

        if (!$sql->getRows()) {
            return null;
        }

        $data = [];
        foreach ($sql->getFieldnames() as $key) {
            $data[$key] = $sql->getValue($key);
        }

        return self::fromSqlData($data);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @param mixed $value
     * @return $this
     */
    public function setValue(string $key, $value): self
    {
        $this->data[$key] = $value;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getValue(string $key)
    {
        if ('id' === $key) {
            return $this->id;
        }

        return $this->data[$key];
    }

    /**
     * @return static
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

    public static function createSnapshot(Url $url)
    {
        $response = $url->getContent();

        $sql = \rex_sql::factory();
        $sql->setTable(\rex::getTable('diff_detect_index'));
        $sql->addGlobalCreateFields();
        $sql->addGlobalUpdateFields();
        $sql->setValue('url_id', $url->getId());
        $sql->setValue('content', $response->getBody());
        $sql->setValue('header', $response->getHeader());
        $sql->setValue('statusCode', $response->getStatusCode());
        $sql->setValue('statusMessage', $response->getStatusMessage());
        $sql->insert();
    }

    public function setUrl(Url $url)
    {
        $this->url = $url;
        return $this;
    }

    public function getContent()
    {
        $content = $this->getValue('content');
        $content = (new Html2Text($content))->getText();
        return $content;
    }
}
