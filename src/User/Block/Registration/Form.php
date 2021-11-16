<?php
namespace Mod\User\Block\Registration;

use \Verba\User\User\Block\Login\Form as LoginForm;

class Form extends LoginForm
{
    public $initState = 'registration';
    protected $defaultState = 'registration';
}
