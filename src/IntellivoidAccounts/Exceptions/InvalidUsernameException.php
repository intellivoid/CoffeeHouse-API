<?php

    namespace IntellivoidAccounts\Exceptions;

    use Exception;
    use IntellivoidAccounts\Abstracts\ExceptionCodes;

    /**
     * Class InvalidUsernameException
     * @package IntellivoidAccounts\Exceptions
     */
    class InvalidUsernameException extends Exception
    {
        /**
         * InvalidUsernameException constructor.
         */
        public function __construct()
        {
            parent::__construct('The username is invalid', ExceptionCodes::InvalidUsernameException, null);
        }
    }