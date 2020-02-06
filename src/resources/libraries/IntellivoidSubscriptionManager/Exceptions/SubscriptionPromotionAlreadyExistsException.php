<?php


    namespace IntellivoidSubscriptionManager\Exceptions;


    use Exception;

    class SubscriptionPromotionAlreadyExistsException extends Exception
    {
        /**
         * SubscriptionPromotionAlreadyExistsException constructor.
         */
        public function __construct()
        {
            parent::__construct("The subscription promotion already exists", 0, null);
        }
    }