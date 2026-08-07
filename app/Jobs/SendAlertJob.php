<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\AccessCode;
use App\Models\Residence;
use App\Models\User;
use App\Services\FCMService;
use Illuminate\Support\Facades\Log;

class SendAlertJob implements ShouldQueue {
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected ?int $accessCodeId;
    protected string $hash;
    protected string $status;
    protected string $messageText;
    protected ?string $deviceId;
    protected ?int $residenceId;
    protected string $accessType;
    protected string $method;
    protected ?string $subjectName;
    protected array $extraData;

    /**
     * Create a new job instance supporting both legacy access code params and standardized payloads.
     */
    public function __construct(
        ?int $accessCodeId = null,
        string $hash = '',
        string $status = 'denied',
        string $messageText = '',
        ?string $deviceId = null,
        ?int $residenceId = null,
        string $accessType = 'qr',
        string $method = 'scan',
        ?string $subjectName = null,
        array $extraData = []
    ) {
        $this->accessCodeId = $accessCodeId;
        $this->hash = $hash;
        $this->status = $status;
        $this->messageText = $messageText;
        $this->deviceId = $deviceId;
        $this->residenceId = $residenceId;
        $this->accessType = $accessType;
        $this->method = $method;
        $this->subjectName = $subjectName;
        $this->extraData = $extraData;
    }

    /**
     * Create job from standardized payload array.
     */
    public static function fromPayload(array $payload): self
    {
        return new self(
            $payload['access_code_id'] ?? null,
            $payload['scanned_code'] ?? ($payload['hash'] ?? ''),
            $payload['status'] ?? 'denied',
            $payload['message'] ?? '',
            $payload['device_identifier'] ?? null,
            $payload['residence_id'] ?? null,
            $payload['access_type'] ?? 'qr',
            $payload['method'] ?? 'scan',
            $payload['guest_name'] ?? ($payload['subject_name'] ?? null),
            $payload['data'] ?? ($payload['extra_data'] ?? [])
        );
    }

    /**
     * Execute the job.
     */
    public function handle(FCMService $fcmService): void {
        $tokens = [];
        $guestName = $this->subjectName ?? 'invitado';

        // 1. Resolve tokens and guest name from AccessCode if available
        if ($this->accessCodeId) {
            $accessCode = AccessCode::withTrashed()->with(['user', 'residence.users'])->find($this->accessCodeId);
            if ($accessCode) {
                $guestName = $accessCode->guest_name ?: $guestName;
                if ($accessCode->user && $accessCode->user->fcm_token) {
                    $tokens[] = $accessCode->user->fcm_token;
                }
                if ($accessCode->residence) {
                    foreach ($accessCode->residence->users as $u) {
                        if ($u->fcm_token && !in_array($u->fcm_token, $tokens)) {
                            $tokens[] = $u->fcm_token;
                        }
                    }
                }
            }
        }

        // 2. Resolve tokens from Residence if residenceId is set and no tokens gathered yet
        if (empty($tokens) && $this->residenceId) {
            $residence = Residence::with('users')->find($this->residenceId);
            if ($residence) {
                foreach ($residence->users as $u) {
                    if ($u->fcm_token && !in_array($u->fcm_token, $tokens)) {
                        $tokens[] = $u->fcm_token;
                    }
                }
            }
        }

        if (empty($tokens)) return;

        $title = $this->status === 'granted' ? 'Acceso Permitido' : 'Acceso Denegado';
        $body = "El acceso para {$guestName} ha sido " . ($this->status === 'granted' ? 'concedido' : 'denegado') . ".";

        $data = array_merge([
            'type'              => 'access_log',
            'status'            => $this->status,
            'message'           => $this->messageText,
            'guest_name'        => $guestName,
            'scanned_code'      => $this->hash ?? '',
            'device_identifier' => $this->deviceId ?? '',
            'access_type'       => $this->accessType,
            'method'            => $this->method,
            'residence_id'      => (string) ($this->residenceId ?? ''),
            'timestamp'         => now()->toIso8601String()
        ], array_map('strval', $this->extraData));

        try {
            $report = $fcmService->sendNotification($tokens, $title, $body, $data);
            
            if ($report && $report->hasFailures()) {
                foreach ($report->failures()->getItems() as $failure) {
                    Log::error('FCM Error (SendAlertJob): ' . $failure->error()->getMessage(), [
                        'token' => $failure->target()->value()
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::error('Excepción crítica en SendAlertJob: ' . $e->getMessage());
        }
    }
}
