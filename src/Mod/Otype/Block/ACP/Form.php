<?php
/**
 * @author webart.group
 * @author Кудрявцев Максим (Kudriavtsev Maksym), <kmv@webart.group>
 * @copyright See copyright.md
 * Date: 15.09.19
 * Time: 19:06
 */

namespace Verba\Mod\Otype\Block\ACP;


use Verba\Block\Json;
use function Verba\_oh;

class Form extends Json
{
    public $prod = 0;

    function build()
    {
        $oh = _oh('otype');
        $bp = $this->request->asArray();
        $bp['cfg'] = 'acp acp-otype';

        if ($this->prod) {
            $bp['cfg'] .= '';
        }

        $form = $oh->initForm($bp);
        $this->content = $form->makeForm();
        return $this->content;
    }
}
