<?php

namespace Verba\Act\Form\Element\Extension;

use \Verba\Act\Form\Element\Extension;
use \Verba\Act\Form\Element\Logic;

class AddPlaceholder extends Extension
{
    public $templates = array(
        'placeholderEffectJsInit' => '/aef/exts/addPlaceholder/placeholderEffectJsInit.tpl',
    );
    public $lang_root = false;
    public $placeholder;
    public $labelForced = false;

    function engage()
    {

        if ($this->fe instanceof Logic) {
            return;
        }

        $this->fe->listen('makeE', 'parsePlaceholder', $this);
        $this->fe->listen('makeEFinalize', 'addLabel', $this);
        if (!$this->fe->ah->isListen('particlesAssembled', 'addPlaceholderInitJsHook')) {
            $this->fe->ah->listen('particlesAssembled', 'addJsInit', $this, 'addPlaceholderInitJsHook');
        }
    }

    function generatePlaceholder()
    {

        $placeholder = $this->fe->attr('placeholder');

        if (is_string($placeholder)) {
            // если у элемента есть аттрибут плейсхолдер, то скопировать его,
            // удалить базовый аттр тэга
            $this->fe->removeAttr('placeholder');

        } else {
            if (is_string($this->lang_root)) {
                $lang_root = $this->lang_root . ' ' . $this->fe->acode;
                $v = \Verba\Lang::get($lang_root);
                if (!is_string($v) && is_int($baseOt = $this->fe->oh->getBaseId())) {
                    $_base = \Verba\_oh($baseOt);
                    $lang_root = $this->lang_root . ' ' . $_base->getCode() . ' ' . $this->fe->acode;
                    $v = \Verba\Lang::get($lang_root);
                }

                if (is_string($v)) {
                    $placeholder = $v;
                }
            }
        }

        if (!is_string($placeholder) && is_object($this->fe->A())) {
            $placeholder = $this->fe->getDisplayName();
        }

        if (!is_string($placeholder)) {
            $placeholder = $this->fe->acode;
        }
        return $placeholder;
    }

    function parsePlaceholder()
    {

        $this->fire('beforePlaceholder');

        if (is_string($this->placeholder)) {
            goto PLACEHOLDER_KNOWN;
        }

        $this->placeholder = $this->generatePlaceholder();

//    $this->fe->attr('placeholder', $this->placeholder);
//    $this->fe->attr('data-placeholder', $this->placeholder);

        PLACEHOLDER_KNOWN:

        // добавляем пустое значение в селект если применимо
        if ($this->fe->getTag() == 'select'
            && !$this->fe->getMultiple()
            && !$this->fe->getObligatory()
            && !$this->fe->haveClass('required')
        ) {
            $this->fe->setBlankoption('', '', array('class' => 'blop'));
        }

    }

    function addLabel()
    {

        $this->fe->E = $this->fe->E . '<label class="plh-cnt" data-content="' . $this->placeholder . '"></label>';

    }

    function addJsInit()
    {
        $this->tpl->define($this->templates);
        $this->tpl->assign(array(
            'PLH_FORM_ID' => $this->ah->getFormWrapId(),
        ));

        $this->ah->addJsAfter($this->tpl->parse(false, 'placeholderEffectJsInit'));
    }

}