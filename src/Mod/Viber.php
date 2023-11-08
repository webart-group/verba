<?php

namespace Verba\Mod;

use Verba\Mod\SnailMail\Email;

class Viber extends \Verba\Mod
{

    use \Verba\ModInstance;

    function setWebHook()
    {
        $auth_token = $this->_c['token'];
        $webhook = $this->_c['webhook'];

        $jsonData =
            '{
		"auth_token": "' . $auth_token . '",
		"url": "' . $webhook . '",
		"event_types": ["subscribed", "unsubscribed", "delivered", "message", "seen"]
	}';

        $ch = curl_init('https://chatapi.viber.com/pa/set_webhook');
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        $response = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);
        if ($err) {
            echo($err);
        } else {
            echo($response);
        }
    }

    function saveChatId()
    {
        // Получаем данные из входящего запроса
        $input = file_get_contents("php://input");
        $update = json_decode($input, TRUE);

        // Обработка команды
        if (isset($update["message"])) {
            $message = $update["message"];
            $sender_id = $message["sender"]["id"];
            $text = $message["text"];

            // Проверка команды
            if ($text === "/start") {
                // Проверяем, есть ли запись в базе данных для данного sender_id
                $checkQuery = "SELECT congratulations_viber FROM " . SYS_DATABASE . ".admin_contacts WHERE viber = '" . $this->DB()->escape_string($sender_id) . "'";
                $congratulationsResult = $this->DB()->query($checkQuery);

                if ($congratulationsResult && $row = $congratulationsResult->fetch_assoc()) {
                    // Если запись существует и поздравление уже отправлено, не делаем ничего
                    if ($row["congratulations_viber"] == 1) {
                        return;
                    }
                }

                // Поздравление
                $congratulationsMessage = 'Subscribed successfully!';
                $this->sendMessage($sender_id, $congratulationsMessage);

                // Обновление колонки
                $updateQuery = "INSERT INTO " . SYS_DATABASE . ".admin_contacts (telegram, congratulations_viber) VALUES ('" . $this->DB()->escape_string($sender_id) . "', 1)";
                $this->DB()->query($updateQuery);
            } else {
                $error_message = 'Не правильная команда, введите /start';
                $this->sendMessage($sender_id, $error_message);
            }
        }
    }

    function notifyAdmins($message)
    {
        {
            $query = "SELECT * FROM " . SYS_DATABASE . ".admin_contacts WHERE telegram IS NOT NULL";
            $sqlr = $this->DB()->query($query);

            if (!$sqlr || !$sqlr->getNumRows()) {
                return $this->content;
            }

            $receivers = [];

            while ($item = $sqlr->fetchRow()) {
                $row = [
                    'viber' => $item['viber'] ?? null,
                ];
                $receivers[] = $row;
            }

            // Отправить уведомление каждому
            foreach ($receivers as $receiver) {
                $receiverId = $receiver['viber'];
                $this->sendMessage($receiverId, $message);
            }
        }
    }

    function sendMessage($receiverId, $message)
    {
        // Формируем данные для запроса
        $data = array(
            'receiver' => $receiverId,
            'type' => 'text',
            'text' => $message
        );

        // Преобразуем данные в JSON формат
        $jsonData = json_encode($data);

        // Устанавливаем заголовки запроса
        $headers = array(
            'Content-Type: application/json',
            'X-Viber-Auth-Token: ' . $this->_c['token']
        );

        // Инициализируем cURL сессию
        $ch = curl_init();

        // Устанавливаем URL и другие параметры cURL запроса
        curl_setopt($ch, CURLOPT_URL, 'https://chatapi.viber.com/pa/send_message');
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        // Выполняем cURL запрос и получаем результат
        $response = curl_exec($ch);

        // Закрываем cURL сессию
        curl_close($ch);

        // Обрабатываем ответ от сервера
        if ($response !== false) {
            $responseData = json_decode($response, true);
            if (isset($responseData['status']) && $responseData['status'] == 0) {
                echo "Сообщение успешно отправлено в Viber.";
            } else {
                echo "Ошибка при отправке сообщения в Viber. Код ошибки: " . $responseData['status'];
            }
        } else {
            echo "Ошибка при выполнении запроса к Viber API.";
        }
    }
}
