<?php

    namespace IntellivoidAccounts\Exceptions;

    use Exception;
    use IntellivoidAccounts\Abstracts\ExceptionCodes;

    /**
     * Class AccountNotFoundException
     * @package IntellivoidAccounts\Exceptions
     */
    class AccountNotFoundException extends Exception
    {
        /**
         * AccountNotFoundException constructor.
         */
        public function __construct()
        {
            parent::__construct('The requested account was not found', ExceptionCodes::AccountNotFoundException, null);
        }
    }