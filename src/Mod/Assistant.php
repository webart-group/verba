<?php

namespace Verba\Mod;

use Verba\Mod\SnailMail\Email;
use Verba\Request;
use function Verba\_oh;

class Assistant extends \Verba\Mod
{

    use \Verba\ModInstance;

    function sendMessageToChatGPT(Request $request) {
        $input = $request->post();
        $message = $input['message'];
        $prevBotAnswer = $input['prevBotAnswer'];

        $messages = [
            ["role" => "user", "content" => $message],
            ["role" => "assistant", "content" => implode("; ", $prevBotAnswer)]
        ];

        $data = [
            "model" => "gpt-3.5-turbo",
            "messages" => $messages
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://api.openai.com/v1/chat/completions");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Content-Type: application/json",
            "Authorization: Bearer {$this->_c['token']}"
        ]);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

        $response = curl_exec($ch);
        curl_close($ch);

        return $response;
    }
}