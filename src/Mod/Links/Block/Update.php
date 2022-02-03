<?php

namespace Mod\Links\Block;

class Update extends \Verba\Block\Html
{

    public $lcfg = '';

    function build()
    {

        $mLinks = \Verba\_mod('links');

        $updRes = $mLinks->update($this->rq, $this->lcfg);
        list($primary, $secondary, $extData) = $mLinks->_lastExtractedLinkingData;

        $p_ot_id = key($primary);
        $p_iid = current($primary[$p_ot_id]);
        $ch_ot_id = key($secondary);
        $ch_iid = current($secondary[$ch_ot_id]);

        $_poh = \Verba\_oh($p_ot_id);
        $_soh = \Verba\_oh($ch_ot_id);

        $ruleAlias = $this->lcfg['rule'];
        $rule = $_poh->getRule($_soh, $ruleAlias);

        $q = "SELECT * FROM " . $rule['uri'] . "
      WHERE `p_ot_id` = '" . $this->DB()->escape($p_ot_id) . "' && `p_iid` = '" . $this->DB()->escape($p_iid) . "'
      && `ch_ot_id` = '" . $this->DB()->escape($ch_ot_id) . "' && `ch_iid` = '" . $this->DB()->escape($ch_iid) . "'
    ";

        $sqlr = $this->DB()->query($q);
        $row = is_object($sqlr) && $sqlr->getNumRows() ? $sqlr->fetchRow() : false;
        $this->content = array('item' => $row);
        return $this->content;
    }
}
