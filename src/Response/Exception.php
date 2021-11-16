<?php
/**
 *
 */
namespace Verba\Response;

class Exception extends Raw
{
    public $exception;

    public $headers = [
        'HTTP/1.1 422 Unprocessable Entity'
    ];

    public $content = 'Unprocessable Entity';

    function build()
    {
        if($this->exception instanceof \Exception && \Verba\User()->in_group(USR_ADMIN_GROUP_ID))
        {
            $this->content =  $this->exception->getMessage()
                ."\nTRACE: ---------------\n".$this->exception->getTraceAsString();
        }

        return parent::build();
    }

    function setException(\Exception $e)
    {
        $this->exception = $e;
        return $this;
    }
}