<?php

class page_htmlIncludesForm extends \Verba\Block\Html
{
    function init()
    {
        $this->addCss(array(
            array('jquery-ui', '/js/jquery/ui/theme/pepper-grinder'),
            array('jquery-ui-timepicker-addon', '/js/jquery/timepicker-addon'),
            array('select2.min', '/js/jquery/select2/css'),
            array('form'),
            array('picupload', 'form/fe'),
        ), 900);

        $this->addScripts(array(
            array('jquery-ui.min', 'jquery/ui'),
            array('jquery.ui.datepicker-ru', 'jquery/ui'),
            array('jquery-ui-timepicker-addon', 'jquery/timepicker-addon'),
            array('select2.min', 'jquery/select2/js'),
            array('form formValidator', 'form'),
            array('multi-parent-selector', 'form/e'),
            array('picupload', 'form/fe'),
        ), 900);
    }
}
