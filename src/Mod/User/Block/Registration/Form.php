<?php
namespace Verba\Mod\User\Block\Registration;

use \Verba\Mod\User\Block\Login\Form as LoginForm;

class Form extends LoginForm
{
    public $initState = 'registration';
    protected $defaultState = 'registration';
}
