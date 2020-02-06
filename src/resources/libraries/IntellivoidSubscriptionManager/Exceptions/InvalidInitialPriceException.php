<?php


    namespace IntellivoidSubscriptionManager\Exceptions;


    use Exception;

    class InvalidInitialPriceException extends Exception
    {
        /**
         * InvalidInitialPriceException constructor.
         */
        public function __construct()
        {
            parent::__construct("The initial price cannot be lower than 0", 0, null);
        }
    }