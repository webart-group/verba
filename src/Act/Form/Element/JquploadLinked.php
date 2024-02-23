<?php

namespace Verba\Act\Form\Element;

class JquploadLinked extends Jqupload
{
    public $uiformat = 'multi';
    public $updateUrl;
    public $removeUrl;
    public $uploadUrl;
    public $lot;
    public $attrs = array(
        'title'
    );
    public $templates = array(
        'e_multi' => 'aef/fe/jqupload/e_multi.tpl',
    );

    public $values;

    public $classes = array('fileupload-widget', 'multi');

    function makeRightName()
    {
        $lc = $this->isLcd ? '[' . $this->locale . ']' : '';
        return 'NewObject[' . (!empty($this->lot) ? \Verba\_oh($this->lot)->getID() : $this->oh->getID()) . '][' . (!empty($this->attr) ? $this->attr : $this->acode) . ']' . $lc;
    }

    function loadValues()
    {
        if ($this->values !== null) {
            return $this->values;
        }
        $this->values = array();
        if ($this->aef->getAction() == 'new') {
            return $this->values;
        }

        $_linked = \Verba\_oh($this->lot);
        $l_ot = $_linked->getID();
        $pac = $_linked->getPAC();

        $qm = new \Verba\QueryMaker($_linked, false, $this->attrs);
        $qmc = $qm->addConditionByLinkedOT($this->oh->getID(), $this->aef->getIID());
        $qm->addOrder(array('priority' => 'd', $pac => 'd'));
        $sqlr = $qm->run();
        $mImage = \Verba\_mod('image');
        if ($sqlr && $sqlr->getNumRows() > 0) {
            while ($row = $sqlr->fetchRow()) {
                $this->values[] = array(
                    'ot_id' => $l_ot,
                    'id' => $row[$pac],
                    '_name' => $row['filename'],
                    'name' => $row[$this->attr],
                    'filepath' => $this->fCfg->getFilepath($row[$this->attr]),
                    'size' => $row['size'],
                    'url' => $this->fCfg->getFileUrl($row[$this->attr]),
                    'type' => $mImage->getMIMETypeById($row['type']),
                    'priority' => $row['priority'],
                );
            }
        }
        return $this->values;
    }

    function makeE()
    {

        $this->fire('makeE');

        $l_oh = !empty($this->lot) ? \Verba\_oh($this->lot) : $this->oh;
        $attr = !empty($this->attr) ? $this->attr : $this->acode;

        $cfg = array(
            'ot_id' => $this->oh->getID(),
            'l_ot_id' => $l_oh->getID(),
            'l_ot_code' => $l_oh->getCode(),
            'attr' => $attr,
            'files' => array(),
            'fcfg' => $this->fCfgName,
            'jqu' => $this->jqupload,
            'updateUrl' => $this->updateUrl,
            'removeUrl' => $this->removeUrl,
        );

        $cfg['jqu']['paramName'] = $this->makeRightName();
        $this->loadValues();
        if (!empty($this->values)) {
            $cfg['files'] = $this->values;
        }

        $this->tpl->assign(array(
            'JQU_ONPAGE_CONFIG' => json_encode($cfg),
            'JQU_E_ID' => $this->getId(),
            'JQU_E_NAME' => $this->getName(),
            'JQU_E_CLASS_ATTR' => $this->makeClassesTagAttr(),
            'JQU_E_FORM_ID' => $this->aef->getId(),
            'JQU_MULTIPLE' => /*$single ? '' :*/ 'multiple',
            'JQU_E_HIDDEN_ID' => $this->getId() . '_hidden',
        ));

        $this->tpl->define(array(
            'jqupload' => $this->templates['e_' . $this->uiformat],
        ));

        $this->setE($this->tpl->parse(false, 'jqupload'));
        $this->fire('makeEFinalize');

    }
}
