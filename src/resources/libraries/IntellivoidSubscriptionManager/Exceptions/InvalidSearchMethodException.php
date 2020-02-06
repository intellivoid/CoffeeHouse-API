<?php

    namespace IntellivoidSubscriptionManager\Exceptions;

    use Exception;

    /**
     * Class InvalidSearchMethodException
     * @package IntellivoidSubscriptionManager\Exceptions
     */
    class InvalidSearchMethodException extends Exception
    {
        /**
         * InvalidSearchMethodException constructor.
         */
        public function __construct()
        {
            parent::__construct('The given search method is invalid for the method', 0, null);
        }
    }