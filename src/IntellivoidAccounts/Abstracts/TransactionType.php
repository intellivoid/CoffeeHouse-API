<?php


    namespace IntellivoidAccounts\Abstracts;

    /**
     * Class TransactionType
     * @package IntellivoidAccounts\Abstracts
     */
    abstract class TransactionType
    {
        const Payment = 0;

        const SubscriptionPayment = 1;

        const Deposit = 2;

        const Withdraw = 3;

        const Refund = 4;
    }