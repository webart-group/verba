<?php

namespace Verba\Mod\Catalog;

use Verba\Mod\Catalog\MapTransformer;

class Map extends \Verba\Block\Json
{

    public $rootId = 1;
    public $menuTree;

    public $groups_count = 0;
    public $items_count = 0;

    private $br;
    private $data;

    function build()
    {
        $this->content = $this->getMenuTree();
        return $this->content;
    }

    function getMenuTree()
    {
        if ($this->menuTree !== null) {
            return $this->menuTree;
        }
        $this->menuTree = [];
        $_menu = \Verba\_oh('menu');
        $_cat = \Verba\_oh('catalog');
        $m_ot_id = $_menu->getID();
        $c_ot_id = $_cat->getID();

        $this->br = \Verba\Branch::get_branch([
            $c_ot_id => [
                'aot' => [
                    $c_ot_id
                ],
                'iids' => $this->rootId
            ]], 'down', 4, true, false
        );
        if (!$this->br['handled'][$c_ot_id]) {
            return $this->menuTree;
        }
        //$this->data[$m_ot_id] = $_menu->getData($this->br['handled'][$m_ot_id]);
        $this->data[$c_ot_id] = $_cat->getData($this->br['handled'][$c_ot_id]);
        $this->buildTree();

        return $this->menuTree;
    }

    function buildTree()
    {
        $_menu = \Verba\_oh('menu');
        $_cat = \Verba\_oh('catalog');
        $m_ot_id = $_menu->getID();
        $c_ot_id = $_cat->getID();
        foreach ($this->br['pare'][$c_ot_id][$this->rootId] as $lot => $liids) {
            $this->handleNode($lot, $liids, $this->menuTree);
        }
    }

    function handleNode($ot, $iids, &$pointTo, $parent = false, $url_prefix = false)
    {
        $CatalogTransformer = new MapTransformer();

        $_menu = \Verba\_oh('menu');
        $_cat = \Verba\_oh('catalog');
        $m_ot_id = $_menu->getID();
        $c_ot_id = $_cat->getID();
        $this->sortOt = $ot;
        usort($iids, array($this, 'sort'));
        foreach ($iids as $iid) {
            $item = $this->data[$ot][$iid];

            if (isset($item['url'])) {
                $url = $item['url'];
            } elseif (isset($item['code'])) {
                $this->items_count++;
                $url = $url_prefix . '/' . $item['code'];
            } else {
                $url = $url_prefix;
            }

            $item['url'] = $url;
            $item['items'] = array();
            if (isset($this->br['pare'][$ot][$iid]) && is_array($this->br['pare'][$ot][$iid]) && count($this->br['pare'][$ot][$iid])) {
                foreach ($this->br['pare'][$ot][$iid] as $not => $niids) {
                    $this->groups_count++;
                    $this->handleNode($not, $niids, $item['items'], $item, $url);
                }
            }
            if ($item['ot_id'] == $m_ot_id) {
                $item['_groups'] = $this->groups_count - 1;
                $item['_items'] = $this->items_count - $item['_groups'] - 1;
                $this->items_count = 0;
                $this->groups_count = 0;
            }
            $pointTo[$iid] = $CatalogTransformer->transform($item);
        }
    }

    function sort($a, $b)
    {
        $ap = (int)$this->data[$this->sortOt][$a]['priority'];
        $bp = (int)$this->data[$this->sortOt][$b]['priority'];
        if ($ap == $bp) {
            return 0;
        }
        return ($ap > $bp) ? -1 : +1;
    }
}
