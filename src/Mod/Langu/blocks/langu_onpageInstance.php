<?php

class langu_onpageInstance extends \Verba\Block\Html
{
    public $sendToClient;

    function build()
    {
        if (!empty($this->sendToClient)) {
            \Verba\Lang::sendToClient($this->sendToClient);
        }

        // Компиляция языковой книги для клиента
        $filedetails = \Verba\Lang::compileJsLangFile(null);
        if (!is_array($filedetails) || !is_string($filedetails[1])) {
            throw new Exception('Unable to open lang file.');
        }

        $this->addScripts(
            array($filedetails[1], \Verba\Lang::getJsPathRel())
            , 1000
        );

        // Добавление кода инициализации JS-класса Languages
        $jsCfg = array(
            'avaible' => \Verba\Lang::getUsedLC(),
        );

        $this->addJsBefore(
            "window.Lang = new Languages(" . json_encode($jsCfg) . ");"
        );
    }
}
