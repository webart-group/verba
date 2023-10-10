<?php

namespace Verba\Mod;

use Verba\Mod\SnailMail\Email;

class Telegram extends \Verba\Mod
{

    use \Verba\ModInstance;

    function notifyAdmins($message)
    {
        // Список всех пользователей, для которых идет рассылка
        $subscribers = [
            $this->_c['admins_chat_id'], // https://t.me/+EN9gv5EGLyRjYTgy Boostify Notification Group
        ];

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
