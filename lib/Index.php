<?php

namespace FriendsOfRedaxo\DiffDetect;

use Html2Text\Html2Text;
use voku\helper\HtmlDomParser;

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

        $index = self::fromSqlData($data);
        $index->setUrl(Url::get($index->getValue('url_id')));

        return $index;
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

    public static function createSnapshot(Url $url): bool
    {
        $response = $url->getContent();
        $content = $response->getBody();

        if ($url->getType() === 'HTML') {
//            $onepage = (new HtmlOnepage($url->getUrl(), $content))->get();
            $onepage = '';
        }
        else {
            $onepage = '';
        }

        $hash = md5($content);

        $sql = \rex_sql::factory();
        $sql->setTable(\rex::getTable('diff_detect_index'));
        $sql->setWhere('url_id = ? ORDER BY createdate DESC LIMIT 1', [$url->getId()]);
        $sql->select('id,`hash`');

        if ($sql->getRows() and $sql->getValue('hash') === $hash) {
            $sql->setTable(\rex::getTable('diff_detect_index'));
            $sql->setValue('updatedate', date(\rex_sql::FORMAT_DATETIME));
            $sql->setWhere('id = :id', ['id' => $sql->getValue('id')]);
            $sql->update();
            return false;
        }

        $sql->setTable(\rex::getTable('diff_detect_index'));
        $sql->addGlobalCreateFields();
        $sql->addGlobalUpdateFields();
        $sql->setValue('url_id', $url->getId());
        $sql->setValue('content', $response->getBody());
        $sql->setValue('onepage', $onepage);
        $sql->setValue('hash', $hash);
        $sql->setValue('header', $response->getHeader());
        $sql->setValue('statusCode', $response->getStatusCode());
        $sql->setValue('statusMessage', $response->getStatusMessage());
        $sql->insert();

        return true;
    }

    public function setUrl(Url $url)
    {
        $this->url = $url;
        return $this;
    }

    public function getUrl(): ?Url
    {
        return $this->url;
    }

    public function getContent()
    {
        if ($this->url?->getType() === 'RSS') {
            return $this->getValue('content');
        }

        $content = $this->getValue('content');
        // $content = HtmlDomParser::str_get_html($content)->findOne('#content')->innerHtml();
        $content = (new Html2Text($content))->getText();
        return $content;
    }

    public function getOnepage()
    {
        return $this->getValue('onepage');
    }
}
