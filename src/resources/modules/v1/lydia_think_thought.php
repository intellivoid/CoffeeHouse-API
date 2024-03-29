<?php
    /*
     * Copyright (c) 2017-2021. Intellivoid Technologies
     *
     * All rights reserved, this is a closed-source solution written by Zi Xing Narrakas,
     *  under no circumstances is any entity with access to this file should redistribute
     *  without written permission from Intellivoid and or the original Author.
     */

    /** @noinspection PhpPureAttributeCanBeAddedInspection */
    /** @noinspection PhpUnused */
    /** @noinspection PhpMissingFieldTypeInspection */

    namespace modules\v1;

    use CoffeeHouse\Bots\Cleverbot;
    use CoffeeHouse\CoffeeHouse;
    use CoffeeHouse\Exceptions\BotSessionException;
    use CoffeeHouse\Exceptions\ForeignSessionNotFoundException;
    use Exception;
    use Handler\Abstracts\Module;
    use Handler\GenericResponses\InternalServerError;
    use Handler\Handler;
    use Handler\Interfaces\Response;
    use IntellivoidAPI\Objects\AccessRecord;
    use SubscriptionValidation;

    include_once(__DIR__ . DIRECTORY_SEPARATOR . "script.check_subscription.php");

    /**
     * Class lydia_think_thought
     * @package modules\v1
     */
    class lydia_think_thought extends Module implements  Response
    {
        /**
         * The name of the module
         *
         * @var string
         */
        public string $name = "lydia_think_thought";

        /**
         * The version of this module
         *
         * @var string
         */
        public string $version = "2.0.0.0";

        /**
         * The description of this module
         *
         * @var string
         */
        public string $description = "Invokes the AI to process a input and produce an output";

        /**
         * Optional access record for this module
         *
         * @var AccessRecord
         */
        public AccessRecord $access_record;

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
            return "";
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

            $Parameters = Handler::getParameters(true, true);

            if(isset($Parameters["session_id"]) == false)
            {
                $ResponsePayload = array(
                    "success" => false,
                    "response_code" => 400,
                    "error" => array(
                        "error_code" => 1,
                        "type" => "CLIENT",
                        "message" => "Missing parameter \"session_id\""
                    )
                );
                $this->response_content = json_encode($ResponsePayload);
                $this->response_code = (int)$ResponsePayload["response_code"];

                return null;
            }

            if(isset($Parameters["input"]) == false)
            {
                $ResponsePayload = array(
                    "success" => false,
                    "response_code" => 400,
                    "error" => array(
                        "error_code" => 2,
                        "type" => "CLIENT",
                        "message" => "Missing parameter \"input\""
                    )
                );
                $this->response_content = json_encode($ResponsePayload);
                $this->response_code = (int)$ResponsePayload["response_code"];

                return null;
            }

            if(strlen($Parameters["input"]) < 1)
            {
                $ResponsePayload = array(
                    "success" => false,
                    "response_code" => 400,
                    "error" => array(
                        "error_code" => 3,
                        "type" => "CLIENT",
                        "message" => "Parameter \"input\" contains an invalid value"
                    )
                );
                $this->response_content = json_encode($ResponsePayload);
                $this->response_code = (int)$ResponsePayload["response_code"];

                return null;
            }

            /** @noinspection PhpUnhandledExceptionInspection Doesn't throw any exception */
            $CleverBot = new Cleverbot($CoffeeHouse);

            try
            {
                $CleverBot->loadSession($Parameters["session_id"]);
            }
            catch(ForeignSessionNotFoundException $e)
            {
                $ResponsePayload = array(
                    "success" => false,
                    "response_code" => 404,
                    "error" => array(
                        "error_code" => 4,
                        "type" => "CLIENT",
                        "message" => "The session was not found"
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

            if((int)time() > $CleverBot->getSession()->Expires)
            {
                $ResponsePayload = array(
                    "success" => false,
                    "response_code" => 410,
                    "error" => array(
                        "error_code" => 5,
                        "type" => "CLIENT",
                        "message" => "The session is no longer available"
                    )
                );
                $this->response_content = json_encode($ResponsePayload);
                $this->response_code = (int)$ResponsePayload["response_code"];

                return null;
            }

            if($CleverBot->getSession()->Available == false)
            {
                $ResponsePayload = array(
                    "success" => false,
                    "response_code" => 410,
                    "error" => array(
                        "error_code" => 5,
                        "type" => "CLIENT",
                        "message" => "The session is no longer available"
                    )
                );
                $this->response_content = json_encode($ResponsePayload);
                $this->response_code = (int)$ResponsePayload["response_code"];

                return null;
            }

            try
            {
                $BotResponse = $CleverBot->think($Parameters["input"]);
            }
            catch(BotSessionException $e)
            {
                $Session = $CleverBot->getSession();
                $Session->Available = false;
                /** @noinspection PhpUnhandledExceptionInspection */
                $CoffeeHouse->getForeignSessionsManager()->updateSession($Session);

                $ResponsePayload = array(
                    "success" => false,
                    "response_code" => 410,
                    "error" => array(
                        "error_code" => 5,
                        "type" => "CLIENT",
                        "message" => "The session is no longer available"
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
                    "output" => $BotResponse,
                    "session" => [
                        "session_id" => $CleverBot->getSession()->SessionID,
                        "language" => $CleverBot->getSession()->Language,
                        "available" => (bool)$CleverBot->getSession()->Available,
                        "expires" => (int)$CleverBot->getSession()->Expires
                    ],
                    "attributes" => [
                        "ai_emotion" => $CleverBot->getLocalSession()->AiCurrentEmotion,
                        "ai_emotion_probability" => $CleverBot->getLocalSession()->EmotionLargeGeneralization->TopProbability * 100,
                        "current_language" => $CleverBot->getLocalSession()->PredictedLanguage,
                        "current_language_probability" => $CleverBot->getLocalSession()->LanguageLargeGeneralization->TopProbability * 100
                    ]
                )
            );

            $CoffeeHouse->getDeepAnalytics()->tally("coffeehouse_api", "ai_responses", 0);
            $CoffeeHouse->getDeepAnalytics()->tally("coffeehouse_api", "ai_responses", $this->access_record->ID);
            $this->response_content = json_encode($ResponsePayload);
            $this->response_code = (int)$ResponsePayload["response_code"];
        }
    }