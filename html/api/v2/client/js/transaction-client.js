/**
 * Item API Component
 * Handles all item-related API calls
 */
class Transaction {
    /**
     * Get transactions for an account
     * @param {number|string} accountId - Account ID
     * @returns {Promise<Object>} API response with transaction data
     */
    static async getTransactionsForAccount(accountId) {
        if (!id) {
            throw new Error('Item ID is required');
        }

        try {
            const bodyData = {
                "ctime": '',
                "crand": accountID
            };

            const response = await fetch(`api/v2/server/transaction/get_transactions_for_account`, {
                method: 'POST',
                credentials: 'include',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(bodyData)
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }

            const data = await response.text();
            let jsonData;
            
            try {
                jsonData = JSON.parse(data);
            } catch (parseError) {
                throw new Error('Invalid JSON response from server');
            }

            if (!jsonData.success) {
                throw new Error(jsonData.message || `Failed to get transactions for account ${accountId}`);
            }

            return jsonData;

        } catch (error) {
            console.error(`Transactions.getTransactionsForAccount(${accountId}) failed:`, error);
            throw error;
        }
    }

    /**
     * Get transactions for an account
     * @param {number|string} firstAccountId - first account to get shared transactions
     * @param {number|string} secondAccountId - second account to get shared transactions
     * @returns {Promise<Object>} API response with transaction data
     */
    static async getTransactionsBetweenAccounts(firstAccountId, secondAccountId) {
        if (!firstAccountId) {
            throw new Error('firstAccountId is required');
        }

        if (!secondAccountId) {
            throw new Error('firstAccountId is required');
        }

        try {
            const bodyData = {
                "accounts":[
                    {"ctime": '', "crand": firstAccountId},
                    {"ctime": '', "crand": secondAccountId}
                ]
            };

            const response = await fetch(`api/v2/server/transaction/get_transactions_between_accounts`, {
                method: 'POST',
                credentials: 'include',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(bodyData)
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }

            const data = await response.text();
            let jsonData;
            
            try {
                jsonData = JSON.parse(data);
            } catch (parseError) {
                throw new Error('Invalid JSON response from server');
            }

            if (!jsonData.success) {
                throw new Error(jsonData.message || `Failed to get transactions between accounts ${firstAccountId} and ${secondAccountId} `);
            }

            return jsonData;

        } catch (error) {
            console.error(`Transactions.getTransactionsBetweenAccount(${accountId}) failed:`, error);
            throw error;
        }
    }
}

console.log('Transaction component loaded');
