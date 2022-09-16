<?php
namespace Tree\Node;

class View extends \Tree\Node
{

    use \Verba\Block\Html\Element\Attribute\CssClass;

    public $skipBody = false;
    public $skipBodyClass = false;

    public $parse_attrs = array();
    public $tpl_vars = array();


    public $templates = array(
        'node' => 'tree/node/node.tpl',
        'body' => 'tree/node/body.tpl',
        'body_no_link' => '/tree/node/body-no-link.tpl',
        'items' => 'tree/node/items.tpl',
    );
    /**
     * @var \Verba\FastTemplate
     */
    protected $tpl;

    public $tplSharedKey = 'tree_node_view';

    function init()
    {
        $this->tpl();
    }

    function getTemplates()
    {
        return $this->templates;
    }

    function tpl()
    {
        if ($this->tpl === null) {

            $this->tpl = \Verba\FastTemplate::getShared($this->tplSharedKey, array(
                'templates' => $this->templates,
            ));
        }

        return $this->tpl;
    }

    function prepare()
    {

    }

    function parse()
    {

        $this->prepare();

        $this->addCssClass(array(
            'ot-' . $this->oh->getCode(),
            'lvl-' . $this->level,
            'i-' . $this->iid,
        ));

        $this->tpl->assign('NODE_ITEMS_BLOCK', $this->parseSubitemsBlock());

        $this->tpl->assign('NODE_BODY', $this->parseBody());

        // parse Node
        $this->tpl->assign(array(
            'NODE_CLASSES' => ' ' . $this->implodeCssClass(),
        ));

        return $this->tpl->parse(false, 'node');
    }

    function isParseBody()
    {
        if ($this->skipBody) {
            return false;
        }
        return true;
    }

    function getBodyTplAlias()
    {
        return 'body';
    }

    function parseBody()
    {
        // Если текущий уровень не пропускается
        // - парсинг кнопки ноды
        if (!$this->isParseBody()) {
            $this->addCssClass('no-body');
            return '';
        }

        $tplAlias = $this->getBodyTplAlias();
        // генерация значений шаблонных переменных для ноды из заявленных атрибутов
        if (is_array($this->parse_attrs) && count($this->parse_attrs)) {
            foreach ($this->parse_attrs as $attr_code) {
                $this->tpl->assign(array(
                    'ITEM_' . strtoupper($attr_code) => array_key_exists($attr_code, $this->item)
                        ? (string)$this->item[$attr_code]
                        : '',
                ));
            }
        }

        if (is_array($this->tpl_vars) && count($this->tpl_vars)) {
            foreach ($this->tpl_vars as $tpl_var_code => $tpl_var_value) {
                $this->tpl->assign(array(
                    'ITEM_' . strtoupper($tpl_var_code) => $tpl_var_value
                ));
            }
        }

        return $this->tpl->parse(false, $tplAlias);
    }

    function parseSubitemsBlock()
    {
        // Парсинг нижних нод
        if (!is_array($this->nodes) || !count($this->nodes)) {
            return '';
        }

        $this->addCssClass('haveSubitems');

        $this->tpl->PARSEVARS['NODE_ITEMS'] = '';

        /**
         * @var $Node View
         */
        foreach ($this->nodes as $nodeId => $Node) {
            $this->tpl->PARSEVARS['NODE_ITEMS'] .= $Node->parse();
        }

        return $this->tpl->parse(false, 'items');
    }
}
