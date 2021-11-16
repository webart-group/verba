<?php
namespace Verba;

class Tree extends Configurable implements TreeInterface
{
    /**
     * @var Model
     */
    private $oh;
    /**
     * @var mixed $aot Массив разрешенных ОТ искомых объектов
     */
    private $aot;
    /**
     * @var mixed $start_iids ID корневого объекта дерева (массив ID корневых объектов)
     */
    private $start_iids = false;
    /**
     * @var string $level Глубина получения дерева объектов
     */
    private $level;
    private $branch;
    protected $pair;
    private $nodes_data;
    protected $includeStartNodes = true;
    private $rights;

    protected $nodes;

    protected $nodeTypes;
    protected $levelsCfg = array();

    function __construct($oh, $start_iids = false, $level = 2, $aot = false, $rights = false)
    {
        $this->setOh($oh);
        $this->setRights($rights);
        $this->checkAccess();
        $this->setStartIids($start_iids);
        $this->setlevel($level);
        $this->setAot($aot);
    }

    function setOh($oh)
    {
        $this->oh = \Verba\_oh($oh);
        if (!$this->oh) {
            throw new Exception('bad otype');
        }
        return true;
    }

    function getOh()
    {
        return $this->oh;
    }

    function setAot($aot)
    {
        $this->aot = !\Verba\convertToIdList($aot) ? array($this->oh->getID()) : $aot;
        return true;
    }

    function setStartIids($start_iids)
    {
        if (\Verba\convertToIdList($start_iids)) {
            $this->start_iids = $start_iids;
            return true;
        }
        return false;
    }

    function setLevel($value)
    {
        $this->level = !($value = intval($value)) ? 1 : $value;
        return true;
    }

    function getBranch()
    {
        if ($this->branch === null) {
            $this->loadBranch();
        }
        return $this->branch;
    }

    function loadBranch()
    {
        if (!is_array($this->start_iids)) {
            return ($this->branch = false);
        }

        $direction = $this->level < 0 ? 'up' : 'down';

        $this->branch = \Verba\Branch::get_branch(
            array($this->oh->getID() => array('iids' => $this->start_iids, 'aot' => $this->aot)),
            $direction,
            abs($this->level));

        if (is_array($this->branch['pare']) && count($this->branch['pare']) && array_key_exists($this->oh->getID(), $this->branch['pare'])) {
            $this->pair = &$this->branch['pare'];
        } else {
            $this->pair = false;
        }

        return true;
    }

    function loadNodesData($all_langs = false)
    {
        if (!isset($this->branch['handled']) || !count($this->branch['handled'])) {
            return ($this->nodes_data = false);
        }
        $this->nodes_data = array();
        foreach ($this->branch['handled'] as $ot_id => $iids) {
            $oh = \Verba\_oh($ot_id);
            $this->nodes_data[$ot_id] = $oh->getData($iids, true, true, false, $all_langs);
        }
        return true;
    }

    function getNodesData($all_langs = false)
    {
        if ($this->nodes_data === null) {
            $this->loadNodesData($all_langs);
        }
        return $this->nodes_data;
    }

    function setNodesData($data)
    {
        if (!is_array($data) || !count($data)) {
            return false;
        }
        $this->nodes_data = $data;
        return true;
    }

    function getNodeData($iid, $ot_id = false)
    {
        if (!$ot_id) {
            $ot_id = $this->oh->getID();
        }
        return array_key_exists($ot_id, $this->nodes_data) && array_key_exists($iid, $this->nodes_data[$ot_id])
            ? $this->nodes_data[$ot_id][$iid]
            : $this->nodes_data[$ot_id][$iid];
    }

    function getPair()
    {
        return is_array($this->branch) && array_key_exists('pare', $this->branch)
            ? $this->branch['pare']
            : null;
    }

    function setNodeTypes($nodeTypes)
    {
        $this->nodeTypes = $nodeTypes;
    }

    function getTrunk()
    {
        return $this;
    }

    function getLevel()
    {
        return null;
    }

    function getNodeClassName($ot)
    {
        $oh = \Verba\_oh($ot);
        if (!is_array($this->nodeTypes)
            || !array_key_exists($oh->getCode(), $this->nodeTypes)
        ) {
            return array('TreeNode');
        }

        if (is_string($this->nodeTypes[$oh->getCode()])) {
            return array($this->nodeTypes[$oh->getCode()]);
        }
        if (is_array($this->nodeTypes[$oh->getCode()])) {
            return $this->nodeTypes[$oh->getCode()];
        }
        return array(false, false);
    }

    function getSubnodesIids($ot_id = false, $iid = false)
    {
        if (!$ot_id) {
            if (!is_array($this->branch)
                || !array_key_exists('root_nodes', $this->branch)
                || !is_array($this->branch['root_nodes'])
            ) {
                return false;
            }

            return $this->branch['root_nodes'];
        }

        if (!is_array($this->pair)
            || !array_key_exists($ot_id, $this->pair)
            || !array_key_exists($iid, $this->pair[$ot_id])
            || !is_array($this->pair[$ot_id][$iid])
            || !count($this->pair[$ot_id][$iid])
        ) {
            return false;
        }

        return $this->pair[$ot_id][$iid];
    }

    function buildNodesTree()
    {

        if ($this->branch === null) {
            $this->loadBranch();
            $this->loadNodesData();
        }
        $Node = new Tree\Node($this, false);
        $Node->initSubnodes();
        $this->nodes = current($Node->getNodes());

        return $this->nodes;
    }

    /**
     * Произвольные конфиги для узлов разных уровней
     * Вид array(
     *  'default' => array(...) - конфиг для ноды по умолчанию
     *  1 => array( ... ) - конфиг для ноды первого уровня
     *  2 => ...
     * )
     * @param $levelsCfg
     * @return array|bool
     */

    function setLevelsCfg($levelsCfg)
    {
        if (!is_array($levelsCfg) || !count($levelsCfg)) {
            return false;
        }
        $this->levelsCfg = array('default' => array());
        if (array_key_exists('default', $levelsCfg) && is_array($levelsCfg['default'])) {
            $this->levelsCfg['default'] = $levelsCfg['default'];
            unset($levelsCfg['default']);
        }

        if (count($levelsCfg)) {
            foreach ($levelsCfg as $lvlIndex => $cLvlCfg) {
                if (!is_numeric($lvlIndex) || !is_array($cLvlCfg) || !count($cLvlCfg)) {
                    continue;
                }

                $this->levelsCfg[(int)$lvlIndex] = count($this->levelsCfg['default'])
                    ? array_replace_recursive($this->levelsCfg['default'], $cLvlCfg)
                    : $cLvlCfg;
            }
        }
        return $this->levelsCfg;
    }

    function getLevelCfg($lvl)
    {
        $lvl = (int)$lvl;

        if (!array_key_exists($lvl, $this->levelsCfg)) {
            $this->levelsCfg[$lvl] = &$this->levelsCfg['default'];
        }
        return $this->levelsCfg[$lvl];
    }

    function setRights($rights)
    {
        if (!$rights) {
            $this->rights = (bool)$rights;
        } elseif (\Verba\reductionToArray($rights)) {
            $this->rights = $rights;
        } else {
            return false;
        }
        return true;
    }

    function checkAccess()
    {
        global $S;

        if (!$this->rights) {
            return true;
        }
        $access = true;
        if (is_array($this->rights) && count($this->rights)) {
            foreach ($this->rights as $key => $rights) {
                if (!$S->U()->chr($key, $rights)) {
                    $access = false;
                }
            }
        }
        if (!$access) {
            $this->branch = false;
            $this->nodes_data = false;
            return false;
        }
        return true;
    }
}
