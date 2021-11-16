<?php

namespace Verba\Act\Form\Element;

class Dateselector extends Datetimeselector
{
    protected $showHM = false;
    public $classes = array('date-input');

    function setShowHM($val)
    {
        return;
    }
}
