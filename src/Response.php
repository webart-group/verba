<?php
namespace Verba;

class Response extends \Verba\Block\Html
{
    public $role = 'response';

    function sendHeaders()
    {
        if (!count($this->headers)) {
            return;
        }

        foreach ($this->headers as $h_keyword => $h_value) {
            if (is_numeric($h_keyword)) {
                header($h_value);
            } else {
                header(trim($h_keyword) . ': ' . $h_value);
            }
        }
    }

//    function handleException($e)
//    {
//        $this->content = $e->getMessage();
//        $this->log()->error($e);
//        if (array_key_exists('Location', $this->headers)) {
//            unset($this->headers['Location']);
//        }
//    }

    function output()
    {
        $this->sendHeaders();
        print($this->content);
    }
}
