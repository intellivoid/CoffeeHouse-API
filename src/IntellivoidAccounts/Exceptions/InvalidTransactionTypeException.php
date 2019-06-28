<?php


    namespace IntellivoidAccounts\Exceptions;

    use Exception;
    use IntellivoidAccounts\Abstracts\ExceptionCodes;

    /**
     * Class InvalidTransactionTypeException
     * @package IntellivoidAccounts\Exceptions
     */
    class InvalidTransactionTypeException extends Exception
    {
        /**
         * InvalidTransactionTypeException constructor.
         */
        public function __construct()
        {
            parent::__construct("The given transaction type is invalid", ExceptionCodes::InvalidTransactionTypeException, null);
        }
    }