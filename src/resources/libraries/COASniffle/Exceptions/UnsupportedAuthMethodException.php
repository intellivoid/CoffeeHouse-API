<?php


    namespace COASniffle\Exceptions;


    use Exception;

    /**
     * Class UnsupportedAuthMethodException
     * @package COASniffle\Exceptions
     */
    class UnsupportedAuthMethodException extends Exception
    {
        /**
         * UnsupportedAuthMethodException constructor.
         */
        public function __construct()
        {
            parent::__construct("The library does not support the requested auth method", 0, null);
        }
    }