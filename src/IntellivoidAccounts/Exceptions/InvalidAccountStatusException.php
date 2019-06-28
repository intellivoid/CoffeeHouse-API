<?php


    namespace IntellivoidAccounts\Exceptions;

    use Exception;
    use IntellivoidAccounts\Abstracts\ExceptionCodes;

    /**
     * Class InvalidAccountStatusException
     * @package IntellivoidAccounts\Exceptions
     */
    class InvalidAccountStatusException extends Exception
    {
        /**
         * InvalidAccountStatusException constructor.
         */
        public function __construct()
        {
            parent::__construct('The account status is invalid', ExceptionCodes::InvalidAccountStatusException, null);
        }
    }