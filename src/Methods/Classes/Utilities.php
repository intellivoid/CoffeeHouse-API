<?php

    namespace Methods\Classes;

    use Exception;
    use IntellivoidAPI\Abstracts\SearchMethods\AccessRecordSearchMethod;
    use IntellivoidAPI\Exceptions\AccessRecordNotFoundException;
    use IntellivoidAPI\IntellivoidAPI;
    use IntellivoidAPI\Objects\AccessRecord;
    use KimchiAPI\Abstracts\ResponseStandard;
    use KimchiAPI\Abstracts\ResponseType;
    use KimchiAPI\Exceptions\AccessKeyNotProvidedException;
    use KimchiAPI\Exceptions\ApiException;
    use KimchiAPI\Exceptions\UnsupportedResponseStandardException;
    use KimchiAPI\Exceptions\UnsupportedResponseTypeExceptions;
    use KimchiAPI\KimchiAPI;
    use KimchiAPI\Objects\Response;

    class Utilities
    {
        /**
         * @return string[]
         */
        public static function getSupportedLanguages(): array
        {
            return [
                "en", // English
                "zh", // Chinese
                "de", // German
                "fr", // French
                "pl", // Polish
                "hi", // Hindi
                "hr", // Croatian
                "es", // Spanish
                "ru", // Russian
                "it" // Italian
            ];
        }

        /**
         * Authenticates the user and returns the Access Record once authenticated
         *
         * @param IntellivoidAPI $intellivoidAPI
         * @param string $response_standard
         * @param string $response_type
         * @return AccessRecord
         * @throws AccessKeyNotProvidedException
         * @throws ApiException
         * @throws UnsupportedResponseStandardException
         * @throws UnsupportedResponseTypeExceptions
         */
        public static function authenticateUser(IntellivoidAPI $intellivoidAPI, string $response_standard=ResponseStandard::KimchiAPI, string $response_type=ResponseType::Json): AccessRecord
        {
            $AccessKey = KimchiAPI::getAuthenticationToken('access_key');

            try
            {
                $AccessRecord = $intellivoidAPI->getAccessKeyManager()->getAccessRecord(
                    AccessRecordSearchMethod::byAccessKey, $AccessKey
                );
            }
            catch(AccessRecordNotFoundException $e)
            {
                unset($e);
                $response = new Response();
                $response->ResponseCode = 401;
                $response->Success = false;
                $response->ErrorCode = 401;
                $response->ErrorMessage = 'Invalid access key';
                $response->ResponseStandard = $response_standard;
                $response->ResponseType = $response_type;
                $response->Headers['WWW-Authenticate'] = 'Basic realm="' . KIMCHI_API_NAME . '"';
                KimchiAPI::handleResponse($response);
            }
            catch(Exception $e)
            {
                KimchiAPI::handleException($e);
            }

            return $AccessRecord;
        }
    }