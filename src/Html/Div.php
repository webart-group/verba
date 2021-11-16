<?php

namespace Verba\Html;

class Div extends Element
{
    public $tag = 'div';

    function prepareEAttrs()
    {
        $ia = array();
        $ia['id'] = $this->makeIdTagAttr();
        $ia['attrs'] = $this->makeAttrs();
        $this->fire('addClasses');
        $this->fire('addEvents');
        $ia['classes'] = $this->makeClassesTagAttr();
        $ia['events'] = $this->makeEventsTagAttr();
        return $ia;
    }

    function makeE()
    {
        $this->fire('makeE');

        $this->setE('<' . $this->tag
            . $this->prepareEAttrsImploded()
            . '>'
            . $this->getValue()
            . '</' . $this->tag . '>');

        $this->fire('makeEFinalize');
    }
}
