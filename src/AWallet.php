<?php

namespace IODigital\ABlockLaravel;

use IODigital\ABlockPHP\ABlockClient;
use Illuminate\Database\Eloquent\Model;
use IODigital\ABlockLaravel\Models\ABlockWallet;
use IODigital\ABlockLaravel\Models\ABlockKeypair;
use IODigital\ABlockLaravel\Models\ABlockTransaction;
use IODigital\ABlockPHP\DTO\EncryptedWalletDTO;
use IODigital\ABlockPHP\DTO\PaymentAssetDTO;
use Illuminate\Database\UniqueConstraintViolationException;
use IODigital\ABlockPHP\Exceptions\PassPhraseNotSetException;
use IODigital\ABlockLaravel\Exceptions\NameNotUniqueException;
use Exception;

class AWallet
{
    private ABlockWallet $activeWallet;

    public function __construct(
        private ABlockClient $client
    ) {}

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

            $this->setActive($wallet, $passPhrase);

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

            $this->client->openWallet($walletDTO);
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
            $addressList = $this->getAddressList();

            return $this->client->fetchBalance($addressList);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function createReceiptAsset(
        ABlockKeypair $keyPair,
        string $name,
        int $amount = 1,
        bool $defaultHash = false,
        ?array $metaData = [],
    ): array {
        try {
            return $this->client->createReceiptAsset(
                name: $name,
                encryptedKey: $keyPair->save,
                nonce: $keyPair->nonce,
                amount: $amount,
                defaultDrsTxHash: $defaultHash,
                metaData: $metaData
            );
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function createReceiptPayment(
        string $paymentAddress,
        int $amount,
        string $drsTxHash,
        array $metaData = null,
        string $excessAddress = null
    ): array {
        try {
            $senderKeyPairs = $this->getActiveWalletKeypairs();

            return $this->client->createReceiptPayment(
                senderKeypairs: $senderKeyPairs,
                paymentAddress: $paymentAddress,
                amount: $amount,
                drsTxHash: $drsTxHash,
                metaData: $metaData,
                excessAddress: $excessAddress
            );
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function createTradeRequest(
        string $otherPartyAddress,
        string $myAddress,
        array $myAsset,
        array $otherPartyAsset
    ): ABlockTransaction {
        $keypairs = $this->getActiveWalletKeypairs();
        $myAssetDTO = $this->makeAssetPayload($myAsset);
        $otherPartyAssetDTO = $this->makeAssetPayload($otherPartyAsset);

        $encryptedTransaction = $this->client->createTradeRequest(
            myKeypairs: $keypairs,
            myAddress: $myAddress,
            otherPartyAsset: $otherPartyAssetDTO,
            otherPartyAddress: $otherPartyAddress,
            myAsset: $myAssetDTO
        );

        return $this->activeWallet->transactions()->create([
            'druid' => $encryptedTransaction['druid'],
            'nonce' => $encryptedTransaction['nonce'],
            'content' => $encryptedTransaction['save']
        ]);
    }

    public function getPendingTransactions()
    {
        try {
            $keypairs = $this->getActiveWalletKeypairs();
            $pendingTransactions = $this->activeWallet->transactions()->get();

            $encryptedTransactionMap = $pendingTransactions->mapWithKeys(fn ($item) => [
                $item->druid => [
                    'save' => $item->content,
                    'nonce' => $item->nonce,
                    'druid' => $item->druid
                ]
            ])->toArray();

            return $this->client->getPendingTransactions(
                keypairs: $keypairs,
                encryptedTransactionMap: $encryptedTransactionMap
            );
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function acceptPendingTransaction(
        string $druid,
    ) {
        try {
            return $this->client->acceptPendingTransaction(
                druid: $druid,
                keypairs: $this->getActiveWalletKeypairs(),
            );
        } catch (Exception $e) {
            throw $e;
        }
    }

    private function getAddressList(): array
    {
        return $this->activeWallet->keypairs
        ->map(fn ($item) => $item->address)
        ->unique()
        ->toArray();
    }

    private function getActiveWalletKeypairs(): array
    {
        return $this->activeWallet->keypairs->mapWithKeys(fn ($item) => [$item->address => [
            'encryptedKey' => $item->save,
            'nonce' => $item->nonce
        ]])->toArray();
    }

    private function makeAssetPayload(array $asset): PaymentAssetDTO
    {
        return isset($asset['hash']) ? new PaymentAssetDTO(
            amount: $asset['amount'],
            drsTxHash: $asset['hash'],
            assetType: PaymentAssetDTO::ASSET_TYPE_RECEIPT
        ) : new PaymentAssetDTO(
            amount: $asset['amount'],
            assetType: PaymentAssetDTO::ASSET_TYPE_TOKEN
        );
    }
}
