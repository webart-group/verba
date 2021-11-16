<?php
namespace Verba\Exception;

class Routing extends \Exception {
    public $_handler;
    public $_request;
}