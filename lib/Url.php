<?php

namespace FriendsOfRedaxo\DiffDetect;

use Exception;
use InvalidArgumentException;
use rex;
use rex_addon;
use rex_instance_pool_trait;
use rex_sql;
use rex_sql_exception;
use Symfony\Component\HttpClient\HttpClient;

use function sprintf;

final class Url
{
    use rex_instance_pool_trait;
    public const DefaultTimeOut = 5;
    public const DefaultUserAgent = 'Mozilla/5.0 (DiffDetect Bot)';

    protected ?int $id = null;
    /** @var array<string, mixed> */
    protected array $data = [];
    private static int $maxRedirects = 5;

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
        $sql->setTable(rex::getTable('diff_detect_url'));
        $sql->setWhere('id = ?', [$id]);
        $sql->select();

        if (null === $sql->getRows()) {
            return null;
        }

        $data = [];
        foreach ($sql->getFieldnames() as $key) {
            $data[$key] = $sql->getValue($key);
        }

        return self::fromSqlData($data);
    }

    /**
     * @throws rex_sql_exception
     * @return array<Url>
     */
    public static function getAll(): array
    {
        $sql = rex_sql::factory();
        $sql->setTable(rex::getTable('diff_detect_url'));
        $sql->select();

        $urls = [];
        foreach ($sql->getArray() as $data) {
            $urls[] = self::fromSqlData($data);
        }

        return $urls;
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

    public function getResponse(): array
    {
        $addon = rex_addon::get('diff_detect');

        $Options = [
            'timeout' => $addon->getConfig('timeout') ?? self::DefaultTimeOut,
            'max_redirects' => self::$maxRedirects,
            'headers' => [
                'User-Agent' => $addon->getConfig('user_agent') ?? self::DefaultUserAgent,
                // 'Accept-Encoding' => 'gzip, deflate, br',
            ],
        ];

        // Opt. Basic Auth
        $login = $this->getValue('http_auth_login');
        $password = $this->getValue('http_auth_password');
        if ('' !== $login && '' !== $password) {
            $Options['headers']['Authorization'] = 'Basic ' . base64_encode($login . ':' . $password);
        }

        // Opt. Proxy
        $proxy = trim($addon->getConfig('proxy'));
        if ('' !== $proxy) {
            $Options['proxy'] = $proxy;
        }

        $client = HttpClient::create();
        $response = $client->request('GET', $this->getValue('url'), $Options);

        $stream = $client->stream($response);

        $content = '';
        foreach ($stream as $chunk) {
            $content .= $chunk->getContent();
        }

        $headers = $response->getHeaders();
        $statusCode = $response->getStatusCode();

        if (200 !== $response->getStatusCode()) {
            throw new Exception(sprintf('Failed to fetch content from URL "%s". HTTP status code: %d', $this->getValue('url'), $response->getStatusCode()));
        }

        if ('' === $content) {
            throw new Exception(sprintf('No content received from URL "%s".', $this->getValue('url')));
        }

        unset($response);

        return [
            'Content' => $content,
            'Headers' => $headers,
            'StatusCode' => $statusCode,
        ];
    }

    public function getType(): string
    {
        return $this->getValue('type');
    }

    /** @api */
    public function getUrl(): string
    {
        return $this->getValue('url');
    }

    /**
     * @throws rex_sql_exception
     * @return array<int, array<string, mixed>>
     */
    public function getSnapshots(): array
    {
        return rex_sql::factory()->getArray(
            '
SELECT      i.id, i.createdate, i.createuser, LENGTH(i.content) size, i.checked
FROM        ' . rex::getTable('diff_detect_index') . ' i
WHERE       i.url_id = ' . $this->getId() . '
ORDER BY    i.createdate DESC',
        );
    }

    public function setLastScan(): void
    {
        rex_sql::factory()->setQuery(
            'update ' . rex::getTable('diff_detect_url') . ' set last_scan = :last_scan where id = :id',
            [
                'id' => $this->getId(),
                'last_scan' => date(rex_sql::FORMAT_DATETIME),
            ],
        );
    }

    public function setLastMessage(string $message): void
    {
        rex_sql::factory()->setQuery(
            'update ' . rex::getTable('diff_detect_url') . ' set last_message = :last_message where id = :id',
            [
                'id' => $this->getId(),
                'last_message' => $message,
            ],
        );
    }
}
