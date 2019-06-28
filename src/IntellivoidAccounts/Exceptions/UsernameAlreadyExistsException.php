<?php

    namespace IntellivoidAccounts\Exceptions;

    use Exception;
    use IntellivoidAccounts\Abstracts\ExceptionCodes;

    /**
     * Class UsernameAlreadyExistsException
     * @package IntellivoidAccounts\Exceptions
     */
    class UsernameAlreadyExistsException extends Exception
    {
        /**
         * UsernameAlreadyExistsException constructor.
         */
        public function __construct()
        {
            parent::__construct('The username already exists', ExceptionCodes::UsernameAlreadyExistsException, null);
        }
    }