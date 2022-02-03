<?php

namespace Mod\Notifier;


class Event extends \Verba\Configurable
{
    /**
     * @var string
     */
    public $channel;
    /**
     * @var string
     */
    public $pipe;
    /**
     * @var string
     */
    public $method;
    /**
     * @var mixed
     */
    public $data;

    /**
     * Event constructor.
     * @param null $data
     * @param null $method
     * @param null $pipe
     */
    function __construct($data = null, $method = null, $pipe = null)
    {
        if(isset($data))
        {
            $this->data = $data;
        }

        if(isset($pipe))
        {
            $this->pipe = $pipe;
        }

        if(isset($method))
        {
            $this->method = $method;
        }
    }

    function asArray()
    {
        return [
            'pipe' => $this->pipe,
            'method' => $this->method,
            'data' => $this->data,
        ];
    }
}