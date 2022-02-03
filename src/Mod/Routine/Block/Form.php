<?php
namespace Mod\Routine\Block;

class Form extends \Verba\Block\Html
{
    use Common;

    public $cfg; //string
    public $dcfg = [];
    public $entryData;
    public $extendedData;

    public $form;

    function build()
    {
        $this->content = false;

        $oh = \Verba\_oh($this->rq->ot_id);

        $cfg = $this->request->asArray();

        if ((is_array($this->cfg) || is_string($this->cfg)) && !empty($this->cfg)) {
            $cfg['cfg'] = $this->cfg;
        }

        if (is_array($this->dcfg) && !empty($this->dcfg)) {
            if (!isset($cfg['dcfg']) || !is_array($cfg['dcfg'])) {
                $cfg['dcfg'] = array();
            }
            $cfg['dcfg'] = array_replace_recursive($cfg['dcfg'], $this->dcfg);
        }
        $cfg['block'] = $this;

        $this->form = $oh->initForm($cfg);

        if ($this->form->getAction() == 'edit') {
            if (is_array($this->entryData)) {
                $this->form->setExistsValues($this->entryData);
            }
        }

        $this->content = $this->form->makeForm();

        return $this->content;
    }
}
