<?php

    /** @noinspection PhpUnused */
    /** @noinspection PhpMissingFieldTypeInspection */

    namespace modules\v1;

    use CoffeeHouse\Classes\Utilities;
    use CoffeeHouse\CoffeeHouse;
    use CoffeeHouse\Exceptions\CoffeeHouseUtilsNotReadyException;
    use CoffeeHouse\Exceptions\InvalidInputException;
    use CoffeeHouse\Exceptions\InvalidLanguageException;
    use CoffeeHouse\Exceptions\InvalidTextInputException;
    use Exception;
    use Handler\Abstracts\Module;
    use Handler\GenericResponses\InternalServerError;
    use Handler\Handler;
    use Handler\Interfaces\Response;
    use IntellivoidAPI\Objects\AccessRecord;
    use SubscriptionValidation;

    include_once(__DIR__ . DIRECTORY_SEPARATOR . "script.check_subscription.php");
    include_once(__DIR__ . DIRECTORY_SEPARATOR . "script.supported_languages.php");

    /**
     * Class create_lydia_session
     */
    class pos_tagging extends Module implements Response
    {
        /**
         * The name of the module
         *
         * @var string
         */
        public $name = "pos_tagging";

        /**
         * The version of this module
         *
         * @var string
         */
        public $version = "1.0.0.0";

        /**
         * The description of this module
         *
         * @var string
         */
        public $description = "Tags Part-Of-Speech entities from a text input";

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
         * @noinspection PhpPureAttributeCanBeAddedInspection
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
            if(isset($this->access_record->Variables["POS_CHECKS"]) == false)
            {
                $this->access_record->setVariable("POS_CHECKS", 0);
            }

            // If the user has unlimited, ignore the check.
            if((int)$this->access_record->Variables["MAX_POS_CHECKS"] > 0)
            {
                // If the current sessions are equal or greater
                if($this->access_record->Variables["POS_CHECKS"] >= $this->access_record->Variables["MAX_POS_CHECKS"])
                {
                    $ResponsePayload = array(
                        "success" => false,
                        "response_code" => 429,
                        "error" => array(
                            "error_code" => 6,
                            "type" => "CLIENT",
                            "message" => "You have reached the max quota for this method"
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
         * Validates if the input is applicable to the NLP method
         *
         * @param string $input
         * @return bool
         */
        private function validateNlpInput(string $input): bool
        {
            if(isset($this->access_record->Variables["MAX_NLP_CHARACTERS"]) == false)
            {
                $ResponsePayload = array(
                    "success" => false,
                    "response_code" => 500,
                    "error" => array(
                        "error_code" => -1,
                        "type" => "SERVER",
                        "message" => "The server cannot verify the value 'MAX_NLP_CHARACTERS'"
                    )
                );
                $this->response_content = json_encode($ResponsePayload);
                $this->response_code = (int)$ResponsePayload["response_code"];

                return False;
            }

            if(strlen($input) > (int)$this->access_record->Variables["MAX_NLP_CHARACTERS"])
            {
                $ResponsePayload = array(
                    "success" => false,
                    "response_code" => 400,
                    "error" => array(
                        "error_code" => 21,
                        "type" => "CLIENT",
                        "message" => "The given input exceeds the limit of '" . $this->access_record->Variables["MAX_NLP_CHARACTERS"] . "' characters. (Subscription restriction)"
                    )
                );
                $this->response_content = json_encode($ResponsePayload);
                $this->response_code = (int)$ResponsePayload["response_code"];

                return False;
            }

            if(strlen($input) == 0)
            {
                $ResponsePayload = array(
                    "success" => false,
                    "response_code" => 400,
                    "error" => array(
                        "error_code" => 22,
                        "type" => "CLIENT",
                        "message" => "The given input cannot be empty"
                    )
                );
                $this->response_content = json_encode($ResponsePayload);
                $this->response_code = (int)$ResponsePayload["response_code"];

                return False;
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

            if(isset($Parameters["input"]) == false)
            {
                $ResponsePayload = array(
                    "success" => false,
                    "response_code" => 400,
                    "error" => array(
                        "error_code" => 20,
                        "type" => "CLIENT",
                        "message" => "Missing parameter 'input'"
                    )
                );
                $this->response_content = json_encode($ResponsePayload);
                $this->response_code = (int)$ResponsePayload["response_code"];

                return false;
            }

            if($this->validateNlpInput($Parameters["input"]) == false)
                return false;

            $source_language = "en";

            // Auto-Handle the language input
            if(isset($Parameters["language"]))
            {
                if($Parameters["language"] == "auto")
                {
                    try
                    {
                        $language_prediction_results = $CoffeeHouse->getLanguagePrediction()->predict($Parameters["input"]);
                        $Parameters["language"] = $language_prediction_results->combineResults()[0]->Language;
                    }
                    catch (CoffeeHouseUtilsNotReadyException)
                    {
                        $ResponsePayload = array(
                            "success" => false,
                            "response_code" => 503,
                            "error" => array(
                                "error_code" => 13,
                                "type" => "SERVER",
                                "message" => "CoffeeHouse-Utils is temporarily unavailable"
                            )
                        );
                        $this->response_content = json_encode($ResponsePayload);
                        $this->response_code = (int)$ResponsePayload["response_code"];

                        return false;
                    }
                    catch(Exception)
                    {
                        $ResponsePayload = array(
                            "success" => false,
                            "response_code" => 500,
                            "error" => array(
                                "error_code" => -1,
                                "type" => "SERVER",
                                "message" => "There was an error while trying to auto-detect the language"
                            )
                        );
                        $this->response_content = json_encode($ResponsePayload);
                        $this->response_code = (int)$ResponsePayload["response_code"];

                        return false;
                    }
                }

                try
                {
                    $source_language = Utilities::convertToISO6391($Parameters["language"]);
                }
                catch (InvalidLanguageException)
                {
                    $ResponsePayload = array(
                        "success" => false,
                        "response_code" => 400,
                        "error" => array(
                            "error_code" => 7,
                            "type" => "CLIENT",
                            "message" => "The given language '" . $Parameters["language"] . "' cannot be identified"
                        )
                    );
                    $this->response_content = json_encode($ResponsePayload);
                    $this->response_code = (int)$ResponsePayload["response_code"];

                    return false;
                }
            }

            if(in_array($source_language, get_supported_languages()) == false)
            {
                $ResponsePayload = array(
                    "success" => false,
                    "response_code" => 400,
                    "error" => array(
                        "error_code" => 23,
                        "type" => "CLIENT",
                        "message" => "The given language '$source_language' is not supported"
                    )
                );
                $this->response_content = json_encode($ResponsePayload);
                $this->response_code = (int)$ResponsePayload["response_code"];

                return false;
            }

            try
            {
                $PosTagsResults = $CoffeeHouse->getCoreNLP()->posTag($Parameters["input"], $source_language);
            }
            catch (CoffeeHouseUtilsNotReadyException)
            {
                $ResponsePayload = array(
                    "success" => false,
                    "response_code" => 503,
                    "error" => array(
                        "error_code" => 13,
                        "type" => "SERVER",
                        "message" => "CoffeeHouse-Utils is temporarily unavailable"
                    )
                );
                $this->response_content = json_encode($ResponsePayload);
                $this->response_code = (int)$ResponsePayload["response_code"];

                return false;
            }
            catch (InvalidInputException | InvalidTextInputException | InvalidLanguageException)
            {
                $ResponsePayload = array(
                    "success" => false,
                    "response_code" => 400,
                    "error" => array(
                        "error_code" => 24,
                        "type" => "CLIENT",
                        "message" => "The given input cannot be processed"
                    )
                );
                $this->response_content = json_encode($ResponsePayload);
                $this->response_code = (int)$ResponsePayload["response_code"];

                return false;
            }
            catch(Exception)
            {
                $ResponsePayload = array(
                    "success" => false,
                    "response_code" => 500,
                    "error" => array(
                        "error_code" => -1,
                        "type" => "SERVER",
                        "message" => "There was an unexpected error while trying to process your input"
                    )
                );
                $this->response_content = json_encode($ResponsePayload);
                $this->response_code = (int)$ResponsePayload["response_code"];

                return false;
            }

            $SentencesResults = [];

            foreach($PosTagsResults->PartOfSpeechSentences as $partOfSpeechSentence)
            {
                $pos_tags = [];
                foreach($partOfSpeechSentence->Tags as $posTag)
                {
                    $pos_tags[] = [
                        "text" => $posTag->Word,
                        "offset_begin" => $posTag->CharacterOffsetBegin,
                        "offset_end" => $posTag->CharacterOffsetEnd,
                        "tag_value" => $posTag->Value
                    ];
                }
                $SentencesResults[] = [
                    "text" => $partOfSpeechSentence->Text,
                    "offset_begin" => $partOfSpeechSentence->OffsetBegin,
                    "offset_end" => $partOfSpeechSentence->OffsetEnd,
                    "tags" => $pos_tags
                ];
            }

            $ResponsePayload = array(
                "success" => true,
                "response_code" => 200,
                "results" => [
                    "text" => $PosTagsResults->Text,
                    "source_language" => $source_language,
                    "sentences" => $SentencesResults
                ]
            );
            $this->response_content = json_encode($ResponsePayload);
            $this->response_code = (int)$ResponsePayload["response_code"];

            $this->access_record->Variables["POS_CHECKS"] += 1;
            $CoffeeHouse->getDeepAnalytics()->tally("coffeehouse_api", "pos_checks", 0);
            $CoffeeHouse->getDeepAnalytics()->tally("coffeehouse_api", "pos_checks", $this->access_record->ID);

            return true;
        }
    }