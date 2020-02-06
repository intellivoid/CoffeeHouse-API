<?php


    namespace IntellivoidSubscriptionManager\Exceptions;


    use Exception;

    class InvalidInitialPriceShareException extends Exception
    {
        /**
         * InvalidInitialPriceShareException constructor.
         */
        public function __construct()
        {
            parent::__construct("The initial price share is invalid, it cannot bless than 0 nor greater than the initial price", 0, null);
        }
    }