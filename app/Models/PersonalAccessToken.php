<?php

namespace App\Models;

use Laravel\Sanctum\PersonalAccessToken as SanctumPersonalAccessToken;
use Illuminate\Support\Facades\Cache;

class PersonalAccessToken extends SanctumPersonalAccessToken
{
    /**
     * Cache duration for the token (3 hours).
     */
    protected const CACHE_TTL = 10800;

    /**
     * Override findToken to use Cache and prevent database queries on every request.
     *
     * @param  string  $token
     * @return static|null
     */
    public static function findToken($token)
    {
        if (strpos($token, '|') === false) {
            return null; // Not a valid sanctum token format
        }

        $tokenHash = hash('sha256', explode('|', $token, 2)[1]);

        return Cache::remember("sanctum_token:{$tokenHash}", self::CACHE_TTL, function () use ($token) {
            return parent::findToken($token);
        });
    }

    /**
     * Override save to manage cache and sliding session logic without frequent DB writes.
     */
    public function save(array $options = [])
    {
        $now = now();
        $shouldWriteToDb = false;

        // Validar si debemos hacer "sliding session"
        if ($this->expires_at) {
            $remainingMinutes = $now->diffInMinutes($this->expires_at, false);
            
            // 1. Si está a punto de expirar (menos de 30 mins)
            if ($remainingMinutes <= 30) {
                $shouldWriteToDb = true;
            }
            // 2. Si ya pasaron más de 15 minutos desde que se extendió/creó.
            // Para una sesión de 180 min, si el remaining es <= 165 (180-15), actualizamos.
            elseif ($remainingMinutes <= 165) {
                $shouldWriteToDb = true;
            }
        } else {
            // Si no tiene expires_at, guardamos para registrar uso
            $shouldWriteToDb = true;
        }

        $isRefreshToken = in_array('issue-access-token', $this->abilities ?? []);

        // Si es un access_token normal y requiere escritura, le extendemos la vida
        if (!$isRefreshToken && $shouldWriteToDb) {
            $this->expires_at = $now->copy()->addHours(3);
        }

        $saved = true;

        if ($shouldWriteToDb || !$this->exists) {
            $saved = parent::save($options);
        }

        // Actualizamos la caché con la instancia fresca
        if ($saved && $this->token) {
            Cache::put("sanctum_token:{$this->token}", $this, self::CACHE_TTL);
        }

        return $saved;
    }

    /**
     * Getter para expires_at que añade el periodo de gracia.
     */
    public function getExpiresAtAttribute($value)
    {
        if (!$value) {
            return null;
        }

        $expiresAt = $this->asDateTime($value);

        if ($expiresAt->isPast()) {
            $minutesPast = $expiresAt->diffInMinutes(now(), false);
            
            if ($minutesPast >= 0 && $minutesPast <= 15) {
                // Periodo de gracia de 15 minutos: Devolvemos tiempo futuro para evitar 401
                return now()->addMinute();
            }
        }

        return $expiresAt;
    }

    /**
     * Limpiar la caché al eliminar el token.
     */
    protected static function booted()
    {
        static::deleted(function ($token) {
            if ($token->token) {
                Cache::forget("sanctum_token:{$token->token}");
            }
        });
    }
}
