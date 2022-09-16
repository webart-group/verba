<?php
/**
 * @author webart.group
 * @author Кудрявцев Максим (Kudriavtsev Maksym), <kmv@webart.group>
 * @copyright See copyright.md
 * Date: 14.09.19
 * Time: 19:33
 */
namespace Verba\Mod\Acp\Block\Product;

class Unlink extends \Verba\Block\Html
{
    public $method;
    public $ot_id;
    public $ots = array();


    function build()
    {
        $this->content = 1;
        $_prod = \Verba\_oh($this->request->ot_id);
        $iid = $this->request->iid;
        list($pot, $piid) = $this->request->getFirstParent();
        $_parent = \Verba\_oh($pot);
        $ruleAlias = $this->request->getParam('rule') || false;

        $r = $_parent->unlink($piid, array($_prod->getID() => array($iid)), $ruleAlias);
        return $this->content;
    }

}