<?php
namespace DiffDetect;

use Caxy\HtmlDiff\HtmlDiffConfig;
use Jfcherng\Diff\DiffHelper;
use Jfcherng\Diff\Factory\RendererFactory;
use Laminas\Feed\Reader\Entry\Rss;

class RssDiff
{
    protected string $before = '';
    protected string $after = '';

    public function __construct($before, $after)
    {
        $this->before = $before;
        $this->after = $after;
    }

    protected function getItems($content): array
    {
        $feed = \Laminas\Feed\Reader\Reader::importString($content);
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
        foreach ($itemsBefore as $id => $item) {
            if (array_key_exists($id, $itemsAfter)) {
                $diff = \HtmlDiffAdvanced::create($this->renderItem($item), $this->renderItem($itemsAfter[$id]));
                $diffContent = $diff->build();

                if ($diff->getDifference()) {
                    $class = 'modified';
                    $label = '<span class="label label-info">'.\rex_addon::get('diff_detect')->i18n('modified').'</span>';
                }
                else {
                    $class = 'existing';
                    $label = '<span class="label label-default">'.\rex_addon::get('diff_detect')->i18n('old').'</span>';
                }
                $output .= '<li class="'.$class.'"><div>';
                $output .= $label;
                $output .= $diffContent;
                $output .= '</div></li>';
            }
            else {
                $output .= '<li class="new"><div><span class="label label-success">'.\rex_addon::get('diff_detect')->i18n('new').'</span>' . $this->renderItem($item) . '</div></li>';
            }
        }

        return '<ul class="rss">' . $output . '</ul>';
    }

    public function renderItem(Rss $item): string
    {
        return $item->getContent();
    }
}
