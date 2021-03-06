<?php

use App\Models\Account;
use App\Models\ProjectedJournal;
use App\Models\Journal;
use App\Models\JournalLine;
use App\User;
use Carbon\Carbon;

trait CreatesModels
{
    /**
     * The Account being used for test purposes
     * @var App\Models\Account
     **/
    private $account;

    /**
     * The journal being used for test purposes
     * @var App\Models\Journal
     **/

    private $journal;
    /**
     * The journal line being used for test purposes
     * @var App\Models\JournalLine
     **/
    private $journalLine;

    /**
     * The ProjectedJournal being used for test purposes
     * @var App\Models\ProjectedJournal
     **/
    private $projectedJournal;

    /**
     * The User with admin level permissions being used for test purposes
     * @var App\Models\User
     **/
    private $user;

    private $bankAccount;

    private $salesAccount;

    private $accountsReceivableAccount;

    /**
     * Creates an Account model and returns it
     *
     * @param array $attributes
     * @return App\Models\Account
     **/
    public function createAccount($attributes = [])
    {
        return factory(Account::class)->create($attributes);
    }

    /**
     * Creates an accounts receivable Account model and returns it
     *
     * @param array $attributes
     * @return App\Models\Account
     **/
    public function createAccountsReceivableAccount()
    {
        return factory(Account::class)->create([
            'code'              => 610,
            'name'              => 'Accounts Receivable',
            'type'              => 'CURRENT',
            'status'            => 'ACTIVE',
            'tax_type'          => 'BASEXCLUDED',
            'is_system_account' => 1,
            'currency_code'     => 'AUD',
        ]);
    }

    /**
     * Creates a bank Account model and returns it
     *
     * @param array $attributes
     * @return App\Models\Account
     **/
    public function createBankAccount()
    {
        return factory(Account::class)->create([
            'code'              => 'TESTBANK',
            'name'              => 'Test Bank Account',
            'type'              => 'BANK',
            'status'            => 'ACTIVE',
            'bank_account_type' => 'BANK',
            'tax_type'          => 'BASEXCLUDED',
            'is_system_account' => 0,
            'currency_code'     => 'AUD',
        ]);
    }

    /**
     * Creates all models required for some income
     **/
    public function createIncome($attributes)
    {
        $amount = $attributes['amount'] ?? 1000;
        $date = $attributes['date'] ?? app(\Faker\Generator::class)
            ->datetime
            ->format('Y-m-d h:m:s');

        $accRecJournal = $this->getTestObject('journal', [
            'source_type' => 'ACCREC',
            'date'        => $date
        ]);

        $this->createJournalLine([
            'journal_id'      => $accRecJournal->id,
            'account_xero_id' => $this->salesAccount()->xero_id,
            'gross_amount'    => -$amount,
            'net_amount'      => -$amount,
            'tax_amount'      => 0
        ]);
        $this->createJournalLine([
            'journal_id'      => $accRecJournal->id,
            'account_xero_id' => $this->accountsReceivableAccount()->xero_id,
            'gross_amount'    => $amount,
            'net_amount'      => $amount,
            'tax_amount'      => 0
        ]);

        $accRecPaymentJournal = $this->createJournal([
            'source_type' => 'ACCRECPAYMENT',
            'date'        => $date
        ]);

        $this->createJournalLineForAccountInJournal($this->bankAccount(), $accRecPaymentJournal, [
            'gross_amount'    => $amount,
            'net_amount'      => $amount,
            'tax_amount'      => 0
        ]);
        $this->createJournalLineForAccountInJournal($this->accountsReceivableAccount(), $accRecPaymentJournal, [
            'gross_amount'    => -$amount,
            'net_amount'      => -$amount,
            'tax_amount'      => 0
        ]);
    }

    public function createAccountsReceivablePayment($attributes)
    {
        $accRecPaymentJournal = $this->createJournal([
            'source_type' => 'ACCRECPAYMENT',
            'date'        => $attributes['date'] ?? Carbon::now()->format('Y-m-d h:i:s'),
        ]);

        $this->createJournalLineForAccountInJournal($this->bankAccount(), $accRecPaymentJournal, [
            'gross_amount'    => $attributes['gross_amount'],
            'net_amount'      => $attributes['net_amount'] ?? $attributes['gross_amount'],
            'tax_amount'      => $attributes['tax_amount'] ?? 0,
        ]);

        $this->createJournalLineForAccountInJournal($this->accountsReceivableAccount(), $accRecPaymentJournal, [
            'gross_amount'    => -$attributes['gross_amount'],
            'net_amount'      => -($attributes['net_amount'] ?? $attributes['gross_amount']),
            'tax_amount'      => -($attributes['tax_amount'] ?? 0),
        ]);

        return $accRecPaymentJournal;
    }

    /**
     * Creates a Journal model and returns it
     * @return App\Models\Journal
     **/
    public function createJournal($attributes = [])
    {
        return factory(App\Models\Journal::class)->create($attributes);
    }

    /**
     * Creates a JournalLine model and returns it
     * @return App\Models\JournalLine
     **/
    public function createJournalLine($attributes = [])
    {
        return $this->createJournalLineForAccountInJournal($this->getTestObject('account'), $journal = $this->getTestObject('journal'), $attributes);
    }

    public function createJournalLineForAccountInJournal(Account $account, Journal $journal, $attributes = [])
    {
        return factory(App\Models\JournalLine::class)->create(array_merge([
            'journal_id'      => $journal->id,
            'account_xero_id' => $account->xero_id,
            'account_type'    => $account->type,
            'account_name'    => $account->name,
        ], $attributes));
    }

    /**
     * Creates a sales Account model and returns it
     *
     * @param array $attributes
     * @return App\Models\Account
     **/
    public function createSalesAccount()
    {
        return factory(Account::class)->create([
            'code'              => 200,
            'name'              => 'Sales',
            'type'              => 'REVENUE',
            'status'            => 'ACTIVE',
            'tax_type'          => 'OUTPUT',
            'is_system_account' => 0,
            'currency_code'     => 'AUD',
        ]);
    }

    /**
     * Creates a Projected Journal model and returns it
     *
     * @param array $attributes
     * @return App\Models\ProjectedJournal
     **/
    public function createProjectedJournal($attributes = [])
    {
        return factory(ProjectedJournal::class)->create($attributes);
    }

    /**
     * Creates a user, logs them in and returns the user model
     *
     * @param $attributes
     * @return App\User
     **/
    public function createAndLoginUser($attributes = [])
    {
        $user = $this->getTestObject('user', $attributes);
        \Auth::login($user);

        return $user;
    }

    /**
     * Creates a gst Account model and returns it
     *
     * @param array $attributes
     * @return App\Models\Account
     **/
    public function createGSTAccount($attributes = [])
    {
        return factory(Account::class)->create([
            'code'              => 820,
            'name'              => 'GST',
            'type'              => 'CURRLIAB',
            'status'            => 'ACTIVE',
            'tax_type'          => 'BASEXCLUDED',
            'is_system_account' => 1
        ]);
    }

    /**
     * Creates a pair of balanced and opposing transactions which are not reconciled
     * and returns them in an array with the 'credit' key containing to the positive
     * transaction and the 'debit' key containing the negative transaction.
     *
     * @return array
     **/
    public function createTransactionPair()
    {
        // Create account for credit transaction
        $creditAccount = $this->createAccount();

        // Create credit transaction
        $creditTransaction = $this->createTransaction([
            'account_id' => $creditAccount->id,
        ]);

        // Ensure transaction is positive by taking it's absolute value
        $creditTransaction->amount = abs($creditTransaction->amount);
        $creditTransaction->save();

        // Create account for debit transaction
        $debitAccount = $this->createAccount();

        // Create debit transaction
        $debitTransaction = $this->createTransaction([
            'account_id' => $debitAccount,
            'amount'     => -$creditTransaction->amount
        ]);

        return [
            'credit' => $creditTransaction,
            'debit'  => $debitTransaction
        ];
    }

    /**
     * Creates a random user and returns it
     *
     * @param array $attributes
     * @return \App\Models\User
     **/
    public function createUser($attributes = [])
    {
        return factory(App\User::class)->create($attributes);
    }

    /**
     * Returns the given attribute if it is set. If it is not set, thet set method will be returned.
     *
     * @param string $propertyName
     * @param array $attributes
     * @return mixed
     **/
    public function getTestObject($propertyName, $attributes = [])
    {
        // If the given property has not already been set
        if (is_null($this->$propertyName)) {
            // Generate new object
            $object = $this->{'create'.ucfirst($propertyName)}($attributes);

            // Set the property as the new object
            $this->setTestObject($propertyName, $object);
        }

        return $this->$propertyName;
    }

    /**
     * Sets the given property to the given object. If none is provided, a new one will be generated.
     *
     * @param string $propertyName
     * @param mixed $object (optional)
     **/
    public function setTestObject($propertyName, $object = null)
    {
        // If no object is provided we'll just generate a new one
        if (is_null($object)) {
            // Generate new object
            $object = $this->{'create'.ucfirst($propertyName)}($attributes);
        }

        // Set object
        $this->$propertyName = $object;
    }

    public function accountsReceivableAccount($attributes = [])
    {
        return $this->getTestObject('accountsReceivableAccount', $attributes);
    }

    public function bankAccount($attributes = [])
    {
        return $this->getTestObject('bankAccount', $attributes);
    }

    public function salesAccount($attributes = [])
    {
        return $this->getTestObject('salesAccount', array_merge([
            'code'              => 200,
            'name'              => 'Sales',
            'type'              => 'REVENUE',
            'status'            => 'ACTIVE',
            'tax_type'          => 'OUTPUT',
            'is_system_account' => 0
        ]));
    }
}
