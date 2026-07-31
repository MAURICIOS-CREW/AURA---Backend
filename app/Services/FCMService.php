<?php

namespace App\Services;

use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;

class FCMService {
    protected Messaging $messaging;

    public function __construct(Messaging $messaging) {
        $this->messaging = $messaging;
    }

    /**
     * Envía una notificación push a un token específico o a varios tokens.
     *
     * @param string|array $token El token FCM o array de tokens.
     * @param string $title Título de la notificación.
     * @param string $body Cuerpo de la notificación.
     * @param array $data Datos adicionales a enviar de manera oculta (key-value de strings).
     * @return \Kreait\Firebase\Messaging\MulticastSendReport|null Resultados del envío
     */
    public function sendNotification($token, string $title, string $body, array $data = [])
    {
        if (empty($token)) {
            return null;
        }

        // Para Data-Only messages, agregamos el título y el cuerpo a los datos
        $data['title'] = $title;
        $data['body'] = $body;

        // Convert data values to strings to prevent FCM errors
        $stringData = array_map('strval', $data);

        $message = CloudMessage::new()
            ->withData($stringData);

        if (is_array($token)) {
            return $this->messaging->sendMulticast($message, $token);
        }

        return $this->messaging->sendMulticast($message, [$token]);
    }
}
