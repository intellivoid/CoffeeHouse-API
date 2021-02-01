<?php

    /** @noinspection PhpPureAttributeCanBeAddedInspection */
    /** @noinspection PhpUnused */
    /** @noinspection PhpMissingFieldTypeInspection */

    namespace modules\v1;

    use CoffeeHouse\Abstracts\EmotionType;
    use CoffeeHouse\Abstracts\LargeGeneralizedClassificationSearchMethod;
    use CoffeeHouse\Classes\Utilities;
    use CoffeeHouse\CoffeeHouse;
    use CoffeeHouse\Exceptions\CoffeeHouseUtilsNotReadyException;
    use CoffeeHouse\Exceptions\DatabaseException;
    use CoffeeHouse\Exceptions\InvalidInputException;
    use CoffeeHouse\Exceptions\InvalidLanguageException;
    use CoffeeHouse\Exceptions\InvalidSearchMethodException;
    use CoffeeHouse\Exceptions\InvalidTextInputException;
    use CoffeeHouse\Exceptions\NoResultsFoundException;
    use CoffeeHouse\Objects\LargeGeneralization;
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
     * Class emotion_analysis
     * @package modules\v1
     */
    class emotion_analysis extends Module implements Response
    {
        /**
         * The name of the module
         *
         * @var string
         */
        public string $name = "emotion_analysis";

        /**
         * The version of this module
         *
         * @var string
         */
        public string $version = "1.0.0.0";

        /**
         * The description of this module
         *
         * @var string
         */
        public string $description = "Predicts emotion values from the given input";

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
            // Set the current quota if it doesn't SENTIMENT_CHECKS
            if(isset($this->access_record->Variables["EMOTION_CHECKS"]) == false)
            {
                $this->access_record->setVariable("EMOTION_CHECKS", 0);
            }

            // If the user has unlimited, ignore the check.
            if((int)$this->access_record->Variables["MAX_EMOTION_CHECKS"] > 0)
            {
                // If the current sessions are equal or greater
                if($this->access_record->Variables["EMOTION_CHECKS"] >= $this->access_record->Variables["MAX_EMOTION_CHECKS"])
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
         * @noinspection DuplicatedCode
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
                $EmotionResults = $CoffeeHouse->getEmotionPrediction()->predictSentences($Parameters["input"], $source_language);
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
            $SentenceSplit = false;

            if(isset($Parameters["sentence_split"]))
            {
                if((bool)strtolower($Parameters["sentence_split"]) == true)
                {
                    $SentenceSplit = true;
                }
            }

            foreach($EmotionResults->EmotionPredictionSentences as $emotionPredictionSentence)
            {
                $predictions = [];
                foreach($emotionPredictionSentence->EmotionPredictionResults->toArray()["values"] as $emotion => $prediction)
                    $predictions[$emotion] = $prediction * 100;

                $SentencesResults[] = [
                    "text" => $emotionPredictionSentence->Text,
                    "offset_begin" => $emotionPredictionSentence->OffsetBegin,
                    "offset_end" => $emotionPredictionSentence->OffsetEnd,
                    "sentiment" => [
                        "emotion" => $emotionPredictionSentence->EmotionPredictionResults->TopEmotion,
                        "prediction" => $emotionPredictionSentence->EmotionPredictionResults->TopValue * 100,
                        "predictions" => $predictions
                    ]
                ];
            }

            $predictions = [];
            foreach($EmotionResults->EmotionPrediction->toArray()["values"] as $emotion => $prediction)
                $predictions[$emotion] = $prediction * 100;

            $SingularResults = [
                "emotion" => $EmotionResults->EmotionPrediction->TopEmotion,
                "prediction" => $EmotionResults->EmotionPrediction->TopValue * 100,
                "predictions" => $predictions
            ];

            if($SentenceSplit)
            {
                $ResponsePayload = array(
                    "success" => true,
                    "response_code" => 200,
                    "results" => [
                        "text" => $EmotionResults->Text,
                        "source_language" => $source_language,
                        "sentiment" => $SingularResults,
                        "sentences" => $SentencesResults,
                        "generalization" => null
                    ]
                );
            }
            else
            {
                $ResponsePayload = array(
                    "success" => true,
                    "response_code" => 200,
                    "results" => [
                        "text" => $EmotionResults->Text,
                        "source_language" => $source_language,
                        "sentiment" => $SingularResults,
                        "generalization" => null
                    ]
                );
            }

            try
            {
                $generalization = $this->processGeneralization($CoffeeHouse);

                if($generalization !== null)
                {
                    $generalization = $CoffeeHouse->getEmotionPrediction()->generalize($generalization, $EmotionResults->EmotionPrediction);

                    // Pre-calculate the probabilities
                    $generalization->TopProbability = $generalization->TopProbability * 100;

                    $probabilities_data = array();

                    foreach ($generalization->Probabilities as $probability)
                    {
                        $probabilities_set = [];
                        foreach($probability->Probabilities as $f) $probabilities_set[] = $f * 100;

                        $probabilities_data[] = [
                            "label" => $probability->Label,
                            "calculated_probability" => $probability->CalculatedProbability * 100,
                            "current_pointer" => $probability->CurrentPointer - 1,
                            "probabilities" => $probabilities_set
                        ];
                    }

                    $ResponsePayload["results"]["generalization"] = [
                        "id" => $generalization->PublicID,
                        "size" => $generalization->MaxProbabilitiesSize,
                        "top_label" => $generalization->TopLabel,
                        "top_probability" => $generalization->TopProbability,
                        "probabilities" => $probabilities_data
                    ];
                    $this->response_content = json_encode($ResponsePayload);
                    $this->response_code = (int)$ResponsePayload["response_code"];
                }
            }
            catch(Exception)
            {
                // The request failed, already responded.
                return false;
            }

            $this->response_content = json_encode($ResponsePayload);
            $this->response_code = (int)$ResponsePayload["response_code"];

            $this->access_record->Variables["EMOTION_CHECKS"] += 1;
            $CoffeeHouse->getDeepAnalytics()->tally("coffeehouse_api", "emotion_checks", 0);
            $CoffeeHouse->getDeepAnalytics()->tally("coffeehouse_api", "emotion_checks", $this->access_record->ID);

            return true;
        }

        /**
         * @param CoffeeHouse $coffeeHouse
         * @return LargeGeneralization|null
         * @throws DatabaseException
         * @throws InvalidSearchMethodException
         * @throws NoResultsFoundException
         * @throws Exception
         * @noinspection DuplicatedCode
         */
        public function processGeneralization(CoffeeHouse $coffeeHouse): ?LargeGeneralization
        {
            $Parameters = Handler::getParameters(true, true);

            // Check if the client is requesting for generalization
            if(isset($Parameters["generalize"]))
            {
                if((bool)$Parameters["generalize"] == False)
                {
                    return null;
                }
            }
            else
            {
                return null;
            }

            if(isset($Parameters["generalization_id"]))
            {
                try
                {
                    $large_generalization = $coffeeHouse->getLargeGeneralizedClassificationManager()->get(LargeGeneralizedClassificationSearchMethod::byPublicID, $Parameters["generalization_id"]);
                }
                catch (NoResultsFoundException)
                {
                    $ResponsePayload = array(
                        "success" => false,
                        "response_code" => 404,
                        "error" => array(
                            "error_code" => 18,
                            "type" => "CLIENT",
                            "message" => "The requested generalization data was not found"
                        )
                    );
                    $this->response_content = json_encode($ResponsePayload);
                    $this->response_code = (int)$ResponsePayload["response_code"];

                    throw new Exception($ResponsePayload["error"]["message"], $ResponsePayload["error"]["error_code"]);
                }
                catch(Exception)
                {
                    $ResponsePayload = array(
                        "success" => false,
                        "response_code" => 500,
                        "error" => array(
                            "error_code" => -1,
                            "type" => "SERVER",
                            "message" => "There was an unexpected error while trying to retrieve the generalization data from the server"
                        )
                    );
                    $this->response_content = json_encode($ResponsePayload);
                    $this->response_code = (int)$ResponsePayload["response_code"];

                    throw new Exception($ResponsePayload["error"]["message"], $ResponsePayload["error"]["error_code"]);
                }

                // Verify if this generalization is applicable to this method

                $labels = [];
                $applicable_labels = [
                    EmotionType::Neutral,
                    EmotionType::Affection,
                    EmotionType::Happiness,
                    EmotionType::Anger,
                    EmotionType::Sadness
                ];

                foreach($large_generalization->Probabilities as $probability)
                {
                    if(in_array($probability->Label, $labels) == false)
                        $labels[] = $probability->Label;
                }

                foreach($labels as $label)
                {
                    if(in_array($label, $applicable_labels) == false)
                    {
                        $ResponsePayload = array(
                            "success" => false,
                            "response_code" => 400,
                            "error" => array(
                                "error_code" => 19,
                                "type" => "CLIENT",
                                "message" => "This generalization set does not apply to this method"
                            )
                        );
                        $this->response_content = json_encode($ResponsePayload);
                        $this->response_code = (int)$ResponsePayload["response_code"];

                        throw new Exception($ResponsePayload["error"]["message"], $ResponsePayload["error"]["error_code"]);
                    }
                }

                return $large_generalization;
            }

            if(isset($Parameters["generalization_size"]) == false)
            {
                $ResponsePayload = array(
                    "success" => false,
                    "response_code" => 400,
                    "error" => array(
                        "error_code" => 17,
                        "type" => "SERVER",
                        "message" => "Missing parameter 'generalization_size'"
                    )
                );
                $this->response_content = json_encode($ResponsePayload);
                $this->response_code = (int)$ResponsePayload["response_code"];

                throw new Exception($ResponsePayload["error"]["message"], $ResponsePayload["error"]["error_code"]);
            }
            else
            {
                $GeneralizationSize = (int)$Parameters["generalization_size"];

                if($GeneralizationSize <= 0)
                {
                    $ResponsePayload = array(
                        "success" => false,
                        "response_code" => 400,
                        "error" => array(
                            "error_code" => 15,
                            "type" => "CLIENT",
                            "message" => "The 'generalization_size' parameter cannot contain a value of 0 or negative"
                        )
                    );
                    $this->response_content = json_encode($ResponsePayload);
                    $this->response_code = (int)$ResponsePayload["response_code"];

                    throw new Exception($ResponsePayload["error"]["message"], $ResponsePayload["error"]["error_code"]);
                }

                // Set the current quota if it doesn't exist
                if(isset($this->access_record->Variables["MAX_GENERALIZATION_SIZE"]) == false)
                {
                    $ResponsePayload = array(
                        "success" => false,
                        "response_code" => 500,
                        "error" => array(
                            "error_code" => -1,
                            "type" => "SERVER",
                            "message" => "The server cannot process the variable 'MAX_GENERALIZATION_SIZE'"
                        )
                    );
                    $this->response_content = json_encode($ResponsePayload);
                    $this->response_code = (int)$ResponsePayload["response_code"];

                    throw new Exception($ResponsePayload["error"]["message"], $ResponsePayload["error"]["error_code"]);
                }

                if($GeneralizationSize > (int)$this->access_record->Variables["MAX_GENERALIZATION_SIZE"])
                {

                    $ResponsePayload = array(
                        "success" => false,
                        "response_code" => 400,
                        "error" => [
                            "error_code" => 16,
                            "type" => "CLIENT",
                            "message" => "You cannot exceed a generalization size of '" . $this->access_record->Variables["MAX_GENERALIZATION_SIZE"] . "' (Subscription restriction)"
                        ]
                    );
                    $this->response_content = json_encode($ResponsePayload);
                    $this->response_code = (int)$ResponsePayload["response_code"];

                    throw new Exception($ResponsePayload["error"]["message"], $ResponsePayload["error"]["error_code"]);
                }

                $large_generalization = $coffeeHouse->getLargeGeneralizedClassificationManager()->create($GeneralizationSize);
                return $large_generalization;
            }
        }

    }