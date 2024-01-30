<?php

namespace IODigital\ABlockLaravel\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use AWallet;
use IODigital\ABlockPHP\Exceptions\PassPhraseNotSetException;
use IODigital\ABlockPHP\Exceptions\NameNotUniqueException;
use Exception;
use IODigital\ABlockLaravel\Console\Traits\UserWallets;

class CreateItem extends Command
{
    use UserWallets;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ablock:create-item';

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

        try {
            $wallet = $this->walletSelect($user);
        } catch (Exception $e) {
            $this->error($e->getMessage());
            return;
        }

        $this->openUserWallet($wallet);
        $keyPair = $this->keypairSelect($wallet);

        $itemName = $this->promptForNonEmptyString("What is the item?", "stuff");

        do {
            $qtyResponse = $this->promptForNonEmptyString("How many items?");

            if(is_numeric($qtyResponse) && is_integer((int) $qtyResponse) && $qtyResponse > 0) {
                $qty = (int) $qtyResponse;
            } else {
                $this->error("Please enter an integer number larger than 0");
            }
        } while (!isset($qty));

        $item = AWallet::createAsset(
            keyPair: $keyPair,
            name: $itemName,
            amount: $qty
        );

        $this->line("$qty items named '$itemName' created, unique hash: {$item->getDrsTxHash()}");
    }
}
