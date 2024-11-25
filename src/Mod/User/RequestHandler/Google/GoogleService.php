<?php

namespace Verba\Mod\User\RequestHandler\Google;

class GoogleService
{
    const GET_USER_INFO_URL = 'https://www.googleapis.com/oauth2/v1/userinfo';

    public function __construct()
    {
    }

    public function createAuthUrl()
    {
    }

    public function getUserInfo(string $accessToken): ?UserInfoModel
    {
        $ch = curl_init();

        $url = self::GET_USER_INFO_URL . '?access_token=' . urlencode($accessToken);

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        if ($httpCode !== 200 || $response === false) {
            return null;
        }

        $responseData = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return null;
        }

        return new UserInfoModel($responseData);
    }
}
