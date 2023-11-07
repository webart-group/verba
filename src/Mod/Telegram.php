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
                // Обновление колонки
                $updateQuery = "INSERT INTO admin_contacts (telegram) VALUES ('".$this->DB()->escape_string($chat_id)."')";
                $this->DB()->query($updateQuery);
            }
        }
    }


    function notifyAdmins($message)
    {
        $query = "SELECT * FROM admin_contacts WHERE telegram IS NOT NULL";
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
            $apiUrl = 'https://api.telegram.org/bot' . $this->_c['token'] . '/sendMessage';

            $postData = [
                'chat_id' => $subscriber,
                'text' => $message,
            ];

            $ch = curl_init($apiUrl);
            curl_setopt_array($ch, [
                CURLOPT_POST => TRUE,
                CURLOPT_RETURNTRANSFER => TRUE,
                CURLOPT_TIMEOUT => 10,
                CURLOPT_POSTFIELDS => $postData,
            ]);

            $response = curl_exec($ch);

            if (curl_errno($ch)) {
                echo 'Ошибка при отправке уведомления для chat_id ' . $subscriber . ': ' . curl_error($ch) . PHP_EOL;
            } else {
                print_r('Уведомление успешно отправлено для chat_id ' . $subscriber . "\n");
            }

            curl_close($ch);
        }
    }
}
