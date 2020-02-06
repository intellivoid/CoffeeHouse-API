<?php


    namespace COASniffle\Exceptions;

    use Exception;

    /**
     * Class RedirectParameterMissingException
     * @package COASniffle\Exceptions
     */
    class RedirectParameterMissingException extends Exception
    {
        /**
         * RedirectParameterMissingException constructor.
         */
        public function __construct()
        {
            parent::__construct("Your Application requires a redirection URL to be passed", 0, null);
        }
    }