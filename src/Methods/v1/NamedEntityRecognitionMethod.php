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
    use IntellivoidAPI\Exceptions\AccessRecordNotFoundException;
    use IntellivoidAPI\Exceptions\DatabaseException;
    use IntellivoidAPI\Exceptions\InvalidRateLimitConfiguration;
    use IntellivoidAPI\Exceptions\InvalidSearchMethodException;
    use IntellivoidAPI\IntellivoidAPI;
    use IntellivoidAPI\Objects\AccessRecord;
    use KimchiAPI\Abstracts\Method;
    use KimchiAPI\Abstracts\ResponseStandard;
    use KimchiAPI\Classes\Request;
    use KimchiAPI\Exceptions\AccessKeyNotProvidedException;
    use KimchiAPI\Exceptions\ApiException;
    use KimchiAPI\Exceptions\UnsupportedResponseStandardException;
    use KimchiAPI\Exceptions\UnsupportedResponseTypeExceptions;
    use KimchiAPI\KimchiAPI;
    use KimchiAPI\Objects\Response;
    use Methods\Classes\SubscriptionValidation;

    class NamedEntityRecognitionMethod extends Method
    {
        /**
         * @var AccessRecord
         */
        private $AccessRecord;

        /**
         * Process the quota for the subscription, returns false if the quota limit has been reached.
         *
         * @return Response|null
         */
        private function processQuota(): ?Response
        {
            // Set the current quota if it doesn't exist
            if(isset($this->AccessRecord->Variables["NER_CHECKS"]) == false)
            {
                $this->AccessRecord->setVariable("NER_CHECKS", 0);
            }

            // If the user has unlimited, ignore the check.
            if((int)$this->AccessRecord->Variables["MAX_NER_CHECKS"] > 0)
            {
                // If the current sessions are equal or greater
                if($this->AccessRecord->Variables["NER_CHECKS"] >= $this->AccessRecord->Variables["MAX_NER_CHECKS"])
                {
                    $Response = new Response();
                    $Response->Success = false;
                    $Response->ResponseCode = 429;
                    $Response->ErrorCode = 6;
                    $Response->ErrorMessage = 'You have reached the max quota for this method';
                    $Response->ResponseStandard = ResponseStandard::IntellivoidAPI;
                    return $Response;
                }
            }

            return null;
        }

        /**
         * Validates if the input is applicable to the NLP method
         *
         * @param string $input
         * @return Response|null
         * @noinspection DuplicatedCode
         */
        private function validateNlpInput(string $input): ?Response
        {
            if(isset($this->AccessRecord->Variables["MAX_NLP_CHARACTERS"]) == false)
            {
                $Response = new Response();
                $Response->Success = false;
                $Response->ResponseCode = 500;
                $Response->ErrorCode = -1;
                $Response->ErrorMessage = "The server cannot verify the value 'MAX_NLP_CHARACTERS'";
                $Response->ResponseStandard = ResponseStandard::IntellivoidAPI;
                return $Response;
            }

            if(strlen($input) > (int)$this->AccessRecord->Variables["MAX_NLP_CHARACTERS"])
            {
                $Response = new Response();
                $Response->Success = false;
                $Response->ResponseCode = 400;
                $Response->ErrorCode = 21;
                $Response->ErrorMessage = "The given input exceeds the limit of '" . $this->AccessRecord->Variables["MAX_NLP_CHARACTERS"] . "' characters. (Subscription restriction)";
                $Response->ResponseStandard = ResponseStandard::IntellivoidAPI;
                return $Response;
            }

            if(strlen($input) == 0)
            {
                $Response = new Response();
                $Response->Success = false;
                $Response->ResponseCode = 400;
                $Response->ErrorCode = 22;
                $Response->ErrorMessage = 'The given input cannot be empty';
                $Response->ResponseStandard = ResponseStandard::IntellivoidAPI;
                return $Response;
            }

            return null;
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
                        'type' => NamedEntityAlternativeValueTypes::Duration,
                        'duration' => [
                            'duration_type' => $duration->DurationType,
                            'value_unit' => $duration->ValueType,
                            'value' => $duration->Value
                        ]
                    ];

                case NamedEntityAlternativeValueTypes::Date:
                    /** @var DateType $date */
                    $date = $object;

                    return [
                        'type' => NamedEntityAlternativeValueTypes::Date,
                        'date' => [
                            'day' => $date->Day,
                            'month' => $date->Month,
                            'year' => $date->Year
                        ]
                    ];

                case NamedEntityAlternativeValueTypes::DateTime:
                    /** @var DateTimeType $date_time */
                    $date_time = $object;

                    return [
                        'type' => NamedEntityAlternativeValueTypes::DateTime,
                        'date' => [
                            'day' => $date_time->DateType->Day,
                            'month' => $date_time->DateType->Month,
                            'year' => $date_time->DateType->Year
                        ],
                        'time' => [
                            'hour'  => $date_time->TimeType->Hour,
                            'minute'  => $date_time->TimeType->Minute,
                            'seconds'  => $date_time->TimeType->Seconds
                        ]
                    ];

                case NamedEntityAlternativeValueTypes::Time:
                    /** @var TimeType $time = */
                    $time = $object;

                    return [
                        'type' => NamedEntityAlternativeValueTypes::Time,
                        'time' => [
                            'hour'  => $time->Hour,
                            'minute'  => $time->Minute,
                            'seconds'  => $time->Seconds
                        ]
                    ];

                default:
                case NamedEntityAlternativeValueTypes::None:
                    return [
                        'type' => NamedEntityAlternativeValueTypes::None
                    ];
            }
        }

        /**
         * Determines if the Named Entity type is available to this user subscription type.
         *
         * @param string $ner_type
         * @return bool
         * @throws ApiException
         * @throws UnsupportedResponseStandardException
         * @throws UnsupportedResponseTypeExceptions
         */
        private function determineIfAvailable(string $ner_type): bool
        {
            // Set the current quota if it doesn't exist
            if(isset($this->AccessRecord->Variables['LIMITED_NAMED_ENTITIES']) == false)
            {
                $Response = new Response();
                $Response->Success = false;
                $Response->ResponseCode = 500;
                $Response->ErrorCode = -1;
                $Response->ErrorMessage = "The server cannot verify the value 'LIMITED_NAMED_ENTITIES'";
                $Response->ResponseStandard = ResponseStandard::IntellivoidAPI;

                KimchiAPI::handleResponse($Response);
                return false;
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

            if((bool)$this->AccessRecord->Variables["LIMITED_NAMED_ENTITIES"] == true)
            {
                if(in_array($ner_type, $LimitedEntities))
                    return true;
            }

            if(in_array($ner_type, $LimitedEntities))
                return true;

            if(in_array($ner_type, $FullEntities))
                return true;

            return False;
        }

        /**
         * @return Response
         * @throws AccessKeyNotProvidedException
         * @throws ApiException
         * @throws UnsupportedResponseStandardException
         * @throws UnsupportedResponseTypeExceptions
         * @throws AccessRecordNotFoundException
         * @throws DatabaseException
         * @throws InvalidRateLimitConfiguration
         * @throws InvalidSearchMethodException
         * @noinspection DuplicatedCode
         */
        public function execute(): Response
        {
            /** @noinspection DuplicatedCode */
            $CoffeeHouse = new CoffeeHouse();
            $IntellivoidAPI = new IntellivoidAPI();
            $this->AccessRecord = \Methods\Classes\Utilities::authenticateUser($IntellivoidAPI, ResponseStandard::IntellivoidAPI);
            $SubscriptionValidation = new SubscriptionValidation();

            try
            {
                $SubscriptionValidation->validateUserSubscription($CoffeeHouse, $IntellivoidAPI, $this->AccessRecord);
            }
            catch (Exception $e)
            {
                KimchiAPI::handleException($e);
            }

            $process_quota_results = $this->processQuota();
            if($process_quota_results !== null)
                return $process_quota_results;

            $Parameters = Request::getParameters();

            if(isset($Parameters["input"]) == false)
            {
                $Response = new Response();
                $Response->Success = false;
                $Response->ResponseCode = 400;
                $Response->ErrorCode = 20;
                $Response->ErrorMessage = "Missing parameter 'input'";
                $Response->ResponseStandard = ResponseStandard::IntellivoidAPI;

                return $Response;
            }

            $validateInputResults = $this->validateNlpInput($Parameters['input']);
            if($validateInputResults !== null)
                return $validateInputResults;

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
                        $Response = new Response();
                        $Response->Success = false;
                        $Response->ResponseCode = 503;
                        $Response->ErrorCode = 13;
                        $Response->ErrorMessage = 'CoffeeHouse is temporarily unavailable';
                        $Response->ResponseStandard = ResponseStandard::IntellivoidAPI;
                        $Response->Exception = $e;
                        return $Response;
                    }
                    catch(Exception $e)
                    {
                        $Response = new Response();
                        $Response->Success = false;
                        $Response->ResponseCode = 500;
                        $Response->ErrorCode = 13;
                        $Response->ErrorMessage = 'There was an error while trying to auto-detect the language';
                        $Response->ResponseStandard = ResponseStandard::IntellivoidAPI;
                        $Response->Exception = $e;
                        return $Response;
                    }
                }

                try
                {
                    $source_language = Utilities::convertToISO6391($Parameters["language"]);
                }
                catch (InvalidLanguageException $e)
                {
                    $Response = new Response();
                    $Response->Success = false;
                    $Response->ResponseCode = 400;
                    $Response->ErrorCode = 7;
                    $Response->ErrorMessage = "The given language '" . $Parameters["language"] . "' cannot be identified";
                    $Response->ResponseStandard = ResponseStandard::IntellivoidAPI;
                    $Response->Exception = $e;
                    return $Response;
                }
            }

            if(in_array($source_language, \Methods\Classes\Utilities::getSupportedLanguages()) == false)
            {
                $Response = new Response();
                $Response->Success = false;
                $Response->ResponseCode = 503;
                $Response->ErrorCode = 13;
                $Response->ErrorMessage = "The given language '$source_language' is not supported";
                $Response->ResponseStandard = ResponseStandard::IntellivoidAPI;
                return $Response;
            }

            try
            {
                $NerResults = $CoffeeHouse->getCoreNLP()->ner($Parameters["input"], $source_language);
            }
            catch (CoffeeHouseUtilsNotReadyException $e)
            {
                $Response = new Response();
                $Response->Success = false;
                $Response->ResponseCode = 503;
                $Response->ErrorCode = 13;
                $Response->ErrorMessage = 'CoffeeHouse is temporarily unavailable';
                $Response->ResponseStandard = ResponseStandard::IntellivoidAPI;
                $Response->Exception = $e;
                return $Response;
            }
            catch (InvalidInputException | InvalidTextInputException | InvalidLanguageException $e)
            {
                $Response = new Response();
                $Response->Success = false;
                $Response->ResponseCode = 500;
                $Response->ErrorCode = 24;
                $Response->ErrorMessage = 'The given input cannot be processed';
                $Response->ResponseStandard = ResponseStandard::IntellivoidAPI;
                $Response->Exception = $e;
                return $Response;
            }
            catch(Exception $e)
            {
                $Response = new Response();
                $Response->Success = false;
                $Response->ResponseCode = 500;
                $Response->ErrorCode = -1;
                $Response->ErrorMessage = 'There was an unexpected error while trying to process your input';
                $Response->ResponseStandard = ResponseStandard::IntellivoidAPI;
                $Response->Exception = $e;
                return $Response;
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

                        if ($namedEntity->Type == NamedEntity::Number && is_string($namedEntity->Value))
                        {
                            $namedEntity->Value = (int)preg_replace('/[^0-9]/', '', $namedEntity->Value);
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

            $Response = new Response();
            $Response->Success = true;
            $Response->ResponseCode = 200;

            if($SentenceSplit)
            {
                $Response->ResultData = [
                    "text" => $NerResults->Text,
                    "source_language" => $source_language,
                    "sentences" => $SentencesResults
                ];
            }
            else
            {
                $Response->ResultData = [
                    "text" => $NerResults->Text,
                    "source_language" => $source_language,
                    "ner_tags" => $TokenResults
                ];
            }

            $this->AccessRecord->Variables["NER_CHECKS"] += 1;
            $CoffeeHouse->getDeepAnalytics()->tally("coffeehouse_api", "ner_checks", 0);
            $CoffeeHouse->getDeepAnalytics()->tally("coffeehouse_api", "ner_checks", $this->AccessRecord->ID);
            $IntellivoidAPI->getAccessKeyManager()->updateAccessRecord($this->AccessRecord);

            return $Response;
        }
    }