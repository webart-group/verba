<?php

namespace Verba\Mod;

use Verba\Mod\SnailMail\Email;
use Verba\Request;
use function Verba\_oh;

class Telegram extends \Verba\Mod
{

    use \Verba\ModInstance;

    function saveChatId(Request $request)
    {
        // Получаем данные из входящего запроса
        $input = $request->post();

        // Обработка команды
        if (isset($input["message"])) {
            $message = $input["message"];
            $chat_id = $message["chat"]["id"];
            $text = $message["text"];

            // Проверка команды
            if ($text === "/start") {
                // Проверяем, есть ли запись в базе данных для данного chat_id
                $checkQuery = "SELECT congratulations_sent FROM " . SYS_DATABASE . ".admin_contacts WHERE telegram = '" . $this->DB()->escape_string($chat_id) . "'";
                $congratulationsResult = $this->DB()->query($checkQuery);

                if ($congratulationsResult && $row = $congratulationsResult->fetchRow()) {
                    // Если запись существует и поздравление уже отправлено, не делаем ничего
                    if ($row["congratulations_sent"] == 1) {
                        return;
                    }
                }

                // Обновление колонки
                $updateQuery = "INSERT INTO " . SYS_DATABASE . ".admin_contacts (telegram, congratulations_sent) VALUES ('" . $this->DB()->escape_string($chat_id) . "', 1)";
                $this->DB()->query($updateQuery);
                // Поздравление
                $congratulationsMessage = 'Subscribed successfully!';
                $this->sendMessage($chat_id, $congratulationsMessage);

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
