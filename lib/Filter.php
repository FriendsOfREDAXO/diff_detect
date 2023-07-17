<?php

namespace FriendsOfRedaxo\DiffDetect;

use InvalidArgumentException;
use rex;
use rex_addon;
use rex_instance_pool_trait;
use rex_sql;

class Filter
{
    use rex_instance_pool_trait;

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
            throw new InvalidArgumentException(sprintf('$id has to be an integer greater than 0, but "%s" given', (string) $id));
        }

        $sql = rex_sql::factory();
        $sql->setTable(rex::getTable('diff_detect_filter'));
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

    public function getName(): string
    {
        if ($this->getValue('name')) {
            return $this->getValue('name');
        }

        $addon = rex_addon::get('diff_detect');

        $name = '';
        switch ($this->getValue('type')) {
            case 'strip_tags':
                $name .= $addon->i18n('filter_type_strip_tags_select');
                break;
            case 'CSS':
                $name .= $addon->i18n('filter_type_css');
                break;
            case 'RegEx':
                $name .= $addon->i18n('filter_type_regex');
        }

        switch (true) {
            case ('CSS' === $this->getValue('type') || 'RegEx' === $this->getValue('type')) && 'remain' === $this->getValue('mode'):
            case 'strip_tags' === $this->getValue('type') && '' !== $this->getValue('params'):
                $name .= ', ' . $addon->i18n('filter_mode_remain');
                break;
            case ('CSS' === $this->getValue('type') || 'RegEx' === $this->getValue('type')) && 'remove' === $this->getValue('mode'):
                $name .= ', ' . $addon->i18n('filter_mode_remove');
                break;
        }

        if ('' !== $this->getValue('params')) {
            $name .= ': ' . $this->getValue('params');
        }

        return $name;
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
}
