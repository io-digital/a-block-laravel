<?php

declare(strict_types=1);

namespace IODigital\ABlockLaravel\Traits;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use IODigital\ABlockLaravel\Models\ABlockWallet;

trait HasABlockWallets
{
    public function aBlockWallets(): MorphMany
    {
        return $this->morphMany(ABlockWallet::class, 'owner');
    }
}
