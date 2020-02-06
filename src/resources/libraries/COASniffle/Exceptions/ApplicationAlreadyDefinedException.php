<?php


    namespace COASniffle\Exceptions;


    use Exception;

    /**
     * Class ApplicationAlreadyDefinedException
     * @package COASniffle\Exceptions
     */
    class ApplicationAlreadyDefinedException extends Exception
    {
        /**
         * ApplicationAlreadyDefinedException constructor.
         */
        public function __construct()
        {
            parent::__construct("The application has already been defined in memory", 0, null);
        }
    }