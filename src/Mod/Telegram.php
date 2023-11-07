<?php

namespace Verba\Mod;

use Verba\Mod\SnailMail\Email;
use function Verba\_oh;

class Telegram extends \Verba\Mod
{

    use \Verba\ModInstance;

    function saveChatId()
    {
        // Получаем данные из входящего запроса
        $input = file_get_contents("php://input");
        $update = json_decode($input, TRUE);

        // Обработка команды
        if (isset($update["message"])) {
            $message = $update["message"];
            $chat_id = $message["chat"]["id"];
            $text = $message["text"];

            // Проверка команды
            if ($text === "/start") {
                // Проверяем, было ли уже отправлено поздравление
                $congratulationsSent = $this->checkCongratulationsSent($chat_id);

                if ($congratulationsSent) {
                    return;
                } else {
                    // Поздравление
                    $congratulations = 'Subscribed successfully!';
                    $this->sendMessage($chat_id, $congratulations);

                    // Отмечаем в базе данных, что поздравление было отправлено
                    $this->markCongratulationsSent($chat_id);

                    // Обновление колонки
                    $updateQuery = "INSERT IGNOR INTO " . SYS_DATABASE . ".admin_contacts (telegram, congratulations_sent) VALUES ('" . $this->DB()->escape_string($chat_id) . "', 1)";
                    $this->DB()->query($updateQuery);
                }
            } else {
                $error_message = 'Не правильная команда, введите /start';
                $this->sendMessage($chat_id, $error_message);
            }
        }
    }

    function notifyAdmins($message)
    {
        $query = "SELECT * FROM " . SYS_DATABASE . ".admin_contacts WHERE telegram IS NOT NULL";
        $sqlr = $this->DB()->query($query);

        if (!$sqlr || !$sqlr->getNumRows()) {
            return $this->content;
        }

        $subscribers = [];

        while ($item = $sqlr->fetchRow()) {
            $row = [
                'telegram' => $item['telegram'] ?? null,
            ];
            $subscribers[] = $row;
        }

        // Отправить уведомление каждому
        foreach ($subscribers as $subscriber) {
            $subscriberId = $subscriber['telegram'];
            $this->sendMessage($subscriberId, $message);
        }
    }

    function checkCongratulationsSent($chat_id)
    {
        // Проверка в базе данных, было ли уже отправлено поздравление для данного chat_id
        $query = "SELECT congratulations_sent FROM " . SYS_DATABASE . ".admin_contacts WHERE telegram = '" . $this->DB()->escape_string($chat_id) . "'";
        $result = $this->DB()->query($query);

        if ($result && $row = $result->fetch_assoc()) {
            return (bool)$row['congratulations_sent'];
        }

        return false;
    }

    function markCongratulationsSent($chat_id)
    {
        // Отмечаем в базе данных, что поздравление было отправлено для данного chat_id
        $updateQuery = "UPDATE " . SYS_DATABASE . ".admin_contacts SET congratulations_sent = 1 WHERE telegram = '" . $this->DB()->escape_string($chat_id) . "'";
        $this->DB()->query($updateQuery);
    }

    function sendMessage($chat_id, $message)
    {
        $apiToken = $this->_c['token']; // Замените на свой токен бота
        $apiUrl = "https://api.telegram.org/bot" . $apiToken . "/sendMessage";
        $data = [
            'chat_id' => $chat_id,
            'text' => $message
        ];

        // Отправка запроса к Telegram API
        $ch = curl_init($apiUrl);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        $response = curl_exec($ch);
        curl_close($ch);

        // Обработка ответа от Telegram API
        $result = json_decode($response, true);
        return $result;
    }
}
