<?php
namespace Verba\Mod\Links\Block;

class Unlink extends \Verba\Block\Json
{

    public $lcfg = '';

    function build()
    {

        $mLinks = \Verba\_mod('links');

        $this->content = $mLinks->unlink($this->rq, $this->lcfg);

        return $this->content;
    }
}
