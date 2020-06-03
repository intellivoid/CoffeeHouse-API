<?php


    namespace COASniffle;

    use acm\acm;
    use COASniffle\Abstracts\ApplicationType;
    use COASniffle\Exceptions\ApplicationAlreadyDefinedException;
    use COASniffle\Handlers\COA;
    use Exception;

    $LocalDirectory = __DIR__ . DIRECTORY_SEPARATOR;

    include_once($LocalDirectory . 'Abstracts' . DIRECTORY_SEPARATOR . 'ApplicationType.php');
    include_once($LocalDirectory . 'Abstracts' . DIRECTORY_SEPARATOR . 'AvatarResourceName.php');

    include_once($LocalDirectory . 'Exceptions' . DIRECTORY_SEPARATOR . 'ApplicationAlreadyDefinedException.php');
    include_once($LocalDirectory . 'Exceptions' . DIRECTORY_SEPARATOR . 'BadResponseException.php');
    include_once($LocalDirectory . 'Exceptions' . DIRECTORY_SEPARATOR . 'CoaAuthenticationException.php');
    include_once($LocalDirectory . 'Exceptions' . DIRECTORY_SEPARATOR . 'InvalidRedirectLocationException.php');
    include_once($LocalDirectory . 'Exceptions' . DIRECTORY_SEPARATOR . 'KhmException.php');
    include_once($LocalDirectory . 'Exceptions' . DIRECTORY_SEPARATOR . 'OtlException.php');
    include_once($LocalDirectory . 'Exceptions' . DIRECTORY_SEPARATOR . 'RedirectParameterMissingException.php');
    include_once($LocalDirectory . 'Exceptions' . DIRECTORY_SEPARATOR . 'RequestFailedException.php');
    include_once($LocalDirectory . 'Exceptions' . DIRECTORY_SEPARATOR . 'UnsupportedAuthMethodException.php');

    include_once($LocalDirectory . 'Handlers' . DIRECTORY_SEPARATOR . 'COA.php');
    include_once($LocalDirectory . 'Handlers' . DIRECTORY_SEPARATOR . 'KHM.php');
    include_once($LocalDirectory . 'Handlers' . DIRECTORY_SEPARATOR . 'OTL.php');

    include_once($LocalDirectory . 'Objects' . DIRECTORY_SEPARATOR . 'SubscriptionPurchaseResults' . DIRECTORY_SEPARATOR . 'SubscriptionDetails.php');
    include_once($LocalDirectory . 'Objects' . DIRECTORY_SEPARATOR . 'SubscriptionPurchaseResults' . DIRECTORY_SEPARATOR . 'SubscriptionPlan.php');
    include_once($LocalDirectory . 'Objects' . DIRECTORY_SEPARATOR . 'SubscriptionPurchaseResults' . DIRECTORY_SEPARATOR . 'SubscriptionPlanPromotion.php');
    include_once($LocalDirectory . 'Objects' . DIRECTORY_SEPARATOR . 'UserInformation' . DIRECTORY_SEPARATOR . 'Avatar.php');
    include_once($LocalDirectory . 'Objects' . DIRECTORY_SEPARATOR . 'UserInformation' . DIRECTORY_SEPARATOR . 'EmailAddress.php');
    include_once($LocalDirectory . 'Objects' . DIRECTORY_SEPARATOR . 'UserInformation' . DIRECTORY_SEPARATOR . 'PersonalInformation.php');
    include_once($LocalDirectory . 'Objects' . DIRECTORY_SEPARATOR . 'UserInformation' . DIRECTORY_SEPARATOR . 'PersonalInformation' . DIRECTORY_SEPARATOR . 'Birthday.php');
    include_once($LocalDirectory . 'Objects' . DIRECTORY_SEPARATOR . 'UserInformation' . DIRECTORY_SEPARATOR . 'PersonalInformation' . DIRECTORY_SEPARATOR . 'FirstName.php');
    include_once($LocalDirectory . 'Objects' . DIRECTORY_SEPARATOR . 'UserInformation' . DIRECTORY_SEPARATOR . 'PersonalInformation' . DIRECTORY_SEPARATOR . 'LastName.php');
    include_once($LocalDirectory . 'Objects' . DIRECTORY_SEPARATOR . 'AccessInformation.php');
    include_once($LocalDirectory . 'Objects' . DIRECTORY_SEPARATOR . 'OtlUserResponse.php');
    include_once($LocalDirectory . 'Objects' . DIRECTORY_SEPARATOR . 'Permissions.php');
    include_once($LocalDirectory . 'Objects' . DIRECTORY_SEPARATOR . 'SubscriptionPurchaseResults.php');
    include_once($LocalDirectory . 'Objects' . DIRECTORY_SEPARATOR . 'UserInformation.php');

    include_once($LocalDirectory . 'Utilities' . DIRECTORY_SEPARATOR . 'ErrorResolver.php');
    include_once($LocalDirectory . 'Utilities' . DIRECTORY_SEPARATOR . 'RequestBuilder.php');

    include_once($LocalDirectory . 'AutoConfig.php');

    if(class_exists('acm\acm') == false)
    {
        include_once(__DIR__ . DIRECTORY_SEPARATOR . 'acm' . DIRECTORY_SEPARATOR . 'acm.php');
    }

    class COASniffle
    {
        /**
         * @var acm
         */
        private $acm;

        /**
         * @var mixed
         */
        private $EndpointConfiguration;
        /**
         * @var COA
         */
        private $COA;

        /**
         * COASniffle constructor.
         */
        public function __construct()
        {
            $this->acm = new acm(__DIR__, 'COASniffle');

            try
            {
                $this->EndpointConfiguration = $this->acm->getConfiguration('Endpoint');
            }
            catch (Exception $e)
            {
                print("There was an error while trying to parse the ACM configuration");
                print($e->getMessage());
                exit(0);
            }

            if(strtolower($this->EndpointConfiguration['LocalDevelopment']) == "true")
            {
                define("COA_SNIFFLE_LOCAL_DEVELOPMENT_ENABLED", true);

                if(strtolower($this->EndpointConfiguration['EnableSSL']) == "true")
                {
                    define("COA_SNIFFLE_SSL_ENABLED", true);
                    define("COA_SNIFFLE_ENDPOINT", 'https://' . $this->EndpointConfiguration['LocalEndpoint']);
                }
                else
                {
                    define("COA_SNIFFLE_SSL_ENABLED", false);
                    define("COA_SNIFFLE_ENDPOINT", 'http://' . $this->EndpointConfiguration['LocalEndpoint']);
                }

            }
            else
            {
                define("COA_SNIFFLE_LOCAL_DEVELOPMENT_ENABLED", false);

                if(strtolower($this->EndpointConfiguration['EnableSSL']) == "true")
                {
                    define("COA_SNIFFLE_SSL_ENABLED", true);
                    define("COA_SNIFFLE_ENDPOINT", 'https://' . $this->EndpointConfiguration['ProductionEndpoint']);
                }
                else
                {
                    define("COA_SNIFFLE_SSL_ENABLED", false);
                    define("COA_SNIFFLE_ENDPOINT", 'http://' . $this->EndpointConfiguration['ProductionEndpoint']);
                }
            }

            $this->COA = new COA($this);
        }

        /**
         * Defines the Application Keys and Type in memory
         *
         * @param string $publicApplicationID
         * @param string $secretKey
         * @param ApplicationType|string $applicationType
         * @throws ApplicationAlreadyDefinedException
         */
        public function defineApplication(string $publicApplicationID, string $secretKey, string $applicationType)
        {
            if(defined("COA_SNIFFLE_APP_PUBLIC_ID"))
            {
                throw new ApplicationAlreadyDefinedException();
            }

            if(defined("COA_SNIFFLE_APP_SECRET_KEY"))
            {
                throw new ApplicationAlreadyDefinedException();
            }

            if(defined("COA_SNIFFLE_APP_TYPE"))
            {
                throw new ApplicationAlreadyDefinedException();
            }

            define("COA_SNIFFLE_APP_PUBLIC_ID", $publicApplicationID);
            define("COA_SNIFFLE_APP_SECRET_KEY", $secretKey);
            define("COA_SNIFFLE_APP_TYPE", $applicationType);
        }

        /**
         * @return acm
         */
        public function getAcm()
        {
            return $this->acm;
        }

        /**
         * @return mixed
         */
        public function getEndpointConfiguration()
        {
            return $this->EndpointConfiguration;
        }

        /**
         * @return COA
         */
        public function getCOA(): COA
        {
            return $this->COA;
        }
    }