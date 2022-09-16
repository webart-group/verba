<?php

class page_eInteractive extends page_element
{

    public $templates = array(
        'content' => '/page/elements/interactive/default.tpl',
        'ui' => false,
    );

    /**
     * @var $eid string ID элемента
     */
    public $eid;
    public $component;
    public $script;
    public $style;
    public $classes;
    public $state;
    public $group;
    public $data;
    public $rq_data;
    /**
     * @var \Verba\Html\Element
     */
    public $ui;

    protected $attr_prefix = 'data-ui-tool';

    function build()
    {

        $this->tpl->assign(array(
            'E_ID' => $this->eid,
        ));

        $wrapECfg = array(
            'id' => $this->eid,
            'attr' => array(
                $this->attr_prefix => $this->component,
            ),
            'classes' => 'clearfix',
        );
        // state
        if (is_string($this->state) && !empty($this->state)) {
            $wrapECfg['attr'][$this->attr_prefix . '-state'] = $this->state;
        }
        // group
        if (is_string($this->group) && !empty($this->group)) {
            $wrapECfg['attr'][$this->attr_prefix . '-group'] = $this->group;
        }
        // script
        if (is_string($this->script) && !empty($this->script)) {
            $wrapECfg['attr'][$this->attr_prefix . '-script'] = $this->script;
        }
        // style
        if (is_string($this->style) && !empty($this->style)) {
            $wrapECfg['attr'][$this->attr_prefix . '-style'] = $this->style;
        }
        // classes
        if (is_string($this->classes) && !empty($this->classes)) {
            $wrapECfg['classes'] = $this->classes;
        }
        // data
        if ($this->data && !empty($this->data)) {
            $wrapECfg['attr'][$this->attr_prefix . '-data'] = json_encode($this->data, JSON_FORCE_OBJECT);
        }
        // rq
        if ($this->rq_data && !empty($this->rq_data)) {
            $wrapECfg['attr'][$this->attr_prefix . '-rq'] = json_encode($this->rq_data, JSON_FORCE_OBJECT);
        }
        $eWrap = new \Verba\Html\Div($wrapECfg);

        // Если UI передан как Объект
        if (is_object($this->ui) && $this->ui instanceof \Verba\Html\Element) {

            $uiHtml = $this->ui->parse();

            // Если задекларирован шаблон с кодом ui - парсим его
        } elseif ($this->tpl->isDefined('ui')) {

            $uiHtml = $this->tpl->parse(false, 'ui');

        } elseif ($this->ui === null || is_array($this->ui)) {

            $cfg = array(
                'id' => $this->eid . '_trigger',
                'attr' => array(
                    'data-role' => 'ui-tool-trigger'
                )
            );
            if (is_array($this->ui)) {
                $cfg = array_replace_recursive($cfg, $this->ui);
            }
            $triggerE = new \Html\Button($cfg);

            $uiHtml = $triggerE->parse();
        }

        // tool internak HTML
        if (isset($uiHtml) && is_string($uiHtml)) {
            $eWrap->setValue($uiHtml);
        }

        $this->tpl->assign(array(
            'WRAPPED_ELEMENT' => $eWrap->parse(),
            'TOOL_CLASS_NAME' => is_string($this->component) && !empty($this->component) ? $this->component : 'Base',
        ));

        $this->content = $this->tpl->parse(false, 'content');

        return $this->content;
    }

}
