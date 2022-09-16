<?php
namespace Verba\Mod\Routine\Block;

class MakeList extends \Verba\Block\Html
{

    public $otype;

    public $contentType = 'html';
    /**
     * @var string
     */
    public $cfg;
    /**
     * @var array
     */
    public $dcfg = [];
    /**
     * @var \Verba\Act\MakeList
     */
    public $list;

    public $listId;

    protected $where = array();

    protected $parseHtml = true;

    function init()
    {

        if (!$this->otype && $this->oh()) {
            $this->otype = $this->oh->getCode();
        }

        if ($this->otype && !$this->oh()) {
            $this->oh = \Verba\_oh($this->otype);
            $this->rq->setOt($this->oh->getID());
        }
    }

    function prepare()
    {
        if (!$this->otype
            || !$this->oh()
            || $this->otype != $this->oh->getCode()
        ) {
            throw  new \Verba\Exception\Building('Unknown list content');
        }

        if (!is_object($this->list) || !$this->list instanceof \Verba\Act\MakeList) {

            $cfg = $this->request->asArray();
            $cfg['block'] = $this;

            if (is_string($this->cfg) && !empty($this->cfg)) {
                $cfg['cfg'] = $this->cfg;
            }

            if (is_string($this->listId) && !empty($this->listId)) {
                $this->dcfg['listId'] = $this->listId;
            }

            if (is_array($this->dcfg) && !empty($this->dcfg)) {
                if (!isset($cfg['dcfg']) || !is_array($cfg['dcfg'])) {
                    $cfg['dcfg'] = array();
                }
                $cfg['dcfg'] = array_replace_recursive($cfg['dcfg'], $this->dcfg);
            }

            $this->list = $this->oh->initList($cfg);

        } else {
            $this->list->setBlock($this);
        }

    }

    function setWhere($arr)
    {
        if ($arr === false) {
            $this->where = array();
            return $this->where;
        }
        if (!is_array($arr)) {
            return false;
        }

        $this->where = $arr;
        return $this->where;
    }

    function addWhere($arr)
    {
        if (!is_array($arr)) {
            return false;
        }

        $this->where = array_replace_recursive($this->where, $arr);
        return $this->where;
    }

    function getWhere()
    {
        return $this->where;
    }

    function build()
    {

        $this->content = false;
        if (!is_object($this->list) || !$this->list instanceof \Verba\Act\MakeList) {
            throw   new \Verba\Exception\Building('No valid list');
        }

        if (is_array($this->where) && count($this->where)) {
            $QM = $this->list->QM();
            foreach ($this->where as $fieldName => $fValue) {
                $where_alias = 'ral_where_' . $fieldName;
                $QM->removeWhere($where_alias);
                if ($fValue === false) {
                    continue;
                }
                $QM->addWhere($fValue, $where_alias, $fieldName);
            }
        }

        $qm = $this->list->QM();

        $this->content = $this->list->generateList($this->getParseHtml());

        if($this->content === true) {
            $this->content = '';
        }

        $q = $qm->getQuery();
        return $this->content;
    }

    function setParseHtml($val)
    {
        $this->parseHtml = (bool)$val;
        return $this->parseHtml;
    }

    function getParseHtml()
    {
        return $this->parseHtml;
    }
}
