<?php


    namespace COASniffle\Exceptions;


    use Exception;
    use Throwable;

    /**
     * Class RequestFailedException
     * @package COASniffle\Exceptions
     */
    class RequestFailedException extends Exception
    {
        /**
         * @var string
         */
        private $curl_error;

        /**
         * RequestFailedException constructor.
         * @param string $curl_error
         */
        public function __construct(string $curl_error)
        {
            parent::__construct("The HTTP request failed and raised an error", 0, null);
            $this->curl_error = $curl_error;
        }

        /**
         * @return string
         */
        public function getCurlError(): string
        {
            return $this->curl_error;
        }
    }