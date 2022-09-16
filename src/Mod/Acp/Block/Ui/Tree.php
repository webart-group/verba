<?php

namespace Verba\Mod\Acp\Block\Ui;

class Tree extends \Verba\Block\Json
{

    function build()
    {
        $this->content = false;
        /**
         * @var $acp \Verba\Mod\ACP
         */
        $acp = \Verba\_mod('acp');

        $cfg = $acp->gC('tree');

        if (!is_array($cfg)) {
            return $this->content;
        }
        if (isset($cfg['acpNodeType'])
            && class_exists(($nodeClass = '\Verba\Mod\Acp\Node\\' . strtolower($cfg['acpNodeType'])), false)) {
        } else {
            $nodeClass = '\Verba\Mod\Acp\Node';
        }

        $node = new $nodeClass(null, null, $cfg);
        $nodeRoot = $_REQUEST['nodeId'];
        if (isset($_REQUEST['nodeId']) && is_string($_REQUEST['nodeId']) && !empty($_REQUEST['nodeId'])
            && is_array($nodeChain = explode('.', $_REQUEST['nodeId']))) {
            $cNode = $node;
            $cNodeId = array_shift($nodeChain);
            while ($cNodeId) {
                $cNodePretender = $cNode->getItem($cNodeId);
                $cNode = $cNodePretender;
                if (!$cNode) {
                    break;
                }
                $cNodeId = array_shift($nodeChain);
            }
            if ($cNode) {
                $node = $cNode;
            } else {
                $node = false;
            }
        }
        if (!$node) {
            throw  new \Verba\Exception\Building('Node \'' . $nodeRoot . '\' not found');
        }
        $items = $node->getItems();

        $this->content = [];

        if (is_array($items)) {
            foreach ($items as $item) {
                $cNodeItems = $item->getItems();
                $item->items = null;
                if (is_array($cNodeItems) && count($cNodeItems)) {
                    $item->hasItems = true;
                }
                $idx = is_numeric($item->id) ? 'i' . $item->id : $item->id;
                $this->content[$idx] = $item->exportAsCfg();
            }
        }

        return $this->content;
    }
}
