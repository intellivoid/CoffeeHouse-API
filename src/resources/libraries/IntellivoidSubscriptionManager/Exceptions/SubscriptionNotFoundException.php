<?php


    namespace IntellivoidSubscriptionManager\Exceptions;


    use Exception;

    /**
     * Class SubscriptionNotFoundException
     * @package IntellivoidSubscriptionManager\Exceptions
     */
    class SubscriptionNotFoundException extends Exception
    {
        /**
         * SubscriptionNotFoundException constructor.
         */
        public function __construct()
        {
            parent::__construct("The subscription was not found in the database", 0, null);
        }
    }