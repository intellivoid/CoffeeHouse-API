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

    namespace Methods\v1;

    use CoffeeHouse\Abstracts\CoreNLP\Sentiment;
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
    use IntellivoidAPI\Objects\AccessRecord;
    use KimchiAPI\Abstracts\Method;
    use KimchiAPI\Objects\Response;
    use SubscriptionValidation;

    /**
     * Class sentiment_analysis
     * @package modules\v1
     */
    class SentimentAnalysisMethod extends Method
    {
        /**
         * Process the quota for the subscription, returns false if the quota limit has been reached.
         *
         * @return bool
         */
        private function processQuota(): bool
        {
            // Set the current quota if it doesn't SENTIMENT_CHECKS
            if(isset($this->AccessRecord->Variables["SENTIMENT_CHECKS"]) == false)
            {
                $this->AccessRecord->setVariable("SENTIMENT_CHECKS", 0);
            }

            // If the user has unlimited, ignore the check.
            if((int)$this->AccessRecord->Variables["MAX_SENTIMENT_CHECKS"] > 0)
            {
                // If the current sessions are equal or greater
                if($this->AccessRecord->Variables["SENTIMENT_CHECKS"] >= $this->AccessRecord->Variables["MAX_SENTIMENT_CHECKS"])
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
            if(isset($this->AccessRecord->Variables["MAX_NLP_CHARACTERS"]) == false)
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

            if(strlen($input) > (int)$this->AccessRecord->Variables["MAX_NLP_CHARACTERS"])
            {
                $ResponsePayload = array(
                    "success" => false,
                    "response_code" => 400,
                    "error" => array(
                        "error_code" => 21,
                        "type" => "CLIENT",
                        "message" => "The given input exceeds the limit of '" . $this->AccessRecord->Variables["MAX_NLP_CHARACTERS"] . "' characters. (Subscription restriction)"
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
        public function execute(): Response
        {
            $CoffeeHouse = new CoffeeHouse();

            // Import the check subscription script and execute it
            $SubscriptionValidation = new SubscriptionValidation();

            try
            {
                $ValidationResponse = $SubscriptionValidation->validateUserSubscription($CoffeeHouse, $this->AccessRecord);
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
                    catch (CoffeeHouseUtilsNotReadyException $e)
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
                    catch(Exception $e)
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
                catch (InvalidLanguageException $e)
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
                $SentimentResults = $CoffeeHouse->getCoreNLP()->sentiment($Parameters["input"], $source_language);
            }
            catch (CoffeeHouseUtilsNotReadyException $e)
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
            catch (InvalidInputException | InvalidTextInputException | InvalidLanguageException $e)
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
            catch(Exception $e)
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

            foreach($SentimentResults->SentimentSentences as $sentimentSentence)
            {
                $predictions = [];
                foreach($sentimentSentence->Sentiment->Predictions as $emotion => $prediction)
                    $predictions[$emotion] = $prediction * 100;

                $SentencesResults[] = [
                    "text" => $sentimentSentence->Text,
                    "offset_begin" => $sentimentSentence->OffsetBegin,
                    "offset_end" => $sentimentSentence->OffsetEnd,
                    "sentiment" => [
                        "sentiment" => $sentimentSentence->Sentiment->TopSentiment,
                        "prediction" => $sentimentSentence->Sentiment->TopPrediction * 100,
                        "predictions" => $predictions
                    ]
                ];
            }

            $predictions = [];
            foreach($SentimentResults->Sentiment->Predictions as $emotion => $prediction)
                $predictions[$emotion] = $prediction * 100;

            $SingularResults = [
                "sentiment" => $SentimentResults->Sentiment->TopSentiment,
                "prediction" => $SentimentResults->Sentiment->TopPrediction * 100,
                "predictions" => $predictions
            ];

            if($SentenceSplit)
            {
                $ResponsePayload = array(
                    "success" => true,
                    "response_code" => 200,
                    "results" => [
                        "text" => $SentimentResults->Text,
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
                        "text" => $SentimentResults->Text,
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
                    $generalization = $CoffeeHouse->getCoreNLP()->generalizeSentiment($generalization, $SentimentResults);

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
            catch(Exception $e)
            {
                // The request failed, already responded.
                return false;
            }

            $this->response_content = json_encode($ResponsePayload);
            $this->response_code = (int)$ResponsePayload["response_code"];

            $this->AccessRecord->Variables["SENTIMENT_CHECKS"] += 1;
            $CoffeeHouse->getDeepAnalytics()->tally("coffeehouse_api", "sentiment_checks", 0);
            $CoffeeHouse->getDeepAnalytics()->tally("coffeehouse_api", "sentiment_checks", $this->AccessRecord->ID);

            return true;
        }

        /**
         * @param CoffeeHouse $coffeeHouse
         * @return LargeGeneralization|null
         * @throws DatabaseException
         * @throws InvalidSearchMethodException
         * @throws NoResultsFoundException
         * @throws Exception
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
                catch (NoResultsFoundException $e)
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
                catch(Exception $e)
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
                    Sentiment::Neutral,
                    Sentiment::Negative,
                    Sentiment::Positive,
                    Sentiment::Unknown,
                    Sentiment::VeryNegative,
                    Sentiment::VeryPositive,
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
                if(isset($this->AccessRecord->Variables["MAX_GENERALIZATION_SIZE"]) == false)
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

                if($GeneralizationSize > (int)$this->AccessRecord->Variables["MAX_GENERALIZATION_SIZE"])
                {

                    $ResponsePayload = array(
                        "success" => false,
                        "response_code" => 400,
                        "error" => [
                            "error_code" => 16,
                            "type" => "CLIENT",
                            "message" => "You cannot exceed a generalization size of '" . $this->AccessRecord->Variables["MAX_GENERALIZATION_SIZE"] . "' (Subscription restriction)"
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