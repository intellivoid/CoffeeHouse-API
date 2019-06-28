<?php

    namespace IntellivoidAccounts\Exceptions;

    use Exception;
    use IntellivoidAccounts\Abstracts\ExceptionCodes;

    /**
     * Class IncorrectLoginDetailsException
     * @package IntellivoidAccounts\Exceptions
     */
    class IncorrectLoginDetailsException extends Exception
    {
        /**
         * IncorrectLoginDetailsException constructor.
         */
        public function __construct()
        {
            parent::__construct('The Username, Email or Password is incorrect', ExceptionCodes::IncorrectLoginDetailsException, null);
        }
    }