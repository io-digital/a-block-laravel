<?php

declare(strict_types=1);

namespace IODigital\ABlockLaravel\Traits;

use Illuminate\Database\Eloquent\Relations\MorphMany;

use IODigital\LaravelZenotta\DTO\EncryptedWalletDTO;
use IODigital\ABlockLaravel\Models\ZenottaKeypair;
use IODigital\ABlockLaravel\Models\ZenottaWallet;
use IODigital\LaravelZenotta\ZenottaClient;

trait HasABlockWallets
{
    private ZenottaClient $client;

    private ?ZenottaWallet $activeWallet = null;

    public function __construct()
    {
        $this->client = app()->make(ZenottaClient::class);
    }

    public function aBlockWallets(): MorphMany
    {
        return $this->morphMany(ZenottaWallet::class, 'owner');
    }

    // public function createABlockWallet(string $passPhrase): ZenottaWallet
    // {
    //     $this->client->setPassPhrase($passPhrase);

    //     $walletDTO = $this->client->createWallet();

    //     return $this->aBlockWallets()->create([
    //         'master_key_encrypted_base64' => $walletDTO->getMasterKeyEncrypted(),
    //         'nonce_hex'                   => $walletDTO->getNonce(),
    //     ]);
    // }

    // public function setActiveABlockWallet(ZenottaWallet $wallet, string $passPhrase): bool
    // {
    //     try {
    //         $this->client->setPassPhrase($passPhrase);

    //         $this->client->setWallet(new EncryptedWalletDTO(
    //             masterKeyEncrypted: $wallet->master_key_encrypted_base64,
    //             nonce: $wallet->nonce_hex
    //         ));

    //         $this->activeWallet = $wallet;

    //         return true;
    //     } catch (\Exception $e) {
    //         return false;
    //     }
    // }

    // public function createABlockKeypair(string $name): ZenottaKeypair
    // {
    //     $keypairArr = $this->client->createNewKeypair();

    //     return $this->activeWallet->keypairs()->create([
    //         'name'    => $name,
    //         'nonce'   => $keypairArr['nonce'],
    //         'save'    => $keypairArr['save'],
    //         'address' => $keypairArr['address'],
    //     ]);
    // }
}
