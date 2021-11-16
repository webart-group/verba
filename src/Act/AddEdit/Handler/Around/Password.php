<?php
//unhandled
namespace Verba\Act\AddEdit\Handler\Around;

use Act\AddEdit\Handler\Around;

class Password extends Around
{
    function run()
    {
        $attr_code = $this->A->getCode();

        if (!isset($this->value)) {
            if ($this->ah->getAction() == 'new') {
                $randomPass = \Verba\Hive::make_random_string(8, 8);
                $this->value = array($randomPass, $randomPass);
            } else {
                return null;
            }
        }
        /**
         * @var $mUser \User
         * @var $U \U
         */
        $mUser = \Verba\_mod('user');
        $U = \Verba\User();
        if ($this->action == 'new') {

            if (is_array($this->value) && isset($this->value[0], $this->value[1]) && !empty($this->value[0]) && $this->value[0] == $this->value[1]) {
                $this->ah->addExtendedData(array('password' => $this->value[0]));
                return $mUser->pwdHash($this->value[0]);
            } else {
                return false;
            }
        } elseif ($this->action == 'edit') {

            if (is_array($this->value) && !empty($this->value) && isset($this->value[0]) && !empty($this->value[0])) {

                $confirmed = false;
                if (isset($this->value[2])) {
                    // восстановление пароля
                    if (is_object($this->value[2]) && isset($this->value[2]->password_reset_code)
                        && $this->value[2]->password_reset_code == $this->ah->getExistsValue('password_reset_code')
                        // смена пароля
                        || is_string($this->value[2]) && $mUser->pwdVerify($this->value[2], $this->ah->getExistsValue($attr_code))
                    ) {
                        $confirmed = true;
                    }
                }

                if (!$confirmed && !$U->in_group(USR_ADMIN_GROUP_ID)) {
                    $this->log()->error('Action not confirmed');
                    return false;
                }

                if ($this->value[0] !== $this->value[1]) {
                    $this->log()->error('New password mismatch');
                    return false;
                }

                // Пароль изменен
                return $mUser->pwdHash($this->value[0]);

            } elseif (is_array($this->value) && empty($this->value[0])) {
                return $this->ah->getExistsValue($attr_code);
            }
        }
        return false;
    }
}
