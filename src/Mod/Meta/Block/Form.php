<?php
namespace Mod\Meta\Block;

class Form extends \Verba\Block\Json
{
    function build()
    {
        $bp = $this->request->asArray();
        if (!isset($bp['pot']) || !is_array($bp['pot'])) {
            return '';
        }

        $key = key($bp['pot']);
        $pot = \Verba\_oh($key);

        if (!$pot) {
            return '';
        }

        if (!is_array($bp['pot'][$key]) || !($piid = current($bp['pot'][$key]))) {
            return '';
        }
        /**
         * @var $mod \Mod\Meta
         */
        $mod = \Verba\_mod('meta');
        $cfg = $mod->gC();
        $_meta = \Verba\_oh('meta');
        $pac = $_meta->getPAC();
        $this->tpl->define(array(
            'meta_form' => 'aef/fe/meta/form.tpl',
            'meta_form_lc' => 'aef/fe/meta/lc.tpl',
            'meta_form_row' => 'aef/fe/meta/row.tpl',
            'meta_textarea' => 'aef/fe/meta/textarea.tpl',
        ));
        $types_pd = array_flip($_meta->A('type')->getValues());
        $options = $_meta->A('insert')->getValues();

        $this->tpl->assign(array(
            'LOCALE_SELECTOR' => '',
            'OT_ID' => $_meta->getID(),
            'PAC' => $_meta->getPAC(),
            'POT' => $pot->getID(),
            'PIID' => $piid,
            'FORWARD_ACTION' => '/acp/aenow/meta/editnow',
            'META_PAC' => $pac,
            'INSERT_OPTIONS' => '',
        ));
        $meta_data = $mod->loadObjectMeta(array(array('ot_id' => $pot->getID(), $pot->getPAC() => $piid)));

        $locales = \Verba\Lang::getUsedLC();
        $lc = SYS_LOCALE;
        $lc_count = count($locales);
        foreach ($locales as $locale) {
            if ($lc_count > 1) {
                $this->tpl->assign(array(
                    'LOCALE' => $locale,
                    'LINK_CLASS' => $locale == $lc ? 'sel' : '',
                ));
                $this->tpl->parse('LOCALE_SELECTOR', 'meta_form_lc', true);
            }
        }

        $index = 0;
        $k = $pot->getID() . '_' . $piid;
        foreach ($mod->metaKeys as $type) {
            $meta_id = 0;
            $value = '';
            $connector = '';
            $insert = $options['before'];
            $options_html = '';
            $rules = '';

            if (is_array($meta_data[$k][$type])) {
                $meta_id = $meta_data[$k][$type][$pac];
                $insert = $meta_data[$k][$type]['insert'];
                $connector = $meta_data[$k][$type]['connector'];
                $rules = $meta_data[$k][$type]['rules'];
            }
            $this->tpl->clear_vars(array('META_ELEMENT'));
            $this->tpl->assign(array(
                'TYPE_ID' => $types_pd[$type],
                'META_ID' => $meta_id,
                'META_TYPE' => $type,
                'META_VALUE' => $value,
                'CONECTOR_VALUE' => $connector,
                'RULES_VALUE' => $rules,
                'OBJECT_INDEX' => $index,
            ));
            foreach ($locales as $locale) {
                $value = $meta_data[$k][$type]['meta_' . $locale];
                $this->tpl->assign(array(
                    'META_VALUE' => $value,
                    'LOCALE' => $locale,
                    'VISIBLE' => $locale == $lc ? '' : 'hidden',
                ));
                $this->tpl->parse('META_ELEMENT', 'meta_textarea', true);
            }
            foreach ($options as $pd_id => $title) {
                $selected = $pd_id == $insert ? 'selected' : '';
                $options_html .= '<option ' . $selected . ' value="' . $pd_id . '">' . $title . '</option>';
            }
            $this->tpl->assign('INSERT_OPTIONS', $options_html);
            $this->tpl->parse('META_FORM_ROWS', 'meta_form_row', true);
            ++$index;
        }
        $this->content = $this->tpl->parse(false, 'meta_form');
        return $this->content;
    }

}