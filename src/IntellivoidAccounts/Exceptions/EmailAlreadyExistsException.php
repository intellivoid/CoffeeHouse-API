<?php

    namespace IntellivoidAccounts\Exceptions;

    use Exception;
    use IntellivoidAccounts\Abstracts\ExceptionCodes;

    /**
     * Class EmailAlreadyExistsException
     * @package IntellivoidAccounts\Exceptions
     */
    class EmailAlreadyExistsException extends Exception
    {
        /**
         * EmailAlreadyExistsException constructor.
         */
        public function __construct()
        {
            parent::__construct('The Email already exists', ExceptionCodes::EmailAlreadyExistsException, null);
        }
    }