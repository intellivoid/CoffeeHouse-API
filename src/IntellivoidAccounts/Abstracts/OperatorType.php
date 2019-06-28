<?php


    namespace IntellivoidAccounts\Abstracts;

    /**
     * Class OperatorType
     * @package IntellivoidAccounts\Abstracts
     */
    abstract class OperatorType
    {
        /**
         * Adds the given amount to the Account
         */
        const Deposit = 0;

        /**
         * Removes the given amount from the Account
         */
        const Withdraw = 1;

        /**
         * No money was transferred
         */
        const None = 3;
    }