<?php

namespace Verba\Mod\Links\Block;

class Link extends \Verba\Block\Json
{

    public $lcfg = '';

    function build()
    {

        $mLinks = \Verba\_mod('links');

        $this->content = $mLinks->link($this->rq, $this->lcfg);

        return $this->content;
    }
}
