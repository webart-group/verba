<?php
/**
 * @author webart.group
 * @author Кудрявцев Максим (Kudriavtsev Maksym), <kmv@webart.group>
 * @copyright See copyright.md
 * Date: 16.09.19
 * Time: 12:03
 */

namespace Mod\Otype\Block\ACP\API\Attribute\Form;

class Inside extends \Verba\Block\Json
{
    public $attr;

    function route()
    {
        $className = __NAMESPACE__.'\\Inside\\'.ucfirst($this->rq->node);
        if (!class_exists($className)) {
            $this->log()->warning('Acp otype form attr inside element not found: '.var_export($className));
            $className = __NAMESPACE__.'\\Inside\\Base';
        }

        $iid = $this->rq->iid;
        if (!$iid && $this->rq->getParam('attr_iid')) {
            $iid = (int)$this->rq->getParam('attr_iid');
        }
        if (!$iid) {
            $iid = false;
        }

        $_attr = \Verba\_oh('ot_attribute');
        if ($iid) {
            $this->attr = $_attr->getData($iid, 1);
            if (!$this->attr) {
                throw new \Exception\Routing('Unable to obtain attr data');
            }
        }
        /**
         * @var $h \Block\Html
         *
         */
        $h = new $className($this->rq->shift());

        $h = $h->route();

        $this->addItems($h);

        return $this;
    }

    function build(){
        $this->content = $this->items[0]->content;
        return $this->content;
    }
}