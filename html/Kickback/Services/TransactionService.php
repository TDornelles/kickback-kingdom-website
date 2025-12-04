<?php
namespace Kickback\Services;

use Exception;
use Kickback\Backend\Controllers\AccountController;
use Kickback\Backend\Controllers\StoreController;
use Kickback\Backend\Controllers\TransactionController;
use Kickback\Backend\Models\Enums\CurrencyCode;
use Kickback\Backend\Models\Response;
use Kickback\Backend\Views\vAccount;
use Kickback\Backend\Views\vCart;
use Kickback\Backend\Views\vCartItem;
use Kickback\Backend\Views\vItem;
use Kickback\Backend\Views\vMedia;
use Kickback\Backend\Views\vPrice;
use Kickback\Backend\Views\vPriceComponent;
use Kickback\Backend\Views\vProduct;
use Kickback\Backend\Views\vRecordId;
use Kickback\Backend\Views\vStore;
use Kickback\Backend\Views\vTransaction;
use Kickback\Common\Primitives\Obj;
use Kickback\Services\ApiV2\Endpoint;
use LogicException;

class TransactionService
{
    private static bool $initialized = false;
    private static TransactionService $client = null;

    public static function initialize() : void
    {
        if(self::$initialized) return;

        self::$client = new TransactionService();
        self::$initialized = true;
    }

    public static function get_transactions_between_accounts(?vAccount $account, string $request_contents_json, ?Response &$resp) : int
    {
        $resp = new Response(false, "Unkown error encountered while retreiving transactions between provided accounts");

        if (is_null($account))
        {
            throw new LogicException("Account cannot be null. Require an Account Session before calling this function");
        }

        if(!$account->isSteward)
        {
            $resp->message = "Halt! Only kingdom Stewards may access these records!";
            return 403;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $endpoint_name = Endpoint::calculate_endpoint_resource_name();
            $resp = new Response(false, "$endpoint_name: Method not allowed");
            return 405;
        }

        if(empty($request_contents_json))
        {
            $resp->message = "Request body cannot be empty"; 
            return 400;
        } 

        $body = json_decode($request_contents_json);

        if(!key_exists("accounts", $body))
        {
            $resp->message = "Request body must contain the key : 'accounts'";
            return 400;
        }

        if(empty($body["accounts"]))
        {
            $resp->message = "accounts cannot be empty"; 
            return 400;
        }

        $accounts = $body["accounts"];

        TransactionService::initialize();

        $firstAccount = $accounts[0];
        $secondAccount = $accounts[1];

        $firstAccount = new vRecordId($firstAccount["ctime"], $firstAccount["crand"]);
        $secondAccount = new vRecordId($secondAccount["ctime"], $secondAccount["crand"]);

        $transactionsResp = TransactionController::getTransactionBetweenAccounts($accounts[0], $accounts[1]);

        if(!$transactionsResp->success)
        {
            $resp->message = "Failed to get transacations between accounts : $transactionsResp->message";
            return 500;
        }

        $resp->success = true;
        $resp->message = "Retreived transactions between provided accounts";
        $resp->data = $transactionsResp->data;
        return 200;
    }

    public static function get_transactions_for_account(?vAccount $account, string $request_contents_json, ?Response &$resp) : int
    {
        $resp = new Response(false, "Unkown error encountered while retreiving transactions for account", null);

        if (is_null($account))
        {
            throw new LogicException("Account cannot be null. Require an Account Session before calling this function");
        }

        if(!$account->isSteward)
        {
            $resp->message = "Halt! Only kingdom Stewards may access these records!";
            return 403;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $endpoint_name = Endpoint::calculate_endpoint_resource_name();
            $resp = new Response(false, "$endpoint_name: Method not allowed");
            return 405;
        }

        if(empty($request_contents_json))
        {
            $resp->message = "Request body cannot be empty"; 
            return 400;
        } 

        $body = json_decode($request_contents_json);

        if(!key_exists("crand", $body))
        {
            $resp->message = "Request body must contain the key : 'crand'";
            return 400;
        }

        if(empty($body["crand"]))
        {
            $resp->message = "crand cannot be empty"; 
            return 400;
        }


        TransactionService::initialize();

        $account = new vRecordId('', $body["crand"]);

        $transactionsResp = TransactionController::getTransactionInvolvingAccount($account);

        if(!$transactionsResp->success)
        {
            $resp->message = "Failed to get transacations for account : $transactionsResp->message";
            return 500;
        }

        $resp->success = true;
        $resp->message = "Retreived transactions for account";
        $resp->data = $transactionsResp->data;
        return 200;
    }
}
?>