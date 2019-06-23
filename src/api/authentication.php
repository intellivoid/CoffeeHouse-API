<?php

    use ModularAPI\Abstracts\AuthenticationType;
    use ModularAPI\Exceptions\UnsupportedClientException;
    use ModularAPI\HTTP\Request;
    use ModularAPI\ModularAPI;
    use ModularAPI\Objects\AccessKey;

    /**
     * @param ModularAPI $modularAPI
     * @param bool $forceCertificate
     * @return AccessKey
     * @throws UnsupportedClientException
     */
    function verifyAuthentication(ModularAPI $modularAPI, bool $forceCertificate = false): AccessKey
    {
        $Authentication = Request::parseAuthentication();

        if($Authentication->Type == AuthenticationType::None)
        {
            authenticationRequiredError();
            return null;
        }

        if($forceCertificate == true)
        {
            if($Authentication->Type !== AuthenticationType::Certificate)
            {
                certificateRequiredError();
            }

            try
            {
                return $modularAPI->AccessKeys()->verifyCertificate($Authentication->Certificate);
            }
            catch(Exception $exception)
            {
                invalidAuthenticationError();
            }
        }

        if($Authentication->Type == AuthenticationType::Certificate)
        {
            try
            {
                return $modularAPI->AccessKeys()->verifyCertificate($Authentication->Certificate);
            }
            catch(Exception $exception)
            {
                invalidAuthenticationError();
            }
        }

        if($Authentication->Type == AuthenticationType::APIKey)
        {
            try
            {
                return $modularAPI->AccessKeys()->verifyAPIKey($Authentication->Key);
            }
            catch(Exception $exception)
            {
                invalidAuthenticationError();
            }
        }

        invalidAuthenticationError();
        return null;
    }
