<?php

    namespace IntellivoidAccounts\Exceptions;
    use Exception;
    use IntellivoidAccounts\Abstracts\ExceptionCodes;

    /**
     * Class ConfigurationNotFoundException
     * @package IntellivoidAccounts\Exceptions
     */
    class ConfigurationNotFoundException extends Exception
    {
        /**
         * ConfigurationNotFoundException constructor.
         */
        public function __construct()
        {
            parent::__construct('The configuration file for the OpenBlu library was not found', ExceptionCodes::ConfigurationNotFoundException, null);
        }
    }