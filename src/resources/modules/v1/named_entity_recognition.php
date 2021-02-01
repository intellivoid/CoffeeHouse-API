<?php

    /** @noinspection PhpUnused */
    /** @noinspection PhpMissingFieldTypeInspection */

    namespace modules\v1;

    use CoffeeHouse\Abstracts\CoreNLP\NamedEntity;
    use CoffeeHouse\Abstracts\CoreNLP\NamedEntityAlternativeValueTypes;
    use CoffeeHouse\Classes\Utilities;
    use CoffeeHouse\CoffeeHouse;
    use CoffeeHouse\Exceptions\CoffeeHouseUtilsNotReadyException;
    use CoffeeHouse\Exceptions\InvalidInputException;
    use CoffeeHouse\Exceptions\InvalidLanguageException;
    use CoffeeHouse\Exceptions\InvalidTextInputException;
    use CoffeeHouse\Objects\ProcessedNLP\Types\DateTimeType;
    use CoffeeHouse\Objects\ProcessedNLP\Types\DateType;
    use CoffeeHouse\Objects\ProcessedNLP\Types\Duration;
    use CoffeeHouse\Objects\ProcessedNLP\Types\TimeType;
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
     * Class named_entity_recognition
     */
    class named_entity_recognition extends Module implements Response
    {
        /**
         * The name of the module
         *
         * @var string
         */
        public $name = "named_entity_recognition";

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
        public $description = "Detects named entities from the given input";

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
            if(isset($this->access_record->Variables["NER_CHECKS"]) == false)
            {
                $this->access_record->setVariable("NER_CHECKS", 0);
            }

            // If the user has unlimited, ignore the check.
            if((int)$this->access_record->Variables["MAX_NER_CHECKS"] > 0)
            {
                // If the current sessions are equal or greater
                if($this->access_record->Variables["NER_CHECKS"] >= $this->access_record->Variables["MAX_NER_CHECKS"])
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
         * Determines if the Named Entity type is available to this user subscription type.
         *
         * @param string $ner_type
         * @return bool
         */
        private function determineIfAvailable(string $ner_type): bool
        {
            // Set the current quota if it doesn't exist
            if(isset($this->access_record->Variables["LIMITED_NAMED_ENTITIES"]) == false)
            {
                $ResponsePayload = array(
                    "success" => false,
                    "response_code" => 500,
                    "error" => array(
                        "error_code" => -1,
                        "type" => "SERVER",
                        "message" => "The server cannot verify the value 'LIMITED_NAMED_ENTITIES'"
                    )
                );
                $this->response_content = json_encode($ResponsePayload);
                $this->response_code = (int)$ResponsePayload["response_code"];

                return False;
            }

            $LimitedEntities = [
                NamedEntity::Email,
                NamedEntity::Url,
                NamedEntity::City,
                NamedEntity::StateOrProvince,
                NamedEntity::Country,
                NamedEntity::Nationality,
                NamedEntity::Religion,
                NamedEntity::UsernameHandle
            ];

            $FullEntities = [
                NamedEntity::Person,
                NamedEntity::Location,
                NamedEntity::Organization,
                NamedEntity::Miscellaneous,
                NamedEntity::Money,
                NamedEntity::Number,
                NamedEntity::Percent,
                NamedEntity::Date,
                NamedEntity::Time,
                NamedEntity::CurrentTime,
                NamedEntity::Duration
            ];

            if((bool)$this->access_record->Variables["LIMITED_NAMED_ENTITIES"] == true)
            {
                if(in_array($ner_type, $LimitedEntities))
                    return true;
            }
            else
            {
                if(in_array($ner_type, $LimitedEntities))
                    return true;

                if(in_array($ner_type, $FullEntities))
                    return true;
            }

            return False;
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
                $NerResults = $CoffeeHouse->getCoreNLP()->ner($Parameters["input"], $source_language);
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
            $TokenResults = [];
            $SentenceSplit = false;

            if(isset($Parameters["sentence_split"]))
            {
                if((bool)strtolower($Parameters["sentence_split"]) == true)
                {
                    $SentenceSplit = true;
                }
            }

            foreach($NerResults->NamedEntitySentences as $namedEntitySentence)
            {
                $ner_tags = [];
                foreach($namedEntitySentence->NamedEntities as $namedEntity)
                {
                    if($this->determineIfAvailable($namedEntity->Type))
                    {
                        $alt_value = null;
                        if($namedEntity->AltValueType !== NamedEntityAlternativeValueTypes::None)
                        {
                            $alt_value = [];

                            if(is_array($namedEntity->AltValue))
                            {
                                if(count($namedEntity->AltValue) == 1)
                                {
                                    $alt_value[] = $this->altTypeToStandardArray($namedEntity->AltValue[0]->ObjectType, $namedEntity->AltValue[0]);
                                    $namedEntity->AltValueType = $namedEntity->AltValue[0]->ObjectType;
                                }
                                else
                                {
                                    $namedEntity->AltValueType = "MULTIPLE";
                                    foreach($namedEntity->AltValue as $item)
                                        $alt_value[] = $this->altTypeToStandardArray($item->ObjectType, $item);
                                }
                            }
                            else
                            {
                                $alt_value[] = $this->altTypeToStandardArray($namedEntity->AltValue->ObjectType, $namedEntity->AltValue);
                            }
                        }

                        switch($namedEntity->Type)
                        {
                            case NamedEntity::Number:
                                if(is_string($namedEntity->Value))
                                {
                                    $namedEntity->Value = (int)preg_replace('/[^0-9]/', '', $namedEntity->Value);
                                }
                        }

                        $tag = [
                            "text" => $namedEntity->Text,
                            "offset_begin" => $namedEntity->CharacterOffsetBegin,
                            "offset_end" => $namedEntity->CharacterOffsetEnd,
                            "prediction" => $namedEntity->Confidence,
                            "type" => $namedEntity->Type,
                            "value" => $namedEntity->Value,
                            "alt_values" => $alt_value
                        ];

                        $ner_tags[] = $tag;
                        $TokenResults[] = $tag;
                    }
                }

                $SentencesResults[] = [
                    "text" => $namedEntitySentence->Text,
                    "offset_begin" => $namedEntitySentence->OffsetBegin,
                    "offset_end" => $namedEntitySentence->OffsetEnd,
                    "ner_tags" => $ner_tags
                ];
            }

            if($SentenceSplit)
            {
                $ResponsePayload = array(
                    "success" => true,
                    "response_code" => 200,
                    "results" => [
                        "text" => $NerResults->Text,
                        "source_language" => $source_language,
                        "sentences" => $SentencesResults
                    ]
                );
            }
            else
            {
                $ResponsePayload = array(
                    "success" => true,
                    "response_code" => 200,
                    "results" => [
                        "text" => $NerResults->Text,
                        "source_language" => $source_language,
                        "ner_tags" => $TokenResults
                    ]
                );
            }

            $this->response_content = json_encode($ResponsePayload);
            $this->response_code = (int)$ResponsePayload["response_code"];

            $this->access_record->Variables["NER_CHECKS"] += 1;
            $CoffeeHouse->getDeepAnalytics()->tally("coffeehouse_api", "ner_checks", 0);
            $CoffeeHouse->getDeepAnalytics()->tally("coffeehouse_api", "ner_checks", $this->access_record->ID);

            return true;
        }

        /**
         * Converts an alternative value to a standard array for the response
         *
         * @param string $alt_type
         * @param $object
         * @return array
         */
        public function altTypeToStandardArray(string $alt_type, $object): array
        {
            switch($alt_type)
            {
                case NamedEntityAlternativeValueTypes::Duration:
                    /** @var Duration $duration */
                    $duration = $object;

                    return [
                        "type" => NamedEntityAlternativeValueTypes::Duration,
                        "duration" => [
                            "duration_type" => $duration->DurationType,
                            "value_unit" => $duration->ValueType,
                            "value" => $duration->Value
                        ]
                    ];

                case NamedEntityAlternativeValueTypes::Date:
                    /** @var DateType $date */
                    $date = $object;

                    return [
                        "type" => NamedEntityAlternativeValueTypes::Date,
                        "date" => [
                            "day" => $date->Day,
                            "month" => $date->Month,
                            "year" => $date->Year
                        ]
                    ];

                case NamedEntityAlternativeValueTypes::DateTime:
                    /** @var DateTimeType $date_time */
                    $date_time = $object;

                    return [
                        "type" => NamedEntityAlternativeValueTypes::DateTime,
                        "date" => [
                            "day" => $date_time->DateType->Day,
                            "month" => $date_time->DateType->Month,
                            "year" => $date_time->DateType->Year
                        ],
                        "time" => [
                            "hour"  => $date_time->TimeType->Hour,
                            "minute"  => $date_time->TimeType->Minute,
                            "seconds"  => $date_time->TimeType->Seconds
                        ]
                    ];

                case NamedEntityAlternativeValueTypes::Time:
                    /** @var TimeType $time = */
                    $time = $object;

                    return [
                        "type" => NamedEntityAlternativeValueTypes::Time,
                        "time" => [
                            "hour"  => $time->Hour,
                            "minute"  => $time->Minute,
                            "seconds"  => $time->Seconds
                        ]
                    ];

                default:
                case NamedEntityAlternativeValueTypes::None:
                    return [
                        "type" => NamedEntityAlternativeValueTypes::None
                    ];
            }
        }
    }