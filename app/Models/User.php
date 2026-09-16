<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\Crypt;

/**
 * User Model
 * 
 * @property int $id
 * @property string $name
 * @property string $phone
 * @property string $password
 * @property string|null $voice_path
 * @property array|null $voice_embedding (Encrypted at rest - AES-256)
 * @property \Carbon\Carbon|null $voice_enrolled_at
 */
class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'phone',
        'password',
        'voice_path',
        'voice_embedding',
        'voice_enrolled_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'voice_embedding', // Hide from API responses for security
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'voice_enrolled_at' => 'datetime',
            'password' => 'hashed',
            // voice_embedding handled by custom accessor/mutator below
        ];
    }

    /**
     * Custom accessor/mutator for voice_embedding
     * 
     * GET: Tries to decrypt, falls back to JSON decode for legacy data
     * SET: Always encrypts new data
     */
    protected function voiceEmbedding(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                if (empty($value)) {
                    return null;
                }

                // Try to decrypt (new encrypted format)
                try {
                    return Crypt::decrypt($value);
                } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
                    // Fallback: Legacy data stored as plain JSON
                    $decoded = json_decode($value, true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        return $decoded;
                    }
                    // If not JSON either, return as-is
                    return $value;
                }
            },
            set: function ($value) {
                if (empty($value)) {
                    return null;
                }
                // Always encrypt new data
                return Crypt::encrypt($value);
            }
        );
    }

    /**
     * Check if user has completed voice enrollment
     */
    public function hasVoiceEnrolled(): bool
    {
        return !empty($this->voice_path) &&
            !empty($this->voice_embedding) &&
            !is_null($this->voice_enrolled_at);
    }

    /**
     * Get the URL to the user's avatar.
     */
    protected function avatarUrl(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->avatar ? asset('storage/' . $this->avatar) : null,
        );
    }
}
