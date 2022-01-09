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

    use CoffeeHouse\Classes\Utilities;
    use CoffeeHouse\CoffeeHouse;
    use CoffeeHouse\Exceptions\CoffeeHouseUtilsNotReadyException;
    use CoffeeHouse\Exceptions\InvalidInputException;
    use CoffeeHouse\Exceptions\InvalidLanguageException;
    use CoffeeHouse\Exceptions\InvalidTextInputException;
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

    class PosTaggingMethod extends Method
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
            if(isset($this->AccessRecord->Variables["POS_CHECKS"]) == false)
            {
                $this->AccessRecord->setVariable("POS_CHECKS", 0);
            }

            // If the user has unlimited, ignore the check.
            if((int)$this->AccessRecord->Variables["MAX_POS_CHECKS"] > 0)
            {
                // If the current sessions are equal or greater
                if($this->AccessRecord->Variables["POS_CHECKS"] >= $this->AccessRecord->Variables["MAX_POS_CHECKS"])
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
                $Response->ErrorMessage = "The given input cannot be empty";
                $Response->ResponseStandard = ResponseStandard::IntellivoidAPI;

                return $Response;
            }

            return null;
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
            $IntellivoidAPI = new IntellivoidAPI();
            $CoffeeHouse = new CoffeeHouse();
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

            $validateNlpInputResults = $this->validateNlpInput($Parameters['input']);
            if($validateNlpInputResults !== null)
                return $validateNlpInputResults;

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
                        $Response->ErrorMessage = 'CoffeeHouse is temporary unavailable';
                        $Response->Exception = $e;
                        $Response->ResponseStandard = ResponseStandard::IntellivoidAPI;

                        return $Response;
                    }
                    catch(Exception $e)
                    {
                        $Response = new Response();
                        $Response->Success = false;
                        $Response->ResponseCode = 500;
                        $Response->ErrorCode = -1;
                        $Response->ErrorMessage =  'There was an error while trying to auto-detect the language';
                        $Response->Exception = $e;
                        $Response->ResponseStandard = ResponseStandard::IntellivoidAPI;

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
                    $Response->ErrorMessage = 'The given language ' . $Parameters['language'] . ' cannot be identified';
                    $Response->Exception = $e;
                    $Response->ResponseStandard = ResponseStandard::IntellivoidAPI;

                    return $Response;
                }
            }

            if(in_array($source_language, \Methods\Classes\Utilities::getSupportedLanguages()) == false)
            {
                $Response = new Response();
                $Response->Success = false;
                $Response->ResponseCode = 400;
                $Response->ErrorCode = 23;
                $Response->ErrorMessage = "The given language '$source_language' is not supported";
                $Response->ResponseStandard = ResponseStandard::IntellivoidAPI;

                return $Response;
            }

            try
            {
                $PosTagsResults = $CoffeeHouse->getCoreNLP()->posTag($Parameters["input"], $source_language);
            }
            catch (CoffeeHouseUtilsNotReadyException $e)
            {
                $Response = new Response();
                $Response->Success = false;
                $Response->ResponseCode = 503;
                $Response->ErrorCode = 13;
                $Response->ErrorMessage = 'CoffeeHouse is temporary unavailable';
                $Response->Exception = $e;
                $Response->ResponseStandard = ResponseStandard::IntellivoidAPI;

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

            foreach($PosTagsResults->PartOfSpeechSentences as $partOfSpeechSentence)
            {
                $pos_tags = [];
                foreach($partOfSpeechSentence->Tags as $posTag)
                {
                    $tag = [
                        "text" => $posTag->Word,
                        "offset_begin" => $posTag->CharacterOffsetBegin,
                        "offset_end" => $posTag->CharacterOffsetEnd,
                        "tag_value" => $posTag->Value
                    ];

                    $pos_tags[] = $tag;
                    $TokenResults[] = $tag;
                }

                $SentencesResults[] = [
                    "text" => $partOfSpeechSentence->Text,
                    "offset_begin" => $partOfSpeechSentence->OffsetBegin,
                    "offset_end" => $partOfSpeechSentence->OffsetEnd,
                    "tags" => $pos_tags
                ];
            }

            $Response = new Response();
            $Response->Success = true;
            $Response->ResponseCode = 200;

            if($SentenceSplit)
            {
                $Response->ResultData = [
                    "text" => $PosTagsResults->Text,
                    "source_language" => $source_language,
                    "sentences" => $SentencesResults
                ];
            }
            else
            {
                $Response->ResultData = [
                    "text" => $PosTagsResults->Text,
                    "source_language" => $source_language,
                    "tags" => $TokenResults
                ];
            }

            $this->AccessRecord->Variables["POS_CHECKS"] += 1;
            $CoffeeHouse->getDeepAnalytics()->tally("coffeehouse_api", "pos_checks", 0);
            $CoffeeHouse->getDeepAnalytics()->tally("coffeehouse_api", "pos_checks", $this->AccessRecord->ID);
            $IntellivoidAPI->getAccessKeyManager()->updateAccessRecord($this->AccessRecord);

            return $Response;
        }
    }