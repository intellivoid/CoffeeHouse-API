<?php


    namespace COASniffle\Handlers;

    use COASniffle\Exceptions\BadResponseException;
    use COASniffle\Exceptions\KhmException;
    use COASniffle\Exceptions\RequestFailedException;
    use COASniffle\Exceptions\UnsupportedAuthMethodException;
    use COASniffle\Utilities\RequestBuilder;

    /**
     * Class KHM
     * @package COASniffle\Handlers
     */
    class KHM
    {
        /**
         * Registers a host to the server and returns the host ID
         *
         * @param string $remote_host
         * @param string $user_agent
         * @return string
         * @throws BadResponseException
         * @throws KhmException
         * @throws RequestFailedException
         * @throws UnsupportedAuthMethodException
         */
        public static function registerHost(string $remote_host, string $user_agent): string
        {
            $Parameters =  array(
                'remote_host' => $remote_host,
                'user_agent' => $user_agent,
            );
            $Response = RequestBuilder::sendRequest(
                'khm',
                array(
                    'action' => "register_host",
                ), $Parameters
            );

            $ResponseJson = json_decode($Response['content'], true);
            if($ResponseJson == false)
            {
                throw new BadResponseException();
            }

            if($ResponseJson['status'] == false)
            {
                $StatusCode = 0;
                $ErrorMessage = "Unknown";

                if(isset($ResponseJson['status_code']))
                {
                    $StatusCode = (int)$ResponseJson['status_code'];
                }

                if(isset($ResponseJson['message']))
                {
                    $ErrorMessage = $ResponseJson['message'];
                }

                throw new KhmException($Response['content'], $Parameters, $StatusCode, $ErrorMessage);
            }

            return $ResponseJson['host_id'];
        }
    }