<?php

namespace Verba\Mod\Links\Block\ACP;

use Verba\Mod\Links\Block\Load;

class ActionsAdapter extends \Verba\Block\Html
{
    function build()
    {
        $gen = $this->getItem();
        if (!$gen instanceof \Verba\BlockInterface) {
            throw  new \Verba\Exception\Building();
        }

        if ($gen->rq->action == 'create') {
            if (isset($gen->content[0]) && $gen->content[0] == 1) {
                $prim = $gen->rq->getTempData('primary');
                $sec = $gen->rq->getTempData('secondary');
                if (is_array($prim) && !empty($prim)
                    && is_array($sec) && !empty($sec)) {
                    reset($prim);
                    reset(current($prim));
                    reset($sec);
                    reset(current($sec));

                    $itemsLoader = new Load($this, array(
                        'gid' => array(
                            key($prim),
                            current(current($prim)),
                            key($sec),
                            current(current($sec)),
                        ),
                        'lcfg' => $gen->lcfg
                    ));
                    $itemsLoader->prepare();
                    $r = $itemsLoader->build();
                    if (is_array($r) && count($r)) {
                        reset($r);
                        $content = array('items' => $r);
                    }
                }
            }
        }

        if (isset($content)) {
            $this->content = $content;
        } else {
            $this->content = $gen->content;
        }

        return $this->content;
    }
}

