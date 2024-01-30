<?php

namespace IODigital\ABlockLaravel\Console\Commands;

use AWallet;
use Exception;
use Illuminate\Console\Command;
use IODigital\ABlockLaravel\Console\Traits\UserWallets;
use IODigital\ABlockPHP\Exceptions\NameNotUniqueException;
use IODigital\ABlockPHP\Exceptions\PassPhraseNotSetException;

class CreateWalletForUser extends Command
{
    use UserWallets;
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
        $user = $this->findUserByEmail();

        do {
            try {
                $name = $this->promptForNonEmptyString('Please enter a name for your wallet');
                $passPhrase = $this->promptForNonEmptyString('Please enter a pass phrase for the wallet');

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

        if ($this->confirm('Do you wish to create a default keypair for this wallet?', 'yes')) {
            $keyPair = AWallet::createKeypair("default");
        }

        $this->line("{$walletAndSeedPhrase['wallet']->name} seed phrase: {$walletAndSeedPhrase['seedPhrase']}");

        if(isset($keyPair)) {
            $this->line("Default keypair created");
        }
    }
}
