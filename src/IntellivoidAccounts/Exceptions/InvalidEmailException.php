<?php

    namespace IntellivoidAccounts\Exceptions;

    use Exception;
    use IntellivoidAccounts\Abstracts\ExceptionCodes;

    /**
     * Class InvalidEmailException
     * @package IntellivoidAccounts\Exceptions
     */
    class InvalidEmailException extends Exception
    {
        /**
         * InvalidEmailException constructor.
         */
        public function __construct()
        {
            parent::__construct('The given Email Address is invalid', ExceptionCodes::InvalidEmailException, null);
        }
    }