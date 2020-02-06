<?php


    namespace IntellivoidSubscriptionManager\Exceptions;


    use Exception;

    class InvalidCyclePriceShareException extends Exception
    {
        /**
         * InvalidCycleShareException constructor.
         */
        public function __construct()
        {
            parent::__construct("The given cycle share is invalid, it cannot be less than 0 nor greater than the current cycle share", 0, null);
        }
    }