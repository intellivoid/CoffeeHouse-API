<?php


    namespace COASniffle\Exceptions;


    use COASniffle\Utilities\ErrorResolver;
    use Exception;

    /**
     * Class CoaAuthenticationException
     * @package COASniffle\Exceptions
     */
    class CoaAuthenticationException extends Exception
    {
        /**
         * CoaAuthenticationException constructor.
         * @param int $error_code
         */
        public function __construct(int $error_code)
        {
            parent::__construct(ErrorResolver::resolve_error_code($error_code), $error_code, null);
        }
    }