<?php
namespace Verba\Mod\CfgModify\Block;

class Form extends \Verba\Block\Json
{
    public $modcode;

    function build()
    {
        $this->mod = \Verba\_mod($this->modcode);
        $this->addScripts('customizecfg', 'modules/customizecfg');
        $this->addCSS('customizecfg', 'modules/customizecfg');
        $this->tpl->define(array(
            'body' => '/baseModule/cfgcustomize/body.tpl',
            'row' => '/baseModule/cfgcustomize/row.tpl',
            'element_string' => '/baseModule/cfgcustomize/element/string.tpl',
            'element_common' => '/baseModule/cfgcustomize/element/common.tpl',
            'element_boolean' => '/baseModule/cfgcustomize/element/boolean.tpl',
            'element_array' => '/baseModule/cfgcustomize/element/array.tpl',
            'element_arrayext' => '/baseModule/cfgcustomize/element/arrayext.tpl',
            'element_array_item' => '/baseModule/cfgcustomize/element/array_item.tpl',
        ));

        $modCfg = \Verba\_mod('cfgmodify');
        $modCfg->targetMod = $this->mod;
        $cstm = $this->mod->gC('_customizable');
        $keys = $modCfg->getCustomizableConfigKeys($this->mod);
        $formId = $modCfg->getCfgFormId($this->mod);
        if (!count($keys) || !is_array($cstm) || !count($cstm)) {
            $this->tpl->assign(array(
                'CFG_ROWS' => '',
            ));
        } else {
            $this->tpl->assign(array(
                'CFG_FORM_ACTION_URL' => '/acp/h/cfgmodify/customize/' . $this->mod->getCode(),
                'CFG_FORM_ID' => $formId,
            ));
            foreach ($keys as $key => $keyData) {
                $ename = str_replace(' ', '-', $key);
                $value = $this->mod->gC($key);

                $etype = isset($keyData['etype']) ? $keyData['etype'] : (isset($keyData['datatype']) ? $keyData['datatype'] : 'string');
                $mtd = 'createCfgElement' . ucfirst(strtolower($etype));
                if (!method_exists($this, $mtd)) {
                    $mtd = 'createCfgElementString';
                }
                $element = $this->$mtd($key, $ename, $value, $keyData, $this->mod);

                $this->tpl->assign(array(
                    'CFG_KEY' => isset($keyData['title']) ? (string)$keyData['title'] : 'Unk',
                    'CFG_ELEMENT' => $element
                ));
                $this->tpl->parse('CFG_ROWS', 'row', true);
            }
        }
        $this->content = $this->tpl->parse(false, 'body');

        return $this->content;
    }

    function createCfgElementArray($key, $ename, $value, $keyData, $mod = false)
    {
        if (!is_array($value)) return '';
        $modCfg = \Verba\_mod('cfgmodify');
        $mod = $mod == false ? $this->mod : $mod;
        $items = array();
        $vcfg = isset($keyData['array']['value']) && count($keyData['array']['value']) ? $keyData['array']['value'] : array();
        $kcfg = isset($keyData['array']['key']) && count($keyData['array']['key']) ? $keyData['array']['key'] : array();
        foreach ($value as $vkey => $v) {
            $items[] = array(
                'key' => $modCfg->normalizeCfgValue($vkey, $kcfg),
                'value' => $modCfg->normalizeCfgValue($v, $vcfg),
            );
        }
        $warapId = $ename . '_wrap';
        $arrayData = array(
            'formId' => $modCfg->getCfgFormId($mod),
            'ename' => $ename,
            'wrapId' => $warapId,
            'keyTitle' => isset($keyData['array']['key']['title']) && count($keyData['array']['key']['title']) ? $keyData['array']['key']['title'] : '',
            'valueTitle' => isset($keyData['array']['value']['title']) && count($keyData['array']['value']['title']) ? $keyData['array']['value']['title'] : '',
            'items' => $items,
            'extraHandler' => isset($keyData['extraHandler']) && !empty($keyData['extraHandler'])
                ? $keyData['extraHandler']
                : false,
        );
        $this->tpl->assign(array(
            'CFG_ELEMENT_WRAP_CLASS' => $warapId,
            'CFG_ELEMENT_ARRAY_DATA' => \json_encode($arrayData),
        ));
        return $this->tpl->parse(false, 'element_array');
    }

    function createCfgElementArrayExt($key, $ename, $value, $keyData, $mod = false)
    {

        if (!is_array($value)) return '';
        /**
         * @var $modCfg \Verba\Mod\CfgModify
         */

        $modCfg = \Verba\_mod('cfgmodify');
        $mod = $mod == false ? $this->mod : $mod;
        $items = array();

        $kcfg = isset($keyData['array'][0]) && count($keyData['array'][0])
            ? $keyData['array'][0]
            : array();
        $vcfg = array();
        for ($i = 1; $i < count($keyData['array']); $i++) {
            $vcfg[$i] = isset($keyData['array'][$i]) && count($keyData['array'][$i])
                ? $keyData['array'][$i]
                : array();
        }

        foreach ($value as $vkey => $vls) {
            $fidx = 0;
            $citem = array(
                'key' => $modCfg->normalizeCfgValue($vkey, $kcfg),
                'values' => array(),
            );
            if (!is_array($vls)) {
                $vls = array($vls);
            }
            foreach ($vls as $v) {
                $citem['values']['i' . $fidx] = $modCfg->normalizeCfgValue($v, $vcfg[++$fidx]);
            }
            $items[] = $citem;
        }
        $warapId = $ename . '_wrap';
        $arrayData = array(
            'formId' => $modCfg->getCfgFormId($mod),
            'ename' => $ename,
            'wrapId' => $warapId,
            'itemsCfg' => $keyData['array'],
            'items' => $items,
            'extraHandler' => isset($keyData['extraHandler']) && !empty($keyData['extraHandler'])
                ? $keyData['extraHandler']
                : false,

        );
        $this->tpl->assign(array(
            'CFG_ELEMENT_WRAP_CLASS' => $warapId,
            'CFG_ELEMENT_ARRAY_DATA' => json_encode($arrayData),
        ));
        return $this->tpl->parse(false, 'element_arrayext');
    }

    function createCfgElementString($key, $ename, $value, $keyData, $mod = false)
    {
        /**
         * @var $modCfg \Verba\Mod\CfgModify
         */
        $modCfg = \Verba\_mod('cfgmodify');

        $value = $modCfg->normalizeCfgValue($value, $keyData);
        $this->tpl->assign(array(
            'CFG_ELEMENT_BOX_NAME' => $ename . '_box',
            'CFG_ELEMENT_NAME' => str_replace(' ', '-', $key),
            'CFG_ELEMENT_VALUE' => $value,
        ));
        return $this->tpl->parse(false, 'element_string');
    }

    function createCfgElementBoolean($key, $ename, $value, $keyData, $mod = false)
    {
        /**
         * @var $modCfg \Verba\Mod\CfgModify
         */
        $modCfg = \Verba\_mod('cfgmodify');

        $value = $modCfg->normalizeCfgValue($value, $keyData);
        $sl = new \Verba\Html\Select(array(
            'name' => str_replace(' ', '-', $key)
        ));
        $sl->setValues(array(0 => \Verba\Lang::get('acp customizecfg boolean values 0'), 1 => \Verba\Lang::get('acp customizecfg boolean values 1'),));
        $sl->setValue(intval($value));

        $this->tpl->assign(array(
            'CFG_ELEMENT_BOX_NAME' => $ename . '_box',
            'CFG_ELEMENT_BOOLEAN' => $sl->build(),
        ));
        return $this->tpl->parse(false, 'element_boolean');
    }

    function createCfgElementTextarea($key, $ename, $value, $keyData, $mod = false)
    {
        /**
         * @var $modCfg \Verba\Mod\CfgModify
         */
        $modCfg = \Verba\_mod('cfgmodify');

        $value = $modCfg->normalizeCfgValue($value, $keyData);
        $cfg = array(
            'name' => str_replace(' ', '-', $key)
        );
        if (isset($keyData['econfig']) && is_array($keyData['econfig'])) {
            $cfg = array_replace_recursive($cfg, $keyData['econfig']);
        }
        $sl = new \Verba\Html\Textarea($cfg);
        $sl->setValue($value);

        $this->tpl->assign(array(
            'CFG_ELEMENT_BOX_NAME' => $ename . '_box',
            'CFG_ELEMENT_COMMON' => $sl->build(),
        ));
        return $this->tpl->parse(false, 'element_common');
    }

}
