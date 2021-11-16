<?php
namespace Tree;

class Node extends \Verba\Configurable
{
    public $item;
    /**
     * @var \Verba\Model
     */
    public $oh;
    public $ot_id;
    public $iid;
    public $nodes;

    /**
     * @var Node
     */
    protected $parent;
    public $level = 0;

    /**
     * @var \Tree
     */
    protected $Trunk;

    function __construct($parent, $ot = false, $iid = false, $cfg = false)
    {
        $this->parent = $parent;
        $this->Trunk = $this->parent->getTrunk();
        $parentLevel = $this->parent->getLevel();
        $this->level = !is_int($parentLevel) ? 0 : $parentLevel + 1;
        if ($ot && $iid) {
            $this->item = $this->Trunk->getNodeData($iid, $ot);
            if (is_array($this->item)) {
                $this->oh = \Verba\_oh($this->item['ot_id']);
                $this->ot_id = $this->oh->getID();
                $this->iid = $this->item[$this->oh->getPAC()];
            }
        }
        $this->applyConfigDirect($this->Trunk->getLevelCfg($this->level));
        $this->applyConfigDirect($cfg);

        $this->init();

    }

    function init()
    {

    }

    function getLevel()
    {
        return $this->level;
    }

    function setLevel($val)
    {
        $this->level = (int)$val;
    }

    function initSubnodes()
    {
        $subnodes = $this->Trunk->getSubnodesIids($this->ot_id, $this->iid);
        if (!is_array($subnodes)) {
            return null;
        }

        foreach ($subnodes as $c_ot => $iids) {
            list($className, $cfg) = $this->Trunk->getNodeClassName($c_ot);
            if (!$className || !class_exists($className)) {
                throw new \Exception('Unknown class `'.var_export($className, true).'` for tree node');
            }
            foreach ($iids as $iid) {
                $nodeId = $c_ot . '_' . $iid;
                $this->nodes[$nodeId] = new $className($this, $c_ot, $iid, $cfg);
                $this->nodes[$nodeId]->initSubnodes();
            }
        }

    }

    function getTrunk()
    {
        return $this->Trunk;
    }

    function getNodes()
    {
        return $this->nodes;
    }
}
