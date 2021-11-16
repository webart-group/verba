<?php

namespace Verba\User\Act\List\FieldHandler;

class GetFullUserName extends ListHandlerField
{
    function run()
    {
        return User::getFullName($this->list->row);
    }
}
