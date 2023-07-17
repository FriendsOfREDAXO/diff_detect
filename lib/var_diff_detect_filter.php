<?php

class rex_var_diff_detect_filter extends rex_var
{
    protected function getOutput()
    {
        $id = $this->getArg('id', 0, true);

        $value = $this->getContextData()->getValue('filter_ids' . $id);

        if ($this->hasArg('widget') && $this->getArg('widget')) {
            if (!$this->environmentIs(self::ENV_INPUT)) {
                return false;
            }
            $args = [];
            $value = self::getWidget($id, 'DETECT_FILTERLIST[' . $id . ']', $value, $args);
        }

        return self::quote($value);
    }

    /**
     * @param int|string $id
     * @return string
     */
    public static function getWidget($id, $name, $value, array $args = [])
    {
        $options = '';
        $selectedIds = null === $value ? [] : explode(',', $value);
        foreach ($selectedIds as $selectedId) {
            if ('' == $selectedId) {
                continue;
            }
            if ($item = \DiffDetect\Filter::get((int) $selectedId)) {
                $options .= '<option value="' . $selectedId . '">' . rex_escape(trim(sprintf('%s [%s]', (string) $item->getName(), (string) $item->getId()))) . '</option>';
            }
        }

        $quotedId = "'" . rex_escape($id, 'js') . "'";
        $openFunc = 'openDiffDetectFilterlist(' . $quotedId . ', \'&popup=1\');';
        $deleteFunc = 'deleteDiffDetectFilterlist(' . $quotedId . ');';

        $e = [];
        $e['field'] = '
                <select class="form-control" name="DIFF_DETECT_FILTERLIST_SELECT[' . $id . ']" id="DIFF_DETECT_FILTERLIST_SELECT_' . $id . '" size="5">
                    ' . $options . '
                </select>
                <input type="hidden" name="' . $name . '" id="DIFF_DETECT_FILTERLIST_' . $id . '" value="' . $value . '" />';
        $e['moveButtons'] = '
                    <a href="#" class="btn btn-popup" onclick="moveDiffDetectFilterlist(' . $quotedId . ',\'top\');return false;" title="' . rex_i18n::msg('var_linklist_move_top') . '"><i class="rex-icon rex-icon-top"></i></a>
                    <a href="#" class="btn btn-popup" onclick="moveDiffDetectFilterlist(' . $quotedId . ',\'up\');return false;" title="' . rex_i18n::msg('var_linklist_move_up') . '"><i class="rex-icon rex-icon-up"></i></a>
                    <a href="#" class="btn btn-popup" onclick="moveDiffDetectFilterlist(' . $quotedId . ',\'down\');return false;" title="' . rex_i18n::msg('var_linklist_move_down') . '"><i class="rex-icon rex-icon-down"></i></a>
                    <a href="#" class="btn btn-popup" onclick="moveDiffDetectFilterlist(' . $quotedId . ',\'bottom\');return false;" title="' . rex_i18n::msg('var_linklist_move_bottom') . '"><i class="rex-icon rex-icon-bottom"></i></a>';
        $e['functionButtons'] = '
                    <a href="#" class="btn btn-popup" onclick="' . $openFunc . 'return false;" title="' . rex_i18n::msg('var_link_open') . '"><i class="rex-icon rex-icon-open-linkmap"></i></a>
                    <a href="#" class="btn btn-popup" onclick="' . $deleteFunc . 'return false;" title="' . rex_i18n::msg('var_link_delete') . '"><i class="rex-icon rex-icon-delete-link"></i></a>';

        $fragment = new rex_fragment();
        $fragment->setVar('elements', [$e], false);

        return $fragment->parse('core/form/widget_list.php');
    }
}
