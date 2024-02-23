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

    function output()
    {
        $this->addSystemHeaders();

        $this->sendHeaders();
        print($this->content);
    }

    function addSystemHeaders()
    {
        if(session_status() === PHP_SESSION_ACTIVE) {
            $this->headers['Verba-Session-Id'] = session_id();
        }
    }
}
