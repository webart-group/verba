<?php

class page_htmlIncludesCore extends \Verba\Block\Html
{
    function init()
    {

        $this->addCss(array(
            ['bootstrap.min', 'bootstrap/css'],
            ['jquery.fancybox', '/js/jquery/plugins/fancybox'],
        ), 1000);

        $this->addScripts(array(
            array('jquery-3.4.0.min', 'jquery'),
            array('jquery.inherit.3.4.4 jquery.cookie', 'jquery/plugins'),
            array('bootstrap.bundle.min', 'bootstrap/js'),
            array('php base punycode lang', 'common'),
        ), 1000);
    }
}
