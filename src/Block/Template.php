<?php
namespace Verba\Block;

trait Template
{
    public $templates = array();
    public $tplvars = array();

    /**
     * @var \Verba\FastTemplate
     */
    protected $tpl;

    function __construct($rq = null, $cfg = null)
    {
        parent::__construct($rq, $cfg);
        $this->tpl();
    }

    function tpl()
    {
        if ($this->tpl === null) {
            $this->tpl = new \Verba\FastTemplate(SYS_TEMPLATES_DIR);
        }
        return $this->tpl;
    }

    function setTplvars($tplvars)
    {
        if (!is_array($this->tplvars)) {
            $this->tplvars = [];
        }
        if (!count($tplvars)) {
            return;
        }
        $this->tpl()->assign($tplvars);
        $this->tplvars = array_replace_recursive($this->tplvars, $tplvars);
    }

    function setTemplates($templates)
    {
        if (!is_array($this->templates)) {
            $this->templates = array();
        }
        if (!is_array($templates) || !count($templates)) {
            return;
        }
        $this->tpl()->define($templates);
        $this->templates = array_replace_recursive($this->templates, $templates);
    }
}
