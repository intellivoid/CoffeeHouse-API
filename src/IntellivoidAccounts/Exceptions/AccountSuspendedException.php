<?php

    namespace IntellivoidAccounts\Exceptions;

    use Exception;
    use IntellivoidAccounts\Abstracts\ExceptionCodes;

    /**
     * Class AccountSuspendedException
     * @package IntellivoidAccounts\Exceptions
     */
    class AccountSuspendedException extends Exception
    {
        /**
         * AccountSuspendedException constructor.
         */
        public function __construct()
        {
            parent::__construct('The account has been suspended', ExceptionCodes::AccountSuspendedException, null);
        }
    }