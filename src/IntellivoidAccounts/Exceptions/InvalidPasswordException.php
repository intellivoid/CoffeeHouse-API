<?php

    namespace IntellivoidAccounts\Exceptions;

    use Exception;
    use IntellivoidAccounts\Abstracts\ExceptionCodes;

    /**
     * Class InvalidPasswordException
     * @package IntellivoidAccounts\Exceptions
     */
    class InvalidPasswordException extends Exception
    {
        /**
         * InvalidPasswordException constructor.
         */
        public function __construct()
        {
            parent::__construct('The given Password is invalid', ExceptionCodes::InvalidPasswordException, null);
        }
    }