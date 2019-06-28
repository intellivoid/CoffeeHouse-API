<?php

    namespace IntellivoidAccounts\Exceptions;

    use Exception;
    use IntellivoidAccounts\Abstracts\ExceptionCodes;

    /**
     * Class InvalidSearchMethodException
     * @package IntellivoidAccounts\Exceptions
     */
    class InvalidSearchMethodException extends Exception
    {
        /**
         * InvalidSearchMethodException constructor.
         */
        public function __construct()
        {
            parent::__construct('The given search method is invalid for the method', ExceptionCodes::InvalidSearchMethodException, null);
        }
    }