<?php

    namespace IntellivoidAccounts\Exceptions;

    use Exception;
    use IntellivoidAccounts\Abstracts\ExceptionCodes;

    /**
     * Class InvalidLoginStatusException
     * @package IntellivoidAccounts\Exceptions
     */
    class InvalidLoginStatusException extends Exception
    {
        /**
         * InvalidLoginStatusException constructor.
         */
        public function __construct()
        {
            parent::__construct('The given Login Status code is not valid', ExceptionCodes::InvalidLoginStatusException, null);
        }
    }