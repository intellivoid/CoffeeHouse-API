<?php


    namespace IntellivoidSubscriptionManager\Exceptions;


    use Exception;

    /**
     * Class SubscriptionPromotionNotFoundException
     * @package IntellivoidSubscriptionManager\Exceptions
     */
    class SubscriptionPromotionNotFoundException extends Exception
    {
        /**
         * SubscriptionPromotionNotFoundException constructor.
         */
        public function __construct()
        {
            parent::__construct("The subscription promotion was not found", 0, null);
        }
    }