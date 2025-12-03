<?php

declare(strict_types=1);

namespace Kickback\Backend\Controllers;

use Exception;
use Kickback\Backend\Models\Enums\CurrencyCode;
use Kickback\Backend\Models\Response;
use Kickback\Backend\Models\Transaction;
use Kickback\Backend\Views\vAccount;
use Kickback\Backend\Views\vLoot;
use Kickback\Backend\Views\vRecordId;
use Kickback\Backend\Views\vTransaction;
use Kickback\Backend\Views\vTransactionComponent;
use Kickback\Services\Database;

class TransactionController
{

    public static function getTransactionBetweenAccounts(vRecordId $firstAccount, vRecordId $secondAccount) : Response
    {
        $resp = new Response(false, "unkown error in getting transaction between accounts");

        $sql = "SELECT 
            t.ctime,
            t.crand,
            t.complete,
            t.void,
            t.`description`,
            t.transaction_type,
            t.ref_first_account_ctime,
            t.ref_first_account_crand,
            t.ref_second_account_ctime,
            t.ref_second_account_crand,
            t.comp.ref_transferred,
            comp.ctime as comp_ctime,
            comp.crand as comp_crand,
            comp.ref_transferred_from_ctime,
            comp.ref_transferred_from_crand,
            comp.ref_transferred_to_ctime,
            comp.ref_transferred_to_crand,
            comp.amount,
            comp.loot_id,
            comp.currency_code
        FROM transaction_component comp
        JOIN `transaction` t ON comp.ref_transaction_ctime = t.ctime AND comp.ref_transaction_crand = t.crand
        WHERE (t.ref_first_account_crand = ? OR t.ref_second_account_crand = ?) AND (t.ref_first_account_crand = ? OR t.ref_second_account_crand = ?)
        ORDER BY t.ctime, t.crand;";

        $params = [$firstAccount->crand, $firstAccount->crand,$secondAccount->crand, $secondAccount->crand];

        try
        {
            $result = Database::executeSqlQuery($sql, $params);

            if(!$result) throw new Exception("Failed to get all transaciton components with transaction header information from database");

            $transactions = [];

            $currentTransaction = null;

            /**
             * This populates the transactions array with the records from the database.
             * Because the SELECT query orders the records by the transactions Id, we don't have to
             * keep track of the ones we've already processed, only when we encounter the next grouping
             * of transaction data. Once we detect we're in the next grouping we can push the last transaction
             * we had been populating onto the transactions array and reassign the current transaction to the
             * information of the new grouping of transaction data
             */
            while($row = $result->fetch_assoc())
            {
                //When entering the while loop for the first time, populate the current transaction with the rows information
                if(is_null($currentTransaction))
                {
                    $currentTransaction = new vTransaction($row["ctime"], $row["crand"]);
                    $currentTransaction->complete = $row["complete"];
                    $currentTransaction->void = $row["void"];
                    $currentTransaction->description = $row["description"];
                    $currentTransaction->type = $row["type"];
                    $currentTransaction->firstAccount = new vAccount($row["ref_first_account_ctime"], $row["ref_first_account_crand"]);
                    $currentTransaction->secondAccount = new vAccount($row["ref_second_account_ctime"], $row["ref_second_account_crand"]);
                }

                //Check if the current transaction matches the transaction Id for the current row. If it doesn't, reassign the current transaction
                if($currentTransaction->ctime != $row["ctime"] || $currentTransaction->crand != $row["crand"])
                {
                    array_push($transactions, $currentTransaction);

                    $currentTransaction = new vTransaction($row["ctime"], $row["crand"]);
                    $currentTransaction->complete = $row["complete"];
                    $currentTransaction->void = $row["void"];
                    $currentTransaction->description = $row["description"];
                    $currentTransaction->type = $row["type"];
                    $currentTransaction->firstAccount = new vAccount($row["ref_first_account_ctime"], $row["ref_first_account_crand"]);
                    $currentTransaction->secondAccount = new vAccount($row["ref_second_account_ctime"], $row["ref_second_account_crand"]);
                }

                //Add the component to the current transaction
                $component = new vTransactionComponent();
                $component->transaction = $currentTransaction;
                $component->fromAccount = new vAccount($row["ref_transferred_from_ctime"],$row["ref_transferred_from_crand"]);
                $component->toAccount = new vAccount($row["ref_transferred_to_ctime"],$row["ref_transferred_to_crand"]);
                $component->amount = $row["amount"];
                $component->loot = is_null($row["loot_id"]) ? null : new vLoot('', $row["loot_id"]);
                $component->currencyCode = is_null($row["currency_code"]) ? null : CurrencyCode::from($row["currency_code"]);

                $currentTransaction->addComponent($component);
            }

            $resp->success = true;
            $resp->message = "Retreived transactions between accounts";
            $resp->data = $transactions;
        }
        catch(Exception $e)
        {
            throw new Exception("exception caught while getting transaction between accounts : $e");
        }

        return $resp;
    }

    public static function getTransactionInvolvingAccount(vRecordId $accountId) : Response
    {
        $resp = new Response(false, "unkown error in getting transaction involving account");

        $sql = "SELECT 
            t.ctime,
            t.crand,
            t.complete,
            t.void,
            t.`description`,
            t.transaction_type,
            t.ref_first_account_ctime,
            t.ref_first_account_crand,
            t.ref_second_account_ctime,
            t.ref_second_account_crand,
            t.comp.ref_transferred,
            comp.ctime as comp_ctime,
            comp.crand as comp_crand,
            comp.ref_transferred_from_ctime,
            comp.ref_transferred_from_crand,
            comp.ref_transferred_to_ctime,
            comp.ref_transferred_to_crand,
            comp.amount,
            comp.loot_id,
            comp.currency_code
        FROM transaction_component comp
        JOIN `transaction` t ON comp.ref_transaction_ctime = t.ctime AND comp.ref_transaction_crand = t.crand
        WHERE t.ref_first_account_crand = ? OR t.ref_second_account_crand = ?
        ORDER BY t.ctime, t.crand;";

        $params = [$accountId->crand, $accountId->crand];

        try
        {
            $result = Database::executeSqlQuery($sql, $params);

            if(!$result) throw new Exception("Failed to get all transaciton components with transaction header information from database");

            $transactions = [];

            $currentTransaction = null;

            /**
             * This populates the transactions array with the records from the database.
             * Because the SELECT query orders the records by the transactions Id, we don't have to
             * keep track of the ones we've already processed, only when we encounter the next grouping
             * of transaction data. Once we detect we're in the next grouping we can push the last transaction
             * we had been populating onto the transactions array and reassign the current transaction to the
             * information of the new grouping of transaction data
             */
            while($row = $result->fetch_assoc())
            {
                //When entering the while loop for the first time, populate the current transaction with the rows information
                if(is_null($currentTransaction))
                {
                    $currentTransaction = new vTransaction($row["ctime"], $row["crand"]);
                    $currentTransaction->complete = $row["complete"];
                    $currentTransaction->void = $row["void"];
                    $currentTransaction->description = $row["description"];
                    $currentTransaction->type = $row["type"];
                    $currentTransaction->firstAccount = new vAccount($row["ref_first_account_ctime"], $row["ref_first_account_crand"]);
                    $currentTransaction->secondAccount = new vAccount($row["ref_second_account_ctime"], $row["ref_second_account_crand"]);
                }

                //Check if the current transaction matches the transaction Id for the current row. If it doesn't, reassign the current transaction
                if($currentTransaction->ctime != $row["ctime"] || $currentTransaction->crand != $row["crand"])
                {
                    array_push($transactions, $currentTransaction);

                    $currentTransaction = new vTransaction($row["ctime"], $row["crand"]);
                    $currentTransaction->complete = $row["complete"];
                    $currentTransaction->void = $row["void"];
                    $currentTransaction->description = $row["description"];
                    $currentTransaction->type = $row["type"];
                    $currentTransaction->firstAccount = new vAccount($row["ref_first_account_ctime"], $row["ref_first_account_crand"]);
                    $currentTransaction->secondAccount = new vAccount($row["ref_second_account_ctime"], $row["ref_second_account_crand"]);
                }

                //Add the component to the current transaction
                $component = new vTransactionComponent();
                $component->transaction = $currentTransaction;
                $component->fromAccount = new vAccount($row["ref_transferred_from_ctime"],$row["ref_transferred_from_crand"]);
                $component->toAccount = new vAccount($row["ref_transferred_to_ctime"],$row["ref_transferred_to_crand"]);
                $component->amount = $row["amount"];
                $component->loot = is_null($row["loot_id"]) ? null : new vLoot('', $row["loot_id"]);
                $component->currencyCode = is_null($row["currency_code"]) ? null : CurrencyCode::from($row["currency_code"]);

                $currentTransaction->addComponent($component);
            }

            $resp->success = true;
            $resp->message = "Retreived transactions invloving account";
            $resp->data = $transactions;
        }
        catch(Exception $e)
        {
            throw new Exception("exception caught while getting transaction invloving account : $e");
        }

        return $resp;
    }

    public static function insertTransaction(Transaction $transaction) : void
    {
        $params = [
            $transaction->ctime,
            $transaction->crand,
            $transaction->complete,
            $transaction->void,
            $transaction->description,
            $transaction->type,
            $transaction->firstAccount->ctime,
            $transaction->firstAccount->crand,
            $transaction->secondAccount->ctime,
            $transaction->secondAccount->crand
        ];
        
        $insertTransactionSql = "INSERT INTO `transaction` (
            ctime,
            crand,
            complete,
            void,
            `description`,
            transaction_type,
            ref_first_account_ctime,
            ref_first_account_crand,
            ref_second_account_ctime,
            ref_second_account_crand
            ) VALUES (?,?,?,?,?,?,?,?,?,?)";

        $compnentParams = [];
        $valueClause = static::createValueClauseForInsertTransactionForComponents($transaction, $compnentParams);
        $insertComponentsSql = "INSERT INTO transaction_compnents (
        ctime,
        crand,
        ref_transaction_ctime,
        ref_transaction_crand,
        ref_transferred_from_ctime,
        ref_transferred_from_crand,
        ref_transferred_to_ctime,
        ref_transferred_to_crand,
        amount,
        loot_id,
        currency_code
        ) VALUES $valueClause";

        try
        {
            $conn = Database::getConnection();
            $conn->begin_transaction();

                $result = Database::executeSqlQuery($insertTransactionSql, $params);
                if(!$result) throw new Exception("Failed to insert main transaction record");

                $result = Database::executeSqlQuery($insertComponentsSql, $compnentParams);
                if(!$result) throw new Exception("Failed to insert transaction component records");

            $conn->commit();
        }
        catch(Exception $e)
        {
            $conn->rollback();
            throw new Exception("Exception thrown while trying to insert transaction : $e");
        }
    }

    private static function createValueClauseForInsertTransactionForComponents(Transaction $transaction, array &$params) : string
    {
        $valueClause = "";

        $components = $transaction->getComponents();

        for($i = 0; $i < count($components); $i++)
        {
            $comp = $components[$i];

            if($i != 0)
            {
                $valueClause .= ", ";
            }

            $valueClause .= "(?,?,?,?,?,?,?,?,?,?,?)";

            $currencyCode = is_null($comp->currencyCode) ? '' : $comp->currencyCode->value;

            array_push($params,
            $comp->ctime,
            $comp->crand,
            $comp->transaction->ctime,
            $comp->transaction->crand,
            $comp->toAccount->ctime,
            $comp->toAccount->crand,
            $comp->fromAccount->ctime,
            $comp->fromAccount->crand,
            $comp->amount,
            $comp->loot->crand,
            $currencyCode
            );
        }
        return $valueClause;
    }
}

?>