<?php


    namespace IntellivoidAccounts\Exceptions;

    use Exception;
    use IntellivoidAccounts\Abstracts\ExceptionCodes;

    /**
     * Class InsufficientFundsException
     * @package IntellivoidAccounts\Exceptions
     */
    class InsufficientFundsException extends Exception
    {
        /**
         * InsufficientFundsException constructor.
         */
        public function __construct()
        {
            parent::__construct("The account has insufficient funds to process this transaction", ExceptionCodes::InsufficientFundsException, null);
        }
    }