<?php

namespace Verba\Mod;

use Verba\Mod\SnailMail\Email;
use Verba\Request;
use function Verba\_oh;

class Assistant extends \Verba\Mod
{

    use \Verba\ModInstance;

    function sendMessageToChatGPT(Request $request)
    {
        $input = $request->post();
        $message = $input['message'] . " Ответь от лица компании по продвижению Boostify UA {$this->_c['company_name']}"; // message to bot

        $sid = $_REQUEST['sid']; // id session

        $lastAnswers = []; // last answers from bot by sid

        // query to get sids of the user
        $query_sid = "
            SELECT COUNT(*) AS count_sid
            FROM " . SYS_DATABASE . ".assistant
            WHERE sid = '" . $this->DB()->escape_string($sid) . "'";
        $sid_result = $this->DB()->query($query_sid);

        // count of sid of user
        if ($sid_result && $sid_result->getNumRows() > 0) {
            $row = $sid_result->fetchRow();
            $sid_count = $row["count_sid"];
        } else {
            $sid_count = 0;
        }

        // query to get last to answers by sid
        $query_answer = "
            SELECT answer
            FROM " . SYS_DATABASE . ".assistant
            WHERE sid = '" . $this->DB()->escape_string($sid) . "'
            ORDER BY created_at 
            DESC LIMIT 2";
        $answer_result = $this->DB()->query($query_answer);

        // if nothing in DB return []
        if (!$answer_result || !$answer_result->getNumRows()) {
            return $lastAnswers;
        }

        // write answer into $lastAnswers
        while ($item = $answer_result->fetchRow()) {
            $lastAnswers[] = $item['answer'];
        }

        // request content
        $messages = [
            ["role" => "user", "content" => $message],
            ["role" => "assistant", "content" => implode("; ", array_reverse($lastAnswers))]
        ];

        $request_data = [
            "model" => "gpt-3.5-turbo",
            "messages" => $messages
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->_c['api_url']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Content-Type: application/json",
            "Authorization: Bearer {$this->_c['token']}"
        ]);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($request_data));

        $response = curl_exec($ch);

        $res_data = json_decode($response, true);
        $assistant_answer = $res_data['choices'][0]['message']['content'];

        // write to DB question and answer from bot
        $updateQuery = "
        INSERT INTO " . SYS_DATABASE . ".assistant (
            question, 
            answer,
            sid,
            question_number) 
        VALUES (
            '" . $this->DB()->escape_string($message) . "',
            '" . $this->DB()->escape_string($assistant_answer) . "',
            '" . $this->DB()->escape_string($sid) . "',
            '" . ($sid_count + 1) . "'
            )";
        $this->DB()->query($updateQuery);

        curl_close($ch);

        return $assistant_answer;
    }
}
