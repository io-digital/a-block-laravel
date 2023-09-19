<?php

namespace IODigital\ABlockLaravel\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ABlockWallet extends Model
{
    use HasFactory;

    protected $fillable = [
        'master_key_encrypted_base64',
        'nonce_hex',
        'owner_type',
        'owner_id',
    ];

    public function owner(): MorphTo
    {
        return $this->morphTo();
    }

    public function keypairs(): HasMany
    {
        return $this->hasMany(ABlockKeypair::class);
    }
}
