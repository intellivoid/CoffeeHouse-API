<?php


    namespace COASniffle\Handlers;


    use COASniffle\Abstracts\ApplicationType;
    use COASniffle\COASniffle;
    use COASniffle\Exceptions\BadResponseException;
    use COASniffle\Exceptions\CoaAuthenticationException;
    use COASniffle\Exceptions\InvalidRedirectLocationException;
    use COASniffle\Exceptions\RedirectParameterMissingException;
    use COASniffle\Exceptions\RequestFailedException;
    use COASniffle\Exceptions\UnsupportedAuthMethodException;
    use COASniffle\Objects\AccessInformation;
    use COASniffle\Objects\Permissions;
    use COASniffle\Objects\SubscriptionPurchaseResults;
    use COASniffle\Objects\UserInformation;
    use COASniffle\Utilities\RequestBuilder;

    /**
     * Class COA
     * @package COASniffle\Handlers
     */
    class COA
    {
        /**
         * @var COASniffle
         */
        private $COASniffle;

        /**
         * COA constructor.
         * @param COASniffle $COASniffle
         */
        public function __construct(COASniffle $COASniffle)
        {
            $this->COASniffle = $COASniffle;
        }

        /**
         * Creates an authentication request which returns the Request Token and the Authentiction URL
         *
         * The array contains two values ('request_token', 'auth_url')
         * The request_token is the token used to establish an authentication request
         * The authentication URL is the URL that the user needs to open to authenticate to your Application
         *
         * @param string $redirect
         * @return array
         * @throws BadResponseException
         * @throws CoaAuthenticationException
         * @throws InvalidRedirectLocationException
         * @throws RedirectParameterMissingException
         * @throws RequestFailedException
         * @throws UnsupportedAuthMethodException
         */
        public function createAuthenticationRequest(string $redirect="None"): array
        {
            if(COA_SNIFFLE_APP_TYPE == ApplicationType::Redirect)
            {
                if($redirect == "None")
                {
                    throw new RedirectParameterMissingException();
                }

                if(filter_var($redirect, FILTER_VALIDATE_URL) == false)
                {
                    throw new InvalidRedirectLocationException();
                }
            }

            $Response = RequestBuilder::sendRequest(
                'coa',
                array(
                    'action' => "create_authentication_request",
                ),
                array(
                    'application_id' => COA_SNIFFLE_APP_PUBLIC_ID,
                    'redirect' => $redirect
                )
            );

            $ResponseJson = json_decode($Response['content'], true);
            if($ResponseJson == false)
            {
                throw new BadResponseException();
            }

            if($ResponseJson['status'] == false)
            {
                throw new CoaAuthenticationException($ResponseJson['error_code']);
            }

            return array(
                'request_token' => $ResponseJson['request_token'],
                'auth_url' => $ResponseJson['auth_url']
            );
        }

        /**
         * Requests for authentication and Returns the location for the user to authenticate to.
         *
         * This is the same as getAuthenticationURL() but it actually processes the
         * request to the URL you get from getAuthenticationURL() to get the redirect
         * URL that the user should open.
         *
         * This function isn't supposed to be used, instead use createAuthenticationRequest()
         * which accomplishes the same as this but you also get the Request Token instead of
         * just the authentication URL
         *
         * @param bool $include_host
         * @param string $redirect
         * @return string
         * @throws CoaAuthenticationException
         * @throws RedirectParameterMissingException
         * @throws UnsupportedAuthMethodException
         * @throws InvalidRedirectLocationException
         * @throws RequestFailedException
         */
        public function requestAuthentication(bool $include_host=True, string $redirect="None"): string
        {
            if(COA_SNIFFLE_APP_TYPE == ApplicationType::Redirect)
            {
                if($redirect == "None")
                {
                    throw new RedirectParameterMissingException();
                }

                if(filter_var($redirect, FILTER_VALIDATE_URL) == false)
                {
                    throw new InvalidRedirectLocationException();
                }
            }

            $Response = RequestBuilder::sendRequest(
                'coa',
                array(
                    'action' => "request_authentication",
                ),
                array(
                    'application_id' => COA_SNIFFLE_APP_PUBLIC_ID,
                    'redirect' => $redirect
                )
            );

            if(is_null($Response['x_coa_error']) == false)
            {
                throw new CoaAuthenticationException($Response['x_coa_error']);
            }

            if($include_host)
            {
                return COA_SNIFFLE_ENDPOINT . $Response['redirect_location'];
            }

            return $Response['redirect_location'];
        }

        /**
         * Attempts to get the access token once the user has authenticated, will return null
         * when the user hasn't authenticated yet. Otherwise it will return a string which
         * contains the Access Token
         *
         * @param string $request_token
         * @return string|null
         * @throws BadResponseException
         * @throws CoaAuthenticationException
         * @throws RequestFailedException
         * @throws UnsupportedAuthMethodException
         */
        public function getAccessToken(string $request_token)
        {
            $Response = RequestBuilder::sendRequest(
                'coa',
                array(
                    'action' => "get_access_token",
                ),
                array(
                    'application_id' => COA_SNIFFLE_APP_PUBLIC_ID,
                    'secret_key' => COA_SNIFFLE_APP_SECRET_KEY,
                    'request_token' => $request_token
                )
            );

            $ResponseJson = json_decode($Response['content'], true);
            if($ResponseJson == false)
            {
                throw new BadResponseException();
            }

            if($ResponseJson['status'] == false)
            {
                if($ResponseJson['error_code'] == 41)
                {
                    return null;
                }

                throw new CoaAuthenticationException($ResponseJson['error_code']);
            }

            return $ResponseJson['access_token'];
        }

        /**
         * Checks what permissions the user has granted to the Application
         *
         * @param string $access_token
         * @return Permissions
         * @throws BadResponseException
         * @throws CoaAuthenticationException
         * @throws RequestFailedException
         * @throws UnsupportedAuthMethodException
         */
        public function checkPermissions(string $access_token): Permissions
        {
            $Response = RequestBuilder::sendRequest(
                'coa',
                array(
                    'action' => "check_permissions",
                ),
                array(
                    'application_id' => COA_SNIFFLE_APP_PUBLIC_ID,
                    'secret_key' => COA_SNIFFLE_APP_SECRET_KEY,
                    'access_token' => $access_token
                )
            );

            $ResponseJson = json_decode($Response['content'], true);
            if($ResponseJson == false)
            {
                throw new BadResponseException();
            }

            if($ResponseJson['status'] == false)
            {
                throw new CoaAuthenticationException($ResponseJson['error_code']);
            }

            return Permissions::fromArray($ResponseJson['permissions']);
        }

        /**
         * Gets information about the authenticated user
         *
         * @param string $access_token
         * @return UserInformation
         * @throws BadResponseException
         * @throws CoaAuthenticationException
         * @throws RequestFailedException
         * @throws UnsupportedAuthMethodException
         */
        public function getUser(string $access_token): UserInformation
        {
            $Response = RequestBuilder::sendRequest(
                'coa',
                array(
                    'action' => "get_user",
                ),
                array(
                    'application_id' => COA_SNIFFLE_APP_PUBLIC_ID,
                    'secret_key' => COA_SNIFFLE_APP_SECRET_KEY,
                    'access_token' => $access_token
                )
            );

            $ResponseJson = json_decode($Response['content'], true);
            if($ResponseJson == false)
            {
                throw new BadResponseException();
            }

            if($ResponseJson['status'] == false)
            {
                throw new CoaAuthenticationException($ResponseJson['error_code']);
            }

            return UserInformation::fromArray($ResponseJson['user_information']);
        }

        /**
         * Gets the access information
         *
         * @param string $access_token
         * @return AccessInformation
         * @throws BadResponseException
         * @throws CoaAuthenticationException
         * @throws RequestFailedException
         * @throws UnsupportedAuthMethodException
         */
        public function getAccess(string $access_token): AccessInformation
        {
            $Response = RequestBuilder::sendRequest(
                'coa',
                array(
                    'action' => "get_access",
                ),
                array(
                    'application_id' => COA_SNIFFLE_APP_PUBLIC_ID,
                    'secret_key' => COA_SNIFFLE_APP_SECRET_KEY,
                    'access_token' => $access_token
                )
            );

            $ResponseJson = json_decode($Response['content'], true);
            if($ResponseJson == false)
            {
                throw new BadResponseException();
            }

            if($ResponseJson['status'] == false)
            {
                throw new CoaAuthenticationException($ResponseJson['error_code']);
            }

            return AccessInformation::fromArray($ResponseJson['user_information']);
        }

        /**
         * Builds the authentication request URL only where the request token would be created
         * upon request
         *
         * Useful for adding the URL to a href value of a button/link which allows the user
         * to request for authentication to your Application
         *
         * @param string $redirect
         * @return string
         * @throws InvalidRedirectLocationException
         * @throws RedirectParameterMissingException
         */
        public function getAuthenticationURL(string $redirect="None"): string
        {
            if(COA_SNIFFLE_APP_TYPE == ApplicationType::Redirect)
            {
                if($redirect == "None")
                {
                    throw new RedirectParameterMissingException();
                }

                if(filter_var($redirect, FILTER_VALIDATE_URL) == false)
                {
                    throw new InvalidRedirectLocationException();
                }
            }

            $Parameters = array(
                'action' => "request_authentication",
                'application_id' => COA_SNIFFLE_APP_PUBLIC_ID,
                'redirect' => $redirect,
                'wrapper' => 'COASniffle',
                'secured' => 'false'
            );

            if(COA_SNIFFLE_SSL_ENABLED)
            {
                $Parameters['secured'] = 'true';
            }

            $GetParameters = '?' . http_build_query($Parameters);
            return COA_SNIFFLE_ENDPOINT . '/auth/coa' . $GetParameters;
        }

        /**
         * Gets the URL for a user's avatar
         *
         * @param string $resource
         * @param string $userPublicId
         * @return string
         */
        public static function getAvatarUrl(string $resource, string $userPublicId): string
        {
            $Parameters = array(
                'user_id' => $userPublicId,
                'resource' => $resource
            );

            return COA_SNIFFLE_ENDPOINT . '/user/contents/public/avatar?' . http_build_query($Parameters);
        }

        /**
         * @param string $access_token
         * @param string $plan_name
         * @param string $promotion_code
         * @return SubscriptionPurchaseResults
         * @throws BadResponseException
         * @throws CoaAuthenticationException
         * @throws RequestFailedException
         * @throws UnsupportedAuthMethodException
         */
        public function createSubscription(string $access_token, string $plan_name, string $promotion_code="None"): SubscriptionPurchaseResults
        {
            $RequestPayload = array(
                'application_id' => COA_SNIFFLE_APP_PUBLIC_ID,
                'secret_key' => COA_SNIFFLE_APP_SECRET_KEY,
                'access_token' => $access_token,
                'plan_name' => $plan_name,
            );

            if($promotion_code !== "None")
            {
                $RequestPayload['promotion_code'] = $promotion_code;
            }

            $Response = RequestBuilder::sendRequest(
                'coa',
                array(
                    'action' => "create_subscription",
                ),
                $RequestPayload
            );

            $ResponseJson = json_decode($Response['content'], true);
            if($ResponseJson == false)
            {
                throw new BadResponseException();
            }

            if($ResponseJson['status'] == false)
            {
                throw new CoaAuthenticationException($ResponseJson['error_code']);
            }

            return SubscriptionPurchaseResults::fromArray($ResponseJson['payload']);
        }

        /**
         * Processes the subscription billing and returns true if payment was processed
         * or returns false when the payment does not need to be processed
         *
         * @param int $subscription_id
         * @return bool
         * @throws BadResponseException
         * @throws CoaAuthenticationException
         * @throws RequestFailedException
         * @throws UnsupportedAuthMethodException
         */
        public static function processSubscriptionBilling(int $subscription_id): bool
        {
            $RequestPayload = array(
                'subscription_id' => $subscription_id
            );

            $Response = RequestBuilder::sendRequest(
                'coa',
                array(
                    'action' => "process_subscription_billing",
                ),
                $RequestPayload
            );

            $ResponseJson = json_decode($Response['content'], true);
            if($ResponseJson == false)
            {
                throw new BadResponseException();
            }

            if($ResponseJson['status'] == false)
            {
                throw new CoaAuthenticationException($ResponseJson['error_code']);
            }

            return (bool)$ResponseJson['payment_processed'];
        }
    }