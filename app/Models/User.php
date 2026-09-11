<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'username', 'email', 'password', 'role', 'email_verified_at'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /** role => [etiket, açıklama] */
    public const ROLES = [
        'admin' => ['Tam yetkili', 'Her şeyi görür, ekler, düzenler, siler; kullanıcı ve ayarları yönetir'],
        'owner' => ['Patron', 'Tam yetkili ile aynı'],
        'editor' => ['Düzenleyici', 'Ekler ve düzenler, silemez; ayarlar ve kullanıcılar kapalı'],
        'viewer' => ['Çalışan', 'Sadece görüntüler; hiçbir şeyi ekleyemez, düzenleyemez, silemez'],
    ];

    public function isManager(): bool
    {
        return in_array($this->role, ['admin', 'owner'], true);
    }

    public function canEdit(): bool
    {
        return in_array($this->role, ['admin', 'owner', 'editor'], true);
    }

    public function canDelete(): bool
    {
        return $this->isManager();
    }

    public function roleLabel(): string
    {
        return self::ROLES[$this->role][0] ?? $this->role;
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
