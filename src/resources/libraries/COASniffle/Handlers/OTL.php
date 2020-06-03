<?php


    namespace COASniffle\Handlers;

    use COASniffle\Exceptions\BadResponseException;
    use COASniffle\Exceptions\OtlException;
    use COASniffle\Exceptions\RequestFailedException;
    use COASniffle\Exceptions\UnsupportedAuthMethodException;
    use COASniffle\Objects\OtlUserResponse;
    use COASniffle\Utilities\RequestBuilder;

    /**
     * Class OTL
     * @package COASniffle\Handlers
     */
    class OTL
    {
        /**
         * Verifies the OTL code and returns the user once authenticated
         *
         * @param string $auth_code
         * @param string $host_id
         * @param string $user_agent
         * @param string $vendor
         * @return OtlUserResponse
         * @throws BadResponseException
         * @throws OtlException
         * @throws RequestFailedException
         * @throws UnsupportedAuthMethodException
         */
        public static function verifyCode(string $auth_code, string $host_id, string $user_agent, string $vendor): OtlUserResponse
        {
            $Parameters =  array(
                'auth_code' => $auth_code,
                'host_id' => $host_id,
                'user_agent' => $user_agent,
                'vendor' => $vendor
            );
            $Response = RequestBuilder::sendRequest(
                'otl',
                array(
                    'action' => "verify_otl",
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

                throw new OtlException($Response['content'], $Parameters, $StatusCode, $ErrorMessage);
            }

            return OtlUserResponse::fromArray($ResponseJson['account']);
        }
    }