<?php


    namespace COASniffle\Exceptions;


    use Exception;

    /**
     * Class BadResponseException
     * @package COASniffle\Exceptions
     */
    class BadResponseException extends Exception
    {
        /**
         * BadResponseException constructor.
         */
        public function __construct()
        {
            parent::__construct("The response given from the server contains malformed data that cannot be parsed", 0, null);
        }
    }