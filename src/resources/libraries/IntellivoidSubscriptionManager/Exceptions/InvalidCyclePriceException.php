<?php


    namespace IntellivoidSubscriptionManager\Exceptions;


    use Exception;

    /**
     * Class InvalidCyclePriceException
     * @package IntellivoidSubscriptionManager\Exceptions
     */
    class InvalidCyclePriceException extends Exception
    {
        /**
         * InvalidCyclePriceException constructor.
         */
        public function __construct()
        {
            parent::__construct("The cycle price is invalid, it cannot be less than 0", 0, null);
        }
    }