<?php

namespace IODigital\ABlockLaravel;

use IODigital\ABlockPHP\ABlockClient;
use Illuminate\Database\Eloquent\Model;
use IODigital\ABlockLaravel\Models\ABlockWallet;
use IODigital\ABlockLaravel\Models\ABlockKeypair;
use IODigital\ABlockPHP\DTO\EncryptedWalletDTO;
use Illuminate\Database\UniqueConstraintViolationException;
use IODigital\ABlockPHP\Exceptions\PassPhraseNotSetException;
use IODigital\ABlockLaravel\Exceptions\NameNotUniqueException;

class AWallet
{
    private ABlockWallet $activeWallet;

    public function __construct(
        private ABlockClient $client
    ) {
    }

    public function setPassPhrase(string $passPhrase): void
    {
        $this->client->setPassPhrase($passPhrase);
    }

    public function create(
        string $name,
        string $passPhrase,
        Model $owner
    ): array {
        try {
            $this->setPassPhrase($passPhrase);
            $walletDTO = $this->client->createWallet();

            $wallet = $owner->aBlockWallets()->create([
                'name' => $name,
                'master_key_encrypted_base64' => $walletDTO->getMasterKeyEncrypted(),
                'nonce_hex'                   => $walletDTO->getNonce(),
            ]);

            return [
                'wallet' => $wallet,
                'seedPhrase' => $walletDTO->getSeedPhrase()
            ];

        } catch (PassPhraseNotSetException $e) {
            throw $e;
        } catch (UniqueConstraintViolationException $e) {
            throw new NameNotUniqueException();
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function setActive(ABlockWallet $wallet, string $passPhrase): bool
    {
        try {
            $this->setPassPhrase($passPhrase);

            $walletDTO = new EncryptedWalletDTO(
                masterKeyEncrypted: $wallet->master_key_encrypted_base64,
                nonce: $wallet->nonce_hex
            );

            $this->client->setWallet($walletDTO);
            $this->activeWallet = $wallet;

            return true;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function createKeypair(string $name): ABlockKeypair
    {
        try {
            $keypairArr = $this->client->createKeypair();

            return $this->activeWallet->keypairs()->create([
                'name'    => $name,
                'nonce'   => $keypairArr['nonce'],
                'save'    => $keypairArr['save'],
                'address' => $keypairArr['address'],
            ]);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function fetchBalance(): array
    {
        try {
            $addressList = $this->activeWallet->keypairs->map(fn ($item) => $item->address)->toArray();
            return $this->client->fetchBalance($addressList);
        } catch (\Exception $e) {
            throw $e;
        }
    }
}
