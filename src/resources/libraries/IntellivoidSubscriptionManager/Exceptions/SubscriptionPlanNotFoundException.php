<?php


    namespace IntellivoidSubscriptionManager\Exceptions;


    use Exception;

    /**
     * Class SubscriptionPlanNotFoundException
     * @package IntellivoidSubscriptionManager\Exceptions
     */
    class SubscriptionPlanNotFoundException extends Exception
    {
        /**
         * SubscriptionPlanNotFoundException constructor.
         */
        public function __construct()
        {
            parent::__construct("The Subscription Plan was not found in the database", 0, null);
        }
    }