<?php

namespace Verba\Mod\User\RequestHandler\Google;
class UserInfoModel
{
    /** int */
    public $id;
    /** string */
    public $email;
    /** bool */
    public $verified_email;
    /** string */

    public function __construct(array $requestData)
    {
        $this->id = intval($requestData['id']);
        $this->email = $requestData['email'];
        $this->verified_email = boolval($requestData['verified_email']);
    }
}
