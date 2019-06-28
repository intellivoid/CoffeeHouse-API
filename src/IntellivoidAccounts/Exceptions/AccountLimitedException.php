<?php


    namespace IntellivoidAccounts\Exceptions;

    use Exception;
    use IntellivoidAccounts\Abstracts\ExceptionCodes;

    /**
     * Class AccountLimitedException
     * @package IntellivoidAccounts\Exceptions
     */
    class AccountLimitedException extends Exception
    {
        /**
         * AccountLimitedException constructor.
         */
        public function __construct()
        {
            parent::__construct('The operation is not allowed because the account is limited', ExceptionCodes::AccountLimitedException, null);
        }
    }