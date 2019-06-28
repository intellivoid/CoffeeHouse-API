<?php


    namespace IntellivoidAccounts\Exceptions;

    use Exception;
    use IntellivoidAccounts\Abstracts\ExceptionCodes;

    /**
     * Class InvalidVendorException
     * @package IntellivoidAccounts\Exceptions
     */
    class InvalidVendorException extends Exception
    {
        /**
         * InvalidVendorException constructor.
         */
        public function __construct()
        {
            parent::__construct("The given vendor name cannot be empty or greater than 200 characters", ExceptionCodes::InvalidVendorException, null);
        }
    }