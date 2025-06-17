<?php

namespace FriendsOfRedaxo\DiffDetect;

use HtmlDiffAdvanced;
use Laminas\Feed\Reader\Entry\Rss;
use Laminas\Feed\Reader\Reader;
use rex_addon;

use function array_key_exists;
use function is_string;

class RssDiff
{
    protected string $before = '';
    protected string $after = '';

    public function __construct(string $before, string $after)
    {
        $this->before = $before;
        $this->after = $after;
    }

    /**
     * @return array<int|string, mixed>
     */
    protected function getItems(string $content): array
    {
        $feed = Reader::importString($content);
        $items = [];

        foreach ($feed as $item) {
            $items[$item->getId()] = $item;
        }

        return $items;
    }

    public function calculate(): string
    {
        $itemsBefore = $this->getItems($this->before);
        $itemsAfter = $this->getItems($this->after);

        $output = '';
        /** @var Rss $item */
        foreach ($itemsBefore as $id => $item) {
            if (array_key_exists($id, $itemsAfter)) {
                $diff = HtmlDiffAdvanced::create($this->renderItem($item), $this->renderItem($itemsAfter[$id]));
                $diffContent = $diff->build();

                if ('' !== $diff->getDifference()) {
                    $class = 'modified';
                    $label = '<span class="label label-info">' . rex_addon::get('diff_detect')->i18n('modified') . '</span>';
                } else {
                    $class = 'existing';
                    $label = '<span class="label label-default">' . rex_addon::get('diff_detect')->i18n('old') . '</span>';
                }
                $output .= '<li class="' . $class . '"><div>';
                $output .= $label;
                $output .= $diffContent;
                $output .= '</div>';
            } else {
                $output .= '<li class="new"><div><span class="label label-success">' . rex_addon::get('diff_detect')->i18n('new') . '</span>' . $this->renderItem($item) . '</div>';
            }
            $link = $item->getLink();
            if (is_string($link)) {
                if (($linkAfter = ($itemsAfter[$id] ?? null)?->getLink()) && $link !== $linkAfter) {
                    $output .= '<a class="link" href="' . $link . '" target="_blank"><ins>' . $link . '</ins></a>';
                    $output .= '<a class="link" href="' . $linkAfter . '" target="_blank"><del>' . $linkAfter . '</del></a>';
                } else {
                    $output .= '<a class="link" href="' . $link . '" target="_blank">' . $link . '</a>';
                }
            }

            $output .= '</li>';
        }

        return '<ul class="rss">' . $output . '</ul>';
    }

    public function renderItem(Rss $item): string
    {
        return $item->getContent();
    }
}
