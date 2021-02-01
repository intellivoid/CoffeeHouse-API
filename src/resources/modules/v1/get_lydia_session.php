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

    use CoffeeHouse\Abstracts\ForeignSessionSearchMethod;
    use CoffeeHouse\CoffeeHouse;
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
     * Class get_lydia_session
     * @package modules\v1
     */
    class get_lydia_session extends Module implements  Response
    {
        /**
         * The name of the module
         *
         * @var string
         */
        public string $name = "get_lydia_session";

        /**
         * The version of this module
         *
         * @var string
         */
        public string $version = "1.0.2.0";

        /**
         * The description of this module
         *
         * @var string
         */
        public string $description = "Gets an existing Lydia Session";

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
            return null;
        }

        /**
         * @inheritDoc
         * @throws Exception
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
                        "message" => "Missing parameter 'session_id'"
                    )
                );
                $this->response_content = json_encode($ResponsePayload);
                $this->response_code = (int)$ResponsePayload["response_code"];

                return null;
            }

            try
            {
                $Session = $CoffeeHouse->getForeignSessionsManager()->getSession(
                    ForeignSessionSearchMethod::bySessionId, $Parameters["session_id"]
                );
            }
            catch (ForeignSessionNotFoundException)
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

            if((int)time() > $Session->Expires)
            {
                $Session->Available = false;
            }

            $ResponsePayload = array(
                "success" => true,
                "response_code" => 200,
                "results" => array(
                    "session_id" => $Session->SessionID,
                    "language" => $Session->Language,
                    "available" => (bool)$Session->Available,
                    "expires" => (int)$Session->Expires
                )
            );

            $this->response_content = json_encode($ResponsePayload);
            $this->response_code = (int)$ResponsePayload["response_code"];
        }
    }