<?php

class game_pageContent extends page_content
{
    /**
     * @var \Verba\Mod\Game\ServiceRequest
     */
    public $gsr;

    function setGsr($val)
    {
        if (!is_object($val) || !$val instanceof \Verba\Mod\Game\ServiceRequest) {
            return;
        }
        $this->gsr = $val;
    }
}
