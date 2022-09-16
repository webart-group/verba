<?php
/**
 * @author webart.group
 * @author Кудрявцев Максим (Kudriavtsev Maksym), <kmv@webart.group>
 * @copyright See copyright.md
 * Date: 15.09.19
 * Time: 19:06
 */

namespace Verba\Mod\Otype\Block\ACP;


class Form extends \Verba\Block\Json
{
    public $prod = 0;

    function build()
    {
        $oh = \Verba\_oh('otype');
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
