<?php


    namespace COASniffle\Exceptions;


    use Exception;
    use Throwable;

    /**
     * Class InvalidRedirectLocationException
     * @package COASniffle\Exceptions
     */
    class InvalidRedirectLocationException extends Exception
    {
        /**
         * InvalidRedirectLocationException constructor.
         */
        public function __construct()
        {
            parent::__construct("The given redirect URL is invalid", 0, null);
        }
    }