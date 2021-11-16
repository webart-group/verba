<?php

namespace Verba\Act\AddEdit\Handler\Around;

use Act\AddEdit\Handler\Around;

class UseFirstImageFromText extends Around
{
    function run()
    {
        $attr_code = $this->A->getCode();
        $ot_id = $this->oh->getID();
        $exists = $this->getExistsValue('picture');
        if (
            (
                isset($_FILES['NewObject']['tmp_name'][$ot_id][$attr_code]['upl'])
                && !empty($_FILES['NewObject']['tmp_name'][$ot_id][$attr_code]['upl'])
            )
            || isset($this->gettedObjectData[$attr_code]['delete'])
            || !empty($exists)) {
            return null;
        }
        $srcAttrId = $this->params['secondary'];
        $srcA = $this->oh->A($srcAttrId);
        $ctn = $this->ah->getGettedValue($srcA->getCode());
        if ($srcA->isLcd()) {
            $lang = isset($this->params['lang']) && !empty($this->params['lang'])
                ? $this->params['lang']
                : \Verba\Lang::getDefaultLC();
            $ctn = $ctn[$lang];
        }
        if (!is_string($ctn) || empty($ctn)) {
            return false;
        }

        if (!preg_match("/<img([^>]+)>/is", $ctn, $match)
            || !isset($match[1]) || empty($match[1])) {
            return false;
        }

        if (!preg_match("/src=\"([^\"]+)\"/is", $match[1], $src)
            || !isset($src[1])
            || empty($src[1])) {
            return false;
        }

        //create fake Files entries
        $_FILES['NewObject']['tmp_name'][$this->oh->getID()][$this->A->getCode()]['upl'] = SYS_ROOT . $src[1];
        $_FILES['NewObject']['name'][$this->oh->getID()][$this->A->getCode()]['upl'] = basename($src[1]);
        return $src[1];
    }
}
