<?php

namespace Verba\Html;

class Button extends Element
{

    public $tag = 'button';
    public $type = 'button'; // button | reset | submit
    public $formaction;
    public $formmethod;
    public $formnovalidate;
    public $formenctype;
    public $form;
    public $formtarget;

    public $confirm;

    function prepareEAttrs()
    {

        $ia = parent::prepareEAttrs();
        $ia['formaction'] = $this->makeAttr('formaction', $this->formaction);
        $ia['formmethod'] = $this->makeAttr('formmethod', $this->formmethod);
        $ia['formnovalidate'] = $this->makeAttr('formnovalidate', $this->formnovalidate);
        $ia['form'] = $this->makeAttr('form', $this->form);
        $ia['formtarget'] = $this->makeAttr('formtarget', $this->formtarget);

        return $ia;
    }

    function makeE()
    {
        $this->fire('makeE');

        if (is_string($this->confirm)) {
            $this->makeConfirmPopup();
        }

        $this->setE("<" . $this->tag . $this->prepareEAttrsImploded() . ">" . $this->getValue() . "</" . $this->tag . ">");
        $this->fire('makeEFinalize');
    }

    function makeConfirmPopup()
    {
        $this->setEvents('click', "return confirm('" . htmlentities($this->confirm) . "')");
    }
}
