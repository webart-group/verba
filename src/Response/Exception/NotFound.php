<?php
namespace Verba\Response\Exception;

class NotFound extends \Verba\Response\Exception
{
    public $headers = [
        'HTTP/1.1 404 Not Found'
    ];

    public $content = '';
}