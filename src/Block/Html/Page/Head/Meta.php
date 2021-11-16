<?php

namespace Verba\Block\Html\Page\Head;

class Meta extends \Verba\Block\Html
{

    public $last_item_key = false;
    public $finalizedMeta = array();
    public $itemsMeta;
    public $item_key;
    public $item;
    public $metaKey;
    public $join_method;

    function build()
    {
        $r = array();
        $mMenu = \Verba\_mod('menu');
        $mMeta = \Verba\_mod('meta');

        $page = $this->getBlockByRole('HtmlHead');
        if (!$page) {
            return;
        }

        // clear current Page meta properties
        foreach ($mMeta->metaKeys as $mk) {
            $page->clearMeta($mk);
        }

        $mMenu->getChain();
        $this->itemsMeta = $mMeta->loadObjectMeta($mMenu->chain_nodes);

        if (!is_array($this->itemsMeta) || empty($this->itemsMeta)) {
            return false;
        }
        $connector = $mMeta->gC('connector');

        $tconn = array();
        foreach ($this->itemsMeta as $this->item_key => $this->item) {
            if ((isset($this->item['hidden']) && $this->item['hidden'] == 1)
                || (isset($this->item['active']) && $this->item['active'] == 0)) {
                continue;
            }
            foreach ($mMeta->metaKeys as $this->metaKey) {

                if (isset($this->finalizedMeta[$this->metaKey])
                    && $this->finalizedMeta[$this->metaKey] == true) {
                    continue; // skip for that item meta if flag is turnedON by some handler
                }

                if (!isset($tconn[$this->metaKey])) {
                    $tconn[$this->metaKey] = $connector;
                }
                if (isset($this->item[$this->metaKey])
                    && is_array($this->item[$this->metaKey])) {
                    $meta = &$this->item[$this->metaKey];
                } else {
                    $meta = &$this->item;
                }
                $this->join_method = $meta['insert'];


                if (isset($meta['rules']) && !empty($meta['rules'])) {
                    $rules = explode("\n", $meta['rules']);
                    foreach ($rules as $rule) {

                        $rule_arr = explode("@", $rule);
                        $rule = $rule_arr[0];

                        $rule_cfg = isset($rule_arr[1]) && is_string($rule_arr[1]) && !empty($rule_arr[1])
                            ? json_decode($rule_arr[1], true)
                            : array();


                        switch (trim($rule)) {

                            case 'clear':
                                $r[$this->metaKey] = '';
                                break;


                            case 'skip':
                                $meta[$this->metaKey] = '';
                                break;

                            case 'catalogTemplate':
                                $rule_cfg['type'] = 'block';
                                $rule_cfg['name'] = 'catalog_metaTemplate';
                            case 'handler':
                                if (!$rule_cfg) {
                                    continue 2;
                                }
                                switch ($rule_cfg['type']) {
                                    case 'block':
                                    default:
                                        $blockClassName = $rule_cfg['name'];
                                        $blockCfg = isset($rule_cfg['cfg']) && is_array($rule_cfg['cfg'])
                                            ? $rule_cfg['cfg']
                                            : array();
                                        $blockCfg['node_meta'] = $meta;
                                        $block = new $blockClassName($this, $blockCfg);
                                        $block->prepare();
                                        $block->build();
                                        $meta[$this->metaKey] = $block->getContent();
                                }
                                break;
                        }
                    }
                }

                if (empty($meta[$this->metaKey])) {
                    continue;
                }

                if (isset($meta['connector']) && is_string($meta['connector']) && !empty($meta['connector'])) {
                    $conn = $meta['connector'];
                    $tconn[$this->metaKey] = $meta['connector'];
                } elseif (isset($tconn[$this->metaKey]) && is_string($tconn[$this->metaKey]) && !empty($tconn[$this->metaKey])) {
                    $conn = $tconn[$this->metaKey];
                } else {
                    $conn = $connector;
                }
                $value = $meta[$this->metaKey];
                if (!isset($r[$this->metaKey])) {
                    $r[$this->metaKey] = '';
                }
                switch ($this->join_method) {
                    case 'after':
                        $action = 'append';
                        break;
                    case 'overwrite':
                        $action = 'set';
                        break;
                    case 'before':
                    case null:
                    default:
                        $action = 'prepend';
                        break;
                }
                $r[$this->metaKey] = $page->{$action . 'Meta'}($this->metaKey, $value, $conn, $r);
            }// end all metakeys for current chain node

            if (isset($this->last_item_key) && $this->last_item_key == $this->item_key) {
                break; // finish generate chain at this item
            }
        }

        if (empty($r)) {
            return;
        }

        foreach ($r as $metaKey => $metaValue) {
            if (empty($metaValue)) {
                continue;
            }
            $page->setMeta($metaKey, $metaValue);
        }
    }

}
