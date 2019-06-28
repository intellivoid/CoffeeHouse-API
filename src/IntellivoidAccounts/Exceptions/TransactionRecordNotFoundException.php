<?php


    namespace IntellivoidAccounts\Exceptions;

    use Exception;
    use IntellivoidAccounts\Abstracts\ExceptionCodes;

    /**
     * Class TransactionRecordNotFoundException
     * @package IntellivoidAccounts\Exceptions
     */
    class TransactionRecordNotFoundException extends Exception
    {
        /**
         * TransactionRecordNotFoundException constructor.
         */
        public function __construct()
        {
            parent::__construct("The transaction record was not found", ExceptionCodes::TransactionRecordNotFoundException, null);
        }
    }