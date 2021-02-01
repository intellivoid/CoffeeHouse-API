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

    /**
     * Class language_detection
     * @package modules\v1
     */
    class language_detection extends Module implements Response
    {
        /**
         * The name of the module
         *
         * @var string
         */
        public string $name = "language_detection";

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
        public string $description = "Detects the language from an input";

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
            if(isset($this->access_record->Variables["LANGUAGE_CHECKS"]) == false)
            {
                $this->access_record->setVariable("LANGUAGE_CHECKS", 0);
            }

            // If the user has unlimited, ignore the check.
            if((int)$this->access_record->Variables["MAX_LANGUAGE_CHECKS"] > 0)
            {
                // If the current sessions are equal or greater
                if($this->access_record->Variables["LANGUAGE_CHECKS"] >= $this->access_record->Variables["MAX_LANGUAGE_CHECKS"])
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
                    "en", "tk", "xh","km","av","sn","rw","gu","kw","mi","tl","rn","te","as","yo","mk","zu","si","ka",
                    "ne","sd","hi","eu","ig","lo","fa","vi","br","or","ru","ur","ug","ku","tg","it","ar","kk","ba","et",
                    "tt","mr","ml","be","ja","tr","mn","sw","hy","om","pa","th","to","az","ko","so","id","mt","nn","nb",
                    "da","ro","sr","cy","gv","kn","bg","jv","ce","uk","gn","gd","cv","hu","pl","el","am","kv","ht","lg",
                    "la","no","uz","ta","sv","fi","sq","tn","yi","bn","dv","ca","ha","ga","cs","de","sk","nv","nl","ps",
                    "he","fy","sa","es","wo","is","lb","fo","ay","eo","ky","ie","bo","su","co","ms","hr","os","sc","io",
                    "bs","mg","af","sl","fr","wa","gl","qu","se","an","li","vo","ia","my","ln","lt","kl","lv","pt","oc",
                    "rm","zh-tw","zh-cn"
                ];

                $missing_items = 0;

                /** @noinspection DuplicatedCode */
                foreach($large_generalization->Probabilities as $probability)
                {
                    if(in_array($probability->Label, $labels) == false)
                        $labels[] = $probability->Label;
                }

                foreach($labels as $label)
                {
                    if(in_array($label, $applicable_labels) == false)
                    {
                        $missing_items += 1;
                    }
                }

                // If there's more than or equal to 5 missing items then this generalization may be invalid.
                if($missing_items >= 10)
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


            $SentenceSplit = false;

            if(isset($Parameters["sentence_split"]))
            {
                if((bool)strtolower($Parameters["sentence_split"]) == true)
                {
                    $SentenceSplit = true;
                }
            }

            try
            {
                if($SentenceSplit)
                {
                    $LanguageSentencesResults = $CoffeeHouse->getLanguagePrediction()->predictSentences($Parameters["input"], true);
                }
                else
                {
                    $LanguageResults = $CoffeeHouse->getLanguagePrediction()->predict($Parameters["input"]);
                }
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

            if($SentenceSplit)
            {
                $SentencesResults = [];

                foreach($LanguageSentencesResults->LanguagePredictionSentences as $languagePredictionSentence)
                {
                    $predictions = [];
                    $combined_results = $languagePredictionSentence->LanguagePredictionResults->combineResults();

                    foreach($combined_results as $datum)
                    {
                        $datum->updateProbability();
                        $predictions[$datum->Language] = $datum->Probability * 100;
                    }

                    $SentencesResults[] = [
                        "text" => $languagePredictionSentence->Text,
                        "offset_begin" => $languagePredictionSentence->OffsetBegin,
                        "offset_end" => $languagePredictionSentence->OffsetEnd,
                        "language_detection" => [
                            "language" => $combined_results[0]->Language,
                            "prediction" => $combined_results[0]->Probability * 100,
                            "predictions" => $predictions
                        ]
                    ];
                }

                $predictions = [];
                $combined_results = $LanguageSentencesResults->LanguagePrediction->combineResults();

                foreach($combined_results as $datum)
                {
                    $datum->updateProbability();
                    try
                    {
                        $predictions[Utilities::convertToISO6391($datum->Language)] = $datum->Probability * 100;
                    }
                    catch (InvalidLanguageException $e)
                    {
                        unset($e);
                    }
                }
            }
            else
            {
                $predictions = [];
                $combined_results = $LanguageResults->combineResults();

                foreach($combined_results as $datum)
                {
                    $datum->updateProbability();
                    try
                    {
                        $predictions[Utilities::convertToISO6391($datum->Language)] = $datum->Probability * 100;
                    }
                    catch (InvalidLanguageException $e)
                    {
                        unset($e);
                    }
                }
            }

            if($SentenceSplit)
            {
                /** @noinspection PhpUnhandledExceptionInspection */
                $ResponsePayload = array(
                    "success" => true,
                    "response_code" => 200,
                    "results" => [
                        "text" => $LanguageSentencesResults->Text,
                        "language_detection" => [
                            "language" => Utilities::convertToISO6391($combined_results[0]->Language),
                            "prediction" => $combined_results[0]->Probability * 100,
                            "predictions" => $predictions
                        ],
                        "sentences" => $SentencesResults,
                        "generalization" => null
                    ]
                );
            }
            else
            {
                /** @noinspection PhpUnhandledExceptionInspection */
                $ResponsePayload = array(
                    "success" => true,
                    "response_code" => 200,
                    "results" => [
                        "text" => $Parameters["input"],
                        "language_detection" => [
                            "language" => Utilities::convertToISO6391($combined_results[0]->Language),
                            "prediction" => $combined_results[0]->Probability * 100,
                            "predictions" => $predictions
                        ],
                        "generalization" => null
                    ]
                );
            }

            try
            {
                $generalization = $this->processGeneralization($CoffeeHouse);

                if($generalization !== null)
                {
                    $generalizationTarget = null;

                    if($SentenceSplit)
                    {
                        $generalizationTarget = $LanguageSentencesResults->LanguagePrediction;
                    }
                    else
                    {
                        $generalizationTarget = $LanguageResults;
                    }

                    $generalization = $CoffeeHouse->getLanguagePrediction()->generalize($generalization, $generalizationTarget);

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

            $this->access_record->Variables["LANGUAGE_CHECKS"] += 1;
            $CoffeeHouse->getDeepAnalytics()->tally("coffeehouse_api", "language_checks", 0);
            $CoffeeHouse->getDeepAnalytics()->tally("coffeehouse_api", "language_checks", $this->access_record->ID);

            return true;
        }

    }