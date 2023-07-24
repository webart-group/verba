<?php

class page_htmlIncludesCore extends \Verba\Block\Html
{
    function init()
    {
        $this->addScripts(array(
            array('jquery-3.4.0.min', 'jquery'),
            array('jquery.inherit.3.4.4 jquery.cookie', 'jquery/plugins'),
            array('php base punycode lang', 'common'),
        ), 1000);
    }
}
