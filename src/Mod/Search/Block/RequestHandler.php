<?php

namespace Verba\Mod\Search\Block;

class RequestHandler extends \Verba\Block\Html
{
    function build()
    {
        $mSrc = \Verba\_mod('search');

        try {
            $this->content = $mSrc->handleQuery($_POST['q']);
        } catch (\Exception $e) {
            $this->content = $e->getMessage();
        }

        return $this->content;
    }
}
