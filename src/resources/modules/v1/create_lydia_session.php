<?php

    namespace modules\v1;

    use CoffeeHouse\Bots\Cleverbot;
    use CoffeeHouse\Classes\Utilities;
    use CoffeeHouse\CoffeeHouse;
    use CoffeeHouse\Exceptions\BotSessionException;
    use CoffeeHouse\Exceptions\InvalidLanguageException;
    use Exception;
    use Handler\Abstracts\Module;
    use Handler\GenericResponses\InternalServerError;
    use Handler\Handler;
    use Handler\Interfaces\Response;
    use IntellivoidAPI\Objects\AccessRecord;
    use SubscriptionValidation;

    include_once(__DIR__ . DIRECTORY_SEPARATOR . "script.check_subscription.php");

    /**
     * Class create_lydia_session
     */
    class create_lydia_session extends Module implements Response
    {
        /**
         * The name of the module
         *
         * @var string
         */
        public $name = "create_lydia_session";

        /**
         * The version of this module
         *
         * @var string
         */
        public $version = "1.0.2.0";

        /**
         * The description of this module
         *
         * @var string
         */
        public $description = "Creates a new Lydia chat session";

        /**
         * Optional access record for this module
         *
         * @var AccessRecord
         */
        public $access_record;

        /**
         * The content to give on the response
         *
         * @var string
         */
        private $response_content;

        /**
         * The HTTP response code that will be given to the client
         *
         * @var int
         */
        private $response_code = 200;

        /**
         * @inheritDoc
         */
        public function getContentType(): ?string
        {
            return "application/json";
        }

        /**
         * @inheritDoc
         */
        public function getContentLength(): ?int
        {
            return strlen($this->response_content);
        }

        /**
         * @inheritDoc
         */
        public function getBodyContent(): ?string
        {
            return $this->response_content;
        }

        /**
         * @inheritDoc
         */
        public function getResponseCode(): ?int
        {
            return $this->response_code;
        }

        /**
         * @inheritDoc
         */
        public function isFile(): ?bool
        {
            return false;
        }

        /**
         * @inheritDoc
         */
        public function getFileName(): ?string
        {
            return null;
        }

        /**
         * Process the quota for the subscription, returns false if the quota limit has been reached.
         *
         * @return bool
         */
        private function processQuota(): bool
        {
            // Set the current quota if it doesn't exist
            if(isset($this->access_record->Variables["LYDIA_SESSIONS"]) == false)
            {
                $this->access_record->setVariable("LYDIA_SESSIONS", 0);
            }

            // If the user has unlimited, ignore the check.
            if((int)$this->access_record->Variables["MAX_LYDIA_SESSIONS"] > 0)
            {
                // If the current sessions are equal or greater
                if($this->access_record->Variables["LYDIA_SESSIONS"] >= $this->access_record->Variables["MAX_LYDIA_SESSIONS"])
                {
                    $ResponsePayload = array(
                        "success" => false,
                        "response_code" => 429,
                        "error" => array(
                            "error_code" => 6,
                            "type" => "CLIENT",
                            "message" => "Lydia sessions quota limit reached"
                        )
                    );
                    $this->response_content = json_encode($ResponsePayload);
                    $this->response_code = (int)$ResponsePayload["response_code"];

                    return False;
                }
            }

            return True;
        }

        /**
         * @inheritDoc
         * @noinspection DuplicatedCode
         */
        public function processRequest()
        {
            $CoffeeHouse = new CoffeeHouse();

            // Import the check subscription script and execute it
            $SubscriptionValidation = new SubscriptionValidation();

            try
            {
                $ValidationResponse = $SubscriptionValidation->validateUserSubscription($CoffeeHouse, $this->access_record);
            }
            catch (Exception $e)
            {
                InternalServerError::executeResponse($e);
                exit();
            }

            if(is_null($ValidationResponse) == false)
            {
                $this->response_content = json_encode($ValidationResponse["response"]);
                $this->response_code = $ValidationResponse["response_code"];

                return null;
            }

            if($this->processQuota() == false)
            {
                return null;
            }

            $Parameters = Handler::getParameters(true, true);
            $SelectedLanguage = "en";

            if(isset($Parameters["target_language"]))
            {
                try
                {
                    $SelectedLanguage = Utilities::convertToISO6391($Parameters["target_language"]);
                }
                catch (InvalidLanguageException)
                {
                    $ResponsePayload = array(
                        "success" => false,
                        "response_code" => 400,
                        "error" => array(
                            "error_code" => 7,
                            "type" => "CLIENT",
                            "message" => "The given language code is not a valid ISO 639-1 Language Code"
                        )
                    );

                    $this->response_content = json_encode($ResponsePayload);
                    $this->response_code = (int)$ResponsePayload["response_code"];

                    return null;
                }
            }

            try
            {
                $CleverBot = new Cleverbot($CoffeeHouse);
                $CleverBot->newSession($SelectedLanguage);

                $this->access_record->Variables["LYDIA_SESSIONS"] += 1;
            }
            catch(BotSessionException)
            {
                $ResponsePayload = array(
                    "success" => false,
                    "response_code" => 503,
                    "error" => array(
                        "error_code" => 8,
                        "type" => "CLIENT",
                        "message" => "Session cannot be created, service unavailable"
                    )
                );
                $this->response_content = json_encode($ResponsePayload);
                $this->response_code = (int)$ResponsePayload["response_code"];

                return null;
            }
            catch(Exception $e)
            {
                InternalServerError::executeResponse($e);
                exit();
            }

            $ResponsePayload = array(
                "success" => true,
                "response_code" => 200,
                "results" => array(
                    "session_id" => $CleverBot->getSession()->SessionID,
                    "language" => $CleverBot->getSession()->Language,
                    "available" => (bool)$CleverBot->getSession()->Available,
                    "expires" => (int)$CleverBot->getSession()->Expires
                )
            );

            $CoffeeHouse->getDeepAnalytics()->tally("coffeehouse_api", "created_sessions", 0);
            $CoffeeHouse->getDeepAnalytics()->tally("coffeehouse_api", "created_sessions", $this->access_record->ID);
            $this->response_content = json_encode($ResponsePayload);
            $this->response_code = (int)$ResponsePayload["response_code"];
        }
    }