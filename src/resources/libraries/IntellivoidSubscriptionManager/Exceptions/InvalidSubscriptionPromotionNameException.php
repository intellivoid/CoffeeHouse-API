<?php


    namespace IntellivoidSubscriptionManager\Exceptions;


    use Exception;

    /**
     * Class InvalidSubscriptionPromotionNameException
     * @package IntellivoidSubscriptionManager\Exceptions
     */
    class InvalidSubscriptionPromotionNameException extends Exception
    {
        /**
         * InvalidSubscriptionPromotionNameException constructor.
         */
        public function __construct()
        {
            parent::__construct("The subscription's promotion code is invalid", 0, null);
        }
    }