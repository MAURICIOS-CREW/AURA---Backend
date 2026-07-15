<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'username', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable {
    /** @use HasFactory<UserFactory> */
    use \Laravel\Sanctum\HasApiTokens, HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }


    /**
     * Relación con el modelo Role
     */
    public function role() { return $this->belongsTo(Role::class); }

    /**
     * Comprueba si el usuario tiene un rol administrativo
     * Se asume que 'superadmin' y 'admin' son roles que acceden al panel web.
     */
    public function isAdmin(): bool {
        return $this->role && in_array($this->role->name, ['superadmin', 'admin']);
    }

    /**
     * Comprueba si el usuario es un usuario móvil
     * Roles que usan la app: 'resident', 'guard', etc.
     */
    public function isMobileUser(): bool{
        return $this->role && in_array($this->role->name, ['resident', 'guard']);
    }
}
