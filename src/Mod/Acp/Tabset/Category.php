<?php

namespace Verba\Mod\Acp\Tabset;


class Category extends \Verba\Mod\Acp\Tabset
{
    function tabs()
    {
        $itemsOt = isset($this->node->itemData['itemsType']) && !empty($this->node->itemData['itemsType'])
            ? $this->node->itemData['itemsType']
            : false;

        $itemsTab = array();

        if ($itemsOt) {
            $itemsTab['CategoryItems'] = array('ot' => $itemsOt);
        }

        $commonTabs = array(
            'CategoryAef',
            'MetaAef' => array('linkedTo' => array('type' => 'node'))
        );

        return array_merge($itemsTab, $commonTabs);
    }
}
