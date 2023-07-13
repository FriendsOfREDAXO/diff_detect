<?php

namespace FriendsOfRedaxo\DiffDetect;

class Url
{
    use \rex_instance_pool_trait;

    protected ?int $id = null;
    protected $data = [];
    public static $timeout = 5;

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
        $sql->setTable(\rex::getTable('diff_detect_url'));
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

    public function getName(): ?string
    {
        return $this->data['name'];
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

    public function getContent(): \rex_socket_response
    {
        $socket = \rex_socket::factoryUrl($this->getValue('url'));
        $socket->acceptCompression();
        $socket->followRedirects(3);
        $socket->setTimeout(self::$timeout);

        if ($login = $this->getValue('http_auth_login') && $password = $this->getValue('http_auth_password')) {
            $socket->addBasicAuthorization($login, $password);
        }

        $response = $socket->doGet();

        if ($cookie = $response->getHeader('Set-Cookie')) {
            $socket->addHeader('Cookie', substr($cookie, 0, strpos($cookie, ';')));
            $response = $socket->doGet();
        }

        return $response->decompressContent(true);
    }

    public function getType(): string
    {
        return $this->getValue('type');
    }

    public function getUrl(): string
    {
        return $this->getValue('url');
    }

    public function getSnapshots(): array
    {
        return \rex_sql::factory()->getArray(
            '
SELECT      i.id, i.createdate, i.createuser, LENGTH(i.content) size
FROM        '.\rex::getTable('diff_detect_index').' i
WHERE       i.url_id = '.$this->getId().'
ORDER BY    i.createdate DESC'
        );
    }
}
