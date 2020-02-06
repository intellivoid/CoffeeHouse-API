<?php

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

    include_once(__DIR__ . DIRECTORY_SEPARATOR . 'script.check_subscription.php');

    /**
     * Class lydia_think_thought
     */
    class lydia_think_thought extends Module implements  Response
    {
        /**
         * The name of the module
         *
         * @var string
         */
        public $name = 'lydia_think_thought';

        /**
         * The version of this module
         *
         * @var string
         */
        public $version = '1.0.0.0';

        /**
         * The description of this module
         *
         * @var string
         */
        public $description = "Invokes the AI to process a input and produce an output";

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
        public function getContentType(): string
        {
            return 'application/json';
        }

        /**
         * @inheritDoc
         */
        public function getContentLength(): int
        {
            return strlen($this->response_content);
        }

        /**
         * @inheritDoc
         */
        public function getBodyContent(): string
        {
            return $this->response_content;
        }

        /**
         * @inheritDoc
         */
        public function getResponseCode(): int
        {
            return $this->response_code;
        }

        /**
         * @inheritDoc
         */
        public function isFile(): bool
        {
            return false;
        }

        /**
         * @inheritDoc
         */
        public function getFileName(): string
        {
            return null;
        }

        /**
         * @inheritDoc
         * @throws Exception
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
                $this->response_content = json_encode($ValidationResponse['response']);
                $this->response_code = $ValidationResponse['response_code'];

                return null;
            }

            $Parameters = Handler::getParameters(true, true);

            if(isset($Parameters['session_id']) == false)
            {
                $ResponsePayload = array(
                    'success' => false,
                    'response_code' => 400,
                    'error' => array(
                        'error_code' => 0,
                        'type' => "CLIENT",
                        "message" => "Missing parameter 'session_id'"
                    )
                );
                $this->response_content = json_encode($ResponsePayload);
                $this->response_code = (int)$ResponsePayload['response_code'];

                return null;
            }

            if(isset($Parameters['input']) == false)
            {
                $ResponsePayload = array(
                    'success' => false,
                    'response_code' => 400,
                    'error' => array(
                        'error_code' => 0,
                        'type' => "CLIENT",
                        "message" => "Missing parameter 'input'"
                    )
                );
                $this->response_content = json_encode($ResponsePayload);
                $this->response_code = (int)$ResponsePayload['response_code'];

                return null;
            }

            if(strlen($Parameters['input']) < 1)
            {
                $ResponsePayload = array(
                    'success' => false,
                    'response_code' => 400,
                    'error' => array(
                        'error_code' => 0,
                        'type' => "CLIENT",
                        "message" => "Parameter 'input' contains an invalid value"
                    )
                );
                $this->response_content = json_encode($ResponsePayload);
                $this->response_code = (int)$ResponsePayload['response_code'];

                return null;
            }

            $CleverBot = new Cleverbot($CoffeeHouse);

            try
            {
                $CleverBot->loadSession($Parameters['session_id']);
            }
            catch(ForeignSessionNotFoundException $foreignSessionNotFoundException)
            {
                $ResponsePayload = array(
                    'success' => false,
                    'response_code' => 404,
                    'error' => array(
                        'error_code' => 0,
                        'type' => "CLIENT",
                        "message" => "The session was not found"
                    )
                );
                $this->response_content = json_encode($ResponsePayload);
                $this->response_code = (int)$ResponsePayload['response_code'];

                return null;
            }

            if((int)time() > $CleverBot->getSession()->Expires)
            {
                $ResponsePayload = array(
                    'success' => false,
                    'response_code' => 410,
                    'error' => array(
                        'error_code' => 0,
                        'type' => "CLIENT",
                        "message" => "The session is no longer available"
                    )
                );
                $this->response_content = json_encode($ResponsePayload);
                $this->response_code = (int)$ResponsePayload['response_code'];

                return null;
            }

            if($CleverBot->getSession()->Available == false)
            {
                if((int)time() > $CleverBot->getSession()->Expires)
                {
                    $ResponsePayload = array(
                        'success' => false,
                        'response_code' => 410,
                        'error' => array(
                            'error_code' => 0,
                            'type' => "CLIENT",
                            "message" => "The session is no longer available"
                        )
                    );
                    $this->response_content = json_encode($ResponsePayload);
                    $this->response_code = (int)$ResponsePayload['response_code'];

                    return null;
                }
            }

            try
            {
                $BotResponse = $CleverBot->think($Parameters['input']);
            }
            catch(BotSessionException $botSessionException)
            {
                $Session = $CleverBot->getSession();
                $Session->Available = false;
                $CoffeeHouse->getForeignSessionsManager()->updateSession($Session);

                $ResponsePayload = array(
                    'success' => false,
                    'response_code' => 410,
                    'error' => array(
                        'error_code' => 0,
                        'type' => "CLIENT",
                        "message" => "The session is no longer available"
                    )
                );
                $this->response_content = json_encode($ResponsePayload);
                $this->response_code = (int)$ResponsePayload['response_code'];

                return null;
            }

            $ResponsePayload = array(
                'success' => true,
                'response_code' => 200,
                'payload' => array(
                    'output' => $BotResponse
                )
            );

            $this->response_content = json_encode($ResponsePayload);
            $this->response_code = (int)$ResponsePayload['response_code'];
        }
    }