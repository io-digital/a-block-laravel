<?php

namespace IODigital\ABlockLaravel\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use AWallet;
use IODigital\ABlockPHP\Exceptions\PassPhraseNotSetException;
use IODigital\ABlockPHP\Exceptions\NameNotUniqueException;
use Exception;

class CreateKeypairForWallet extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ablock:create-keypair-for-wallet';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This is a command that creates a keypair for an existing wallet';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        do {
            $email = $this->ask("What is the user's email address?", 'bob@test.com');
            $user = User::where('email', $email)->first();

            if(!$user) {
                $this->error('User not found, please try again');
            }
        } while (!!$user === false);

        $wallets = $user->aBlockWallets()->orderBy('default', 'DESC')->get();

        if(!$wallets->count()) {
            $this->error('User does not have a wallet');
            return;
        }

        $walletName = $this->choice(
            'Which wallet is this keypair for?',
            $wallets->map(fn($item) => $item->name)->toArray(),
            0,
            $maxAttempts = null,
            $allowMultipleSelections = false
        );

        $wallet = $wallets->where('name', $walletName)->first();
        $keyPair = null;

        do {
            try {
                if(!isset($name) || !$name) {
                    $name = $this->ask('Please enter a name for your wallet', '');
                }

                $keyPair = AWallet::createKeypair($name);
            } catch (NameNotUniqueException $e) {
                $this->error('There is already a keypair for this wallet with that name');
            } catch (Exception $e) {
                $this->error($e->getMessage());
            }
        } while (!!$keyPair === false);

        $this->line("Keypair '$name' created for wallet '$walletName'");
    }
}
