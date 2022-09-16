<?php
/**
 * @author webart.group
 * @author Кудрявцев Максим (Kudriavtsev Maksym), <kmv@webart.group>
 * @copyright See copyright.md
 * Date: 14.09.19
 * Time: 18:09
 */
namespace Verba\Mod\Acp\Block\Tool;

class Tab extends \Verba\Block\Json {

    function build(){

        $this->content = '';

        $class = '\Mod\Game\blocks\acp\tools\bids\refresh';
        if(class_exists($class)){
            $bdr = new $class($this);
            $e = $bdr->getToolE();
            $e->prepare();
            $e->build();
            $this->content .= $e->getContent();
        }

        return $this->content;
    }
}