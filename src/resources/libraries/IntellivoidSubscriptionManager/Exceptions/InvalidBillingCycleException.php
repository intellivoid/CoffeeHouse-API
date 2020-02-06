<?php


    namespace IntellivoidSubscriptionManager\Exceptions;


    use Exception;

    class InvalidBillingCycleException extends Exception
    {
        /**
         * InvalidBillingCycleException constructor.
         */
        public function __construct()
        {
            parent::__construct("The given billing cycle is invalid, it cannot be less than 0", 0, null);
        }
    }