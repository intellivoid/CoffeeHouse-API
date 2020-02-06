<?php


    namespace IntellivoidSubscriptionManager\Exceptions;


    use Exception;

    class InvalidFeatureException extends Exception
    {
        /**
         * InvalidFeatureException constructor.
         */
        public function __construct()
        {
            parent::__construct("The given value must be a feature object, it presents missing values", 0, null);
        }
    }