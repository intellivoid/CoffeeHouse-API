<?php

    namespace IntellivoidAccounts\Exceptions;

    use Exception;
    use IntellivoidAccounts\Abstracts\ExceptionCodes;

    /**
     * Class InvalidIpException
     * @package IntellivoidAccounts\Exceptions
     */
    class InvalidIpException extends Exception
    {
        /**
         * InvalidIpException constructor.
         */
        public function __construct()
        {
            parent::__construct('The given IP is invalid', ExceptionCodes::InvalidIpException, null);
        }
    }