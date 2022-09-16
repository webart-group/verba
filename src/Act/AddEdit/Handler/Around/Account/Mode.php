<?php

namespace Verba\Act\AddEdit\Handler\Around\Account;

use \Verba\Act\AddEdit\Handler\Around;

/**
 * Class Mode
 *
 * Сопряжение Режим с полем active
 *
 * @package Verba\Act\AddEdit\Handler\Around
 */
class Mode extends Around
{
    function run()
    {
        // 1158 - ввод и вывод
        // 1159 - только вывод
        // 1160 - заблокировано

        if(!$this->value){
            return $this->value;
        }

        $active_value = $this->ah->getActualValue('active');
        // 16 ключ - поддержка
        $adminAccess = \Verba\User()->chr(16, 'u');

        // Проверяем активность Счета

        if($this->action == 'edit'){

            // Менять состояние Режима при отключенном счете
            // можно только админу
            if(!$active_value && !$adminAccess){
                throw  new \Verba\Exception\Building(\Verba\Lang::get('account warns inactive_cause_mode inactive_account'));
            }

            $prevValue = $this->getExistsValue('mode');
            // Изменять состояние Режима с 1160 на дпугой
            // можно только админу
            if($prevValue == 1160 && !$adminAccess){
                throw  new \Verba\Exception\Building(\Verba\Lang::get('account warns inactive_cause_mode blocked'));
            }

        }

        // Проверка состояния Активно у Валюты
        $accCur =  \Verba\Mod\Currency::getInstance()->getCurrency($this->ah->getActualValue('currencyId'));
        if(!$accCur->active && $this->value == 1158){
            if($this->action == 'new'){
                $this->value = 1159;
            }else{
                throw  new \Verba\Exception\Building(\Verba\Lang::get('account warns inactive_cause_mode inactive_currency'));
            }

        }

        return $this->value;
    }
}
