<?php

declare(strict_types=1);

namespace IODigital\ABlockLaravel\Console\Traits;

use App\Models\User;
use IODigital\ABlockLaravel\Models\ABlockWallet;
use IODigital\ABlockLaravel\Models\ABlockKeypair;
use IODigital\ABlockPHP\Exceptions\KeypairNotDecryptedException;
use AWallet;
use Exception;

trait UserWallets
{
    public function findUserByEmail(): User
    {
        do {
            $email = $this->ask("What is the user's email address?", 'bob@test.com');
            $user = User::where('email', $email)->first();

            if (!$user) {
                $this->error('User not found, please try again');
            }
        } while (!!$user === false);

        return $user;
    }

    public function promptForNonEmptyString(string $question, string $default = null): string
    {
        do {
            $string = $this->ask($question, $default);
            if(!$string) {
                $this->error('This value cannot be empty');
            }
        } while (!$string);

        return $string;
    }

    public function walletSelect(User $user): ABlockWallet
    {
        $wallets = $user->aBlockWallets()->orderBy('default', 'DESC')->get();

        if (!$wallets->count()) {
            throw new \Exception("User does not have any wallets");
        }

        $walletName = $this->choice(
            'Which wallet is this keypair for?',
            $wallets->map(fn($item) => $item->name)->toArray(),
            0,
            $maxAttempts = null,
            $allowMultipleSelections = false
        );

        return $wallets->where('name', $walletName)->first();
    }

    public function keypairSelect(ABlockWallet $wallet): ABlockKeypair
    {
        $keypairs = $wallet->keypairs()->orderBy('created_at', 'DESC')->get();

        if (!$keypairs->count()) {
            throw new \Exception("The chosen wallet does not have any keypairs");
        }

        $keypairName = $this->choice(
            'Which keypair is this item for?',
            $keypairs->map(fn($item) => $item->name)->toArray(),
            0,
            $maxAttempts = null,
            $allowMultipleSelections = false
        );

        return $keypairs->where('name', $keypairName)->first();
    }

    public function openUserWallet(ABlockWallet $wallet)
    {
        $walletOpened = false;

        do {
            $passPhrase = $this->promptForNonEmptyString('Please enter the pass phrase for this wallet', 'pass');

            try {
                $walletOpened = AWallet::setActive($wallet, $passPhrase);
            } catch (Exception $e) {
                $this->error("Could not open this wallet");
            }
        } while (!$walletOpened);
    }
}
