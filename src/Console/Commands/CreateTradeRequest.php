<?php

namespace IODigital\ABlockLaravel\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use AWallet;
use IODigital\ABlockPHP\Exceptions\PassPhraseNotSetException;
use IODigital\ABlockPHP\Exceptions\NameNotUniqueException;
use Exception;
use IODigital\ABlockLaravel\Console\Traits\UserWallets;

class CreateTradeRequest extends Command
{
    use UserWallets;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ablock:create-trade-request';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This is a command that creates a trade request between 2 addresses';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $senderWallet = $this->openWallet("Email address of initiator?");
        $selectedAssetsToSend = $this->assetsSelect();
        $myAddress = $this->keypairSelect($senderWallet)->address;

        $otherPartyAddress = $this->promptForNonEmptyString("What is the address to send your assets to?");
        $receiveHash = $this->promptForNonEmptyString("What is the hash of the asset you wish to recieve?");
        $receiveQty = $this->promptForNonEmptyString("How many $receiveHash you wish to recieve?");

        $transaction = AWallet::createTradeRequest(
            myAddress: $myAddress,
            myAsset: AWallet::getAssetObject(
                amount: $selectedAssetsToSend['qty'],
                hash: $selectedAssetsToSend['name'],
            ),
            otherPartyAddress: $otherPartyAddress,
            otherPartyAsset: AWallet::getAssetObject(
                amount: $receiveQty,
                hash: $receiveHash,
            )
        );
    }
}
