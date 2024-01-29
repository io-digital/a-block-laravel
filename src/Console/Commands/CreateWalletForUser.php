<?php

namespace IODigital\ABlockLaravel\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use AWallet;
use IODigital\ABlockPHP\Exceptions\PassPhraseNotSetException;
use IODigital\ABlockPHP\Exceptions\NameNotUniqueException;
use Exception;

class CreateWalletForUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ablock:create-wallet-for-user';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This is a command that creates a wallet for an existing user';

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

        $walletAndSeedPhrase = null;

        do {
            try {
                if(!isset($name) || !$name) {
                    $name = $this->ask('Please enter a name for your wallet', '');
                }

                if(!isset($passPhrase) || !$passPhrase) {
                    $passPhrase = $this->ask('Please enter a pass phrase for your wallet', '');
                }

                $walletAndSeedPhrase = AWallet::create(
                    name: $name,
                    passPhrase: $passPhrase,
                    owner: $user
                );
            } catch (PassPhraseNotSetException $e) {
                $this->error('Passphrase cannot be empty, please try again');
            } catch (NameNotUniqueException $e) {
                $this->error('There is already a wallet for this user with that name');
            } catch (Exception $e) {
                $this->error($e->getMessage());
            }
        } while (!!$walletAndSeedPhrase === false);

        //also create a keypair for this wallet
        $keyPair = AWallet::createKeypair("$name Keypair");

        $this->line("{$walletAndSeedPhrase['wallet']->name} seed phrase: {$walletAndSeedPhrase['seedPhrase']}");
    }
}
