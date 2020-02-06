<?php


    namespace COASniffle\Utilities;


    use COASniffle\Exceptions\RequestFailedException;
    use COASniffle\Exceptions\UnsupportedAuthMethodException;

    /**
     * Class RequestBuilder
     * @package COASniffle\Utilities
     */
    class RequestBuilder
    {
        /**
         * Sends a standard POST Request with the required fields for Intellivoid Accounts API Endpoint
         *
         * @param string $authMethod
         * @param array $parameters
         * @param array $payload
         * @return array
         * @throws UnsupportedAuthMethodException
         * @throws RequestFailedException
         */
        public static function sendRequest(string $authMethod, array $parameters, array $payload): array
        {
            switch(strtolower($authMethod))
            {
                case 'otl':
                case 'khm':
                case 'coa':
                    $authMethod = strtolower($authMethod);
                    break;

                default:
                    throw new UnsupportedAuthMethodException();
            }

            // Build the data
            $parameters['wrapper'] = 'COASniffle';
            if(COA_SNIFFLE_SSL_ENABLED)
            {
                $parameters['secured'] = 'true';
            }
            else
            {
                $parameters['secured'] = 'false';
            }
            $GetParameters = '?' . http_build_query($parameters);
            $RequestUrl = COA_SNIFFLE_ENDPOINT . '/auth/' . $authMethod . $GetParameters;

            $CurlClient = curl_init($RequestUrl);
            curl_setopt($CurlClient, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($CurlClient, CURLOPT_POSTFIELDS, $payload);
            curl_setopt($CurlClient, CURLOPT_HEADER, 1);
            curl_setopt($CurlClient, CURLOPT_FOLLOWLOCATION, 0);
            $CurlResponse = curl_exec($CurlClient);

            if(curl_errno($CurlClient))
            {
                throw new RequestFailedException(curl_error($CurlClient));
            }

            $MethodReturnResponse = array(
                'body' => $CurlResponse,
                'content_type' => curl_getinfo($CurlClient, CURLINFO_CONTENT_TYPE),
                'response_code' => curl_getinfo($CurlClient, CURLINFO_HTTP_CODE),
                'redirect_location' => null,
                'x_coa_error' => null
            );

            $HeaderSize = curl_getinfo($CurlClient, CURLINFO_HEADER_SIZE);
            $MethodReturnResponse['headers'] = substr($CurlResponse, 0, $HeaderSize);
            $MethodReturnResponse['content'] = substr($CurlResponse, $HeaderSize);

            curl_close($CurlClient);

            if (preg_match('~Location: (.*)~i', $CurlResponse, $match))
            {
                $MethodReturnResponse['redirect_location'] = trim($match[1]);
            }

            if (preg_match('~X-COA-Error: (.*)~i', $CurlResponse, $match))
            {
                $MethodReturnResponse['x_coa_error'] = (int)trim($match[1]);
            }

            return $MethodReturnResponse;
        }
    }