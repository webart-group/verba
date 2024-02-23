<?php

namespace Verba\Mod;

use Verba\Mod\SnailMail\Email;
use Verba\Request;

class Viber extends \Verba\Mod
{

    use \Verba\ModInstance;

//    function setWebHook()
//    {
//        $auth_token = $this->_c['token'];
//        $webhook = $this->_c['webhook'];
//
//        $jsonData =
//            '{
//		"auth_token": "' . $auth_token . '",
//		"url": "' . $webhook . '",
//		"event_types": ["subscribed", "unsubscribed", "delivered", "message", "seen"]
//	}';
//
//        $ch = curl_init('https://chatapi.viber.com/pa/set_webhook');
//        curl_setopt($ch, CURLOPT_POST, 1);
//        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
//        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
//        $response = curl_exec($ch);
//        $err = curl_error($ch);
//        curl_close($ch);
//        if ($err) {
//            echo($err);
//        } else {
//            echo($response);
//        }
//    }
//
//    function saveChatId()
//    {
//        // Получаем данные из входящего запроса
//        $input = file_get_contents("php://input");
//        $update = json_decode($input, TRUE);
//
//        // Обработка команды
//        if (isset($update["message"])) {
//            $message = $update["message"];
//            $sender_id = $message["sender"]["id"];
//            $text = $message["text"];
//
//            // Проверка команды
//            if ($text === "/start") {
//                // Проверяем, есть ли запись в базе данных для данного sender_id
//                $checkQuery = "SELECT congratulations_viber FROM " . SYS_DATABASE . ".admin_contacts WHERE viber = '" . $this->DB()->escape_string($sender_id) . "'";
//                $congratulationsResult = $this->DB()->query($checkQuery);
//
//                if ($congratulationsResult && $row = $congratulationsResult->fetch_assoc()) {
//                    // Если запись существует и поздравление уже отправлено, не делаем ничего
//                    if ($row["congratulations_viber"] == 1) {
//                        return;
//                    }
//                }
//
//                // Поздравление
//                $congratulationsMessage = 'Subscribed successfully!';
//                $this->sendMessage($sender_id, $congratulationsMessage);
//
//                // Обновление колонки
//                $updateQuery = "INSERT INTO " . SYS_DATABASE . ".admin_contacts (telegram, congratulations_viber) VALUES ('" . $this->DB()->escape_string($sender_id) . "', 1)";
//                $this->DB()->query($updateQuery);
//            } else {
//                $error_message = 'Не правильная команда, введите /start';
//                $this->sendMessage($sender_id, $error_message);
//            }
//        }
//    }
//
//    function notifyAdmins($message)
//    {
//        {
//            $query = "SELECT * FROM " . SYS_DATABASE . ".admin_contacts WHERE telegram IS NOT NULL";
//            $sqlr = $this->DB()->query($query);
//
//            if (!$sqlr || !$sqlr->getNumRows()) {
//                return $this->content;
//            }
//
//            $receivers = [];
//
//            while ($item = $sqlr->fetchRow()) {
//                $row = [
//                    'viber' => $item['viber'] ?? null,
//                ];
//                $receivers[] = $row;
//            }
//
//            // Отправить уведомление каждому
//            foreach ($receivers as $receiver) {
//                $receiverId = $receiver['viber'];
//                $this->sendMessage($receiverId, $message);
//            }
//        }
//    }
//
//    function sendMessage($receiverId, $message)
//    {
//        // Формируем данные для запроса
//        $data = array(
//            'receiver' => $receiverId,
//            'type' => 'text',
//            'text' => $message
//        );
//
//        // Преобразуем данные в JSON формат
//        $jsonData = json_encode($data);
//
//        // Устанавливаем заголовки запроса
//        $headers = array(
//            'Content-Type: application/json',
//            'X-Viber-Auth-Token: ' . $this->_c['token']
//        );
//
//        // Инициализируем cURL сессию
//        $ch = curl_init();
//
//        // Устанавливаем URL и другие параметры cURL запроса
//        curl_setopt($ch, CURLOPT_URL, 'https://chatapi.viber.com/pa/send_message');
//        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
//        curl_setopt($ch, CURLOPT_POST, 1);
//        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
//        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
//
//        // Выполняем cURL запрос и получаем результат
//        $response = curl_exec($ch);
//
//        // Закрываем cURL сессию
//        curl_close($ch);
//
//        // Обрабатываем ответ от сервера
//        if ($response !== false) {
//            $responseData = json_decode($response, true);
//            if (isset($responseData['status']) && $responseData['status'] == 0) {
//                echo "Сообщение успешно отправлено в Viber.";
//            } else {
//                echo "Ошибка при отправке сообщения в Viber. Код ошибки: " . $responseData['status'];
//            }
//        } else {
//            echo "Ошибка при выполнении запроса к Viber API.";
//        }
//    }

    function setWebHook()
    {
        $auth_token = $this->_c['token'];
        $webhook = 'https://api.boostify.com.ua/api/v1/viber';

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

    function handleViberBot(Request $request)
    {
        $auth_token = $this->_c['token'];
        $send_name = "BoostifyNotification";
        $is_log = true;

        function put_log_in($data)
        {
            global $is_log;
            if ($is_log) {
                file_put_contents("tmp_in.txt", $data . "\n", FILE_APPEND);
            }
        }

        function put_log_out($data)
        {
            global $is_log;
            if ($is_log) {
                file_put_contents("tmp_out.txt", $data . "\n", FILE_APPEND);
            }
        }

        function sendReq($data)
        {
            $request_data = json_encode($data);
            put_log_out($request_data);

            //here goes the curl to send data to user
            $ch = curl_init("https://chatapi.viber.com/pa/send_message");
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $request_data);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
            $response = curl_exec($ch);
            $err = curl_error($ch);
            curl_close($ch);
            if ($err) {
                return $err;
            } else {
                return $response;
            }
        }

        function sendMsg($sender_id, $text, $type, $tracking_data = Null, $arr_asoc = Null)
        {
            global $auth_token, $send_name;

            $data['auth_token'] = $auth_token;
            $data['receiver'] = $sender_id;
            if ($text != Null) {
                $data['text'] = $text;
            }
            $data['type'] = $type;
            //$data['min_api_version'] = $input['sender']['api_version'];
            $data['sender']['name'] = $send_name;
            //$data['sender']['avatar'] = $input['sender']['avatar'];
            if ($tracking_data != Null) {
                $data['tracking_data'] = $tracking_data;
            }
            if ($arr_asoc != Null) {
                foreach ($arr_asoc as $key => $val) {
                    $data[$key] = $val;
                }
            }

            return sendReq($data);
        }

        function sendMsgText($sender_id, $text, $tracking_data = Null)
        {
            return sendMsg($sender_id, $text, "text", $tracking_data);
        }

        $input = $request;
        put_log_in($request);

        $type = $input['message']['type']; //type of message received (text/picture)
        $text = $input['message']['text']; //actual message the user has sent
        $sender_id = $input['sender']['id']; //unique viber id of user who sent the message
        $sender_name = $input['sender']['name']; //name of the user who sent the message

        if ($input['event'] == 'webhook') {
            $webhook_response['status'] = 0;
            $webhook_response['status_message'] = "ok";
            $webhook_response['event_types'] = 'delivered';
            echo json_encode($webhook_response);
            die;
        } else if ($input['event'] == "subscribed") {
            sendMsgText($sender_id, "Subscribed successfully!");
        } else if ($input['event'] == "conversation_started") {
            sendMsgText($sender_id, "Conversation started!");
        } elseif ($input['event'] == "message") {
            sendMsg($sender_id, $text, $type);
        }
    }
}
