<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\AccessCode;
use App\Services\FCMService;

class SendAlertJob implements ShouldQueue {
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        protected ?int $accessCodeId,
        protected string $hash,
        protected string $status,
        protected string $messageText,
        protected ?string $deviceId
    ) {}

    /**
     * Execute the job.
     */
    public function handle(FCMService $fcmService): void {
        if (!$this->accessCodeId) return; // No sabemos a quién notificar si el código ni siquiera existe

        $accessCode = AccessCode::withTrashed()->with('user')->find($this->accessCodeId);
        
        if (!$accessCode || !$accessCode->user || !$accessCode->user->fcm_token) return; // No hay usuario o no tiene token FCM

        $token = $accessCode->user->fcm_token;
        $title = $this->status === 'granted' ? 'Acceso Permitido' : 'Acceso Denegado';
        $body = "El acceso para {$accessCode->guest_name} ha sido " . ($this->status === 'granted' ? 'concedido' : 'denegado') . ".";

        $data = [
            'type'              => 'access_log',
            'status'            => $this->status,
            'message'           => $this->messageText,
            'guest_name'        => $accessCode->guest_name ?? '',
            'scanned_code'      => $this->hash ?? '',
            'device_identifier' => $this->deviceId ?? '',
            'access_type'       => 'qr',
            'method'            => 'scan',
            'valid_from'        => $accessCode->valid_from ? $accessCode->valid_from->format('Y-m-d H:i:s') : '',
            'valid_until'       => $accessCode->valid_until ? $accessCode->valid_until->format('Y-m-d H:i:s') : '',
            'uses'              => (string) $accessCode->uses,
            'max_uses'          => (string) $accessCode->max_uses,
            'active_days'       => json_encode($accessCode->active_days ?? []),
            'start_time'        => $accessCode->start_time ?? '',
            'end_time'          => $accessCode->end_time ?? '',
            'timestamp'         => now()->toIso8601String()
        ];

        try {
            $report = $fcmService->sendNotification($token, $title, $body, $data);
            
            if ($report && $report->hasFailures()) {
                foreach ($report->failures()->getItems() as $failure) {
                    \Illuminate\Support\Facades\Log::error('FCM Error (SendAlertJob): ' . $failure->error()->getMessage(), [
                        'token' => $failure->target()->value()
                    ]);
                }
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Excepción crítica en SendAlertJob: ' . $e->getMessage());
        }
    }
}
