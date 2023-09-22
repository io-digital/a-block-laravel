<?php

namespace IODigital\ABlockLaravel\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ABlockTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        //'a_block_keypair_id',
        'druid',
        'nonce',
        'content',
    ];

    // public function keypair(): BelongsTo
    // {
    //     return $this->belongsTo(ABlockKeypair::class, 'a_block_keypair_id');
    // }
}
