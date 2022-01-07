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
    use IntellivoidAPI\Exceptions\AccessRecordNotFoundException;
    use IntellivoidAPI\Exceptions\InvalidRateLimitConfiguration;
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

    class EmotionAnalysisMethod extends Method
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
            // Set the current quota if it doesn't SENTIMENT_CHECKS
            if(isset($this->AccessRecord->Variables["EMOTION_CHECKS"]) == false)
            {
                $this->AccessRecord->setVariable("EMOTION_CHECKS", 0);
            }

            // If the user has unlimited, ignore the check.
            if((int)$this->AccessRecord->Variables["MAX_EMOTION_CHECKS"] > 0)
            {
                // If the current sessions are equal or greater
                if($this->AccessRecord->Variables["EMOTION_CHECKS"] >= $this->AccessRecord->Variables["MAX_EMOTION_CHECKS"])
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
         * @return Response
         * @throws AccessKeyNotProvidedException
         * @throws ApiException
         * @throws UnsupportedResponseStandardException
         * @throws UnsupportedResponseTypeExceptions
         * @throws AccessRecordNotFoundException
         * @throws \IntellivoidAPI\Exceptions\DatabaseException
         * @throws InvalidRateLimitConfiguration
         * @throws \IntellivoidAPI\Exceptions\InvalidSearchMethodException
         * @noinspection DuplicatedCode
         */
        public function execute(): Response
        {
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

            if(isset($Parameters['input']) == false)
            {
                $Response = new Response();
                $Response->Success = false;
                $Response->ResponseCode = 400;
                $Response->ErrorCode = 20;
                $Response->ErrorMessage = "Missing parameter 'input'";
                $Response->ResponseStandard = ResponseStandard::IntellivoidAPI;
                return $Response;
            }

            $inputValidationResults = $this->validateNlpInput($Parameters["input"]);
            if($inputValidationResults !== null)
                return $inputValidationResults;

            $source_language = 'en';

            // Auto-Handle the language input
            if(isset($Parameters['language']))
            {
                if($Parameters['language'] == 'auto')
                {
                    try
                    {
                        $language_prediction_results = $CoffeeHouse->getLanguagePrediction()->predict($Parameters['input']);
                        $Parameters['language'] = $language_prediction_results->combineResults()[0]->Language;
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
                    $source_language = Utilities::convertToISO6391($Parameters['language']);
                }
                catch (InvalidLanguageException $e)
                {
                    $Response = new Response();
                    $Response->Success = false;
                    $Response->ResponseCode = 503;
                    $Response->ErrorCode = 13;
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
                $EmotionResults = $CoffeeHouse->getEmotionPrediction()->predictSentences($Parameters["input"], $source_language);
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
                $Response->ResponseCode = 400;
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
            $SentenceSplit = false;

            if(isset($Parameters['sentence_split']))
            {
                if((bool)strtolower($Parameters['sentence_split']) == true)
                {
                    $SentenceSplit = true;
                }
            }

            foreach($EmotionResults->EmotionPredictionSentences as $emotionPredictionSentence)
            {
                $predictions = [];
                foreach($emotionPredictionSentence->EmotionPredictionResults->toArray()['values'] as $emotion => $prediction)
                    $predictions[$emotion] = $prediction * 100;

                $SentencesResults[] = [
                    'text' => $emotionPredictionSentence->Text,
                    'offset_begin' => $emotionPredictionSentence->OffsetBegin,
                    'offset_end' => $emotionPredictionSentence->OffsetEnd,
                    'emotion' => [
                        'emotion' => $emotionPredictionSentence->EmotionPredictionResults->TopEmotion,
                        'prediction' => $emotionPredictionSentence->EmotionPredictionResults->TopValue * 100,
                        'predictions' => $predictions
                    ]
                ];
            }

            $predictions = [];
            foreach($EmotionResults->EmotionPrediction->toArray()['values'] as $emotion => $prediction)
                $predictions[$emotion] = $prediction * 100;

            $SingularResults = [
                'emotion' => $EmotionResults->EmotionPrediction->TopEmotion,
                'prediction' => $EmotionResults->EmotionPrediction->TopValue * 100,
                'predictions' => $predictions
            ];

            $Response = new Response();
            $Response->Success = true;
            $Response->ResponseCode = 200;

            if($SentenceSplit)
            {
                $Response->ResultData = [
                    'text' => $EmotionResults->Text,
                    'source_language' => $source_language,
                    'emotion' => $SingularResults,
                    'sentences' => $SentencesResults,
                    'generalization' => null
                ];
            }
            else
            {
                $Response->ResultData = [
                    'text' => $EmotionResults->Text,
                    'source_language' => $source_language,
                    'emotion' => $SingularResults,
                    'generalization' => null
                ];
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
                            'label' => $probability->Label,
                            'calculated_probability' => $probability->CalculatedProbability * 100,
                            'current_pointer' => $probability->CurrentPointer - 1,
                            'probabilities' => $probabilities_set
                        ];
                    }

                    $Response->ResultData['generalization'] = [
                        'id' => $generalization->PublicID,
                        'size' => $generalization->MaxProbabilitiesSize,
                        'top_label' => $generalization->TopLabel,
                        'top_probability' => $generalization->TopProbability,
                        'probabilities' => $probabilities_data
                    ];
                }
            }
            catch(Exception $e)
            {
               KimchiAPI::handleException($e);
            }

            $this->AccessRecord->Variables['EMOTION_CHECKS'] += 1;
            $CoffeeHouse->getDeepAnalytics()->tally('coffeehouse_api', 'emotion_checks', 0);
            $CoffeeHouse->getDeepAnalytics()->tally('coffeehouse_api', 'emotion_checks', $this->AccessRecord->ID);
            $IntellivoidAPI->getAccessKeyManager()->updateAccessRecord($this->AccessRecord);

            return $Response;
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
            $Parameters = Request::getParameters();

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
                    $Response = new Response();
                    $Response->Success = false;
                    $Response->ResponseCode = 404;
                    $Response->ErrorCode = 18;
                    $Response->ErrorMessage = 'The requested generalization data was not found';
                    $Response->ResponseStandard = ResponseStandard::IntellivoidAPI;
                    $Response->Exception = $e;

                    KimchiAPI::handleResponse($Response);
                }
                catch(Exception $e)
                {
                    $Response = new Response();
                    $Response->Success = false;
                    $Response->ResponseCode = 500;
                    $Response->ErrorCode = -1;
                    $Response->ErrorMessage = 'There was an unexpected error while trying to retrieve the generalization data from the server';
                    $Response->ResponseStandard = ResponseStandard::IntellivoidAPI;
                    $Response->Exception = $e;

                    KimchiAPI::handleResponse($Response);
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

                /** @noinspection PhpUndefinedVariableInspection */
                foreach($large_generalization->Probabilities as $probability)
                {
                    if(in_array($probability->Label, $labels) == false)
                        $labels[] = $probability->Label;
                }

                foreach($labels as $label)
                {
                    if(in_array($label, $applicable_labels) == false)
                    {
                        $Response = new Response();
                        $Response->Success = false;
                        $Response->ResponseCode = 400;
                        $Response->ErrorCode = 19;
                        $Response->ErrorMessage = 'This generalization set does not apply to this method';
                        $Response->ResponseStandard = ResponseStandard::IntellivoidAPI;

                        KimchiAPI::handleResponse($Response);
                    }
                }

                return $large_generalization;
            }

            if(isset($Parameters["generalization_size"]) == false)
            {
                $Response = new Response();
                $Response->Success = false;
                $Response->ResponseCode = 400;
                $Response->ErrorCode = 17;
                $Response->ErrorMessage = "Missing parameter 'generalization_size'";
                $Response->ResponseStandard = ResponseStandard::IntellivoidAPI;

                KimchiAPI::handleResponse($Response);
            }

            $GeneralizationSize = (int)$Parameters["generalization_size"];

            if($GeneralizationSize <= 0)
            {
                $Response = new Response();
                $Response->Success = false;
                $Response->ResponseCode = 400;
                $Response->ErrorCode = 15;
                $Response->ErrorMessage = "The 'generalization_size' parameter cannot contain a value of 0 or negative";
                $Response->ResponseStandard = ResponseStandard::IntellivoidAPI;

                KimchiAPI::handleResponse($Response);
            }

            // Set the current quota if it doesn't exist
            if(isset($this->AccessRecord->Variables["MAX_GENERALIZATION_SIZE"]) == false)
            {
                $Response = new Response();
                $Response->Success = false;
                $Response->ResponseCode = 500;
                $Response->ErrorCode = -1;
                $Response->ErrorMessage = "The server cannot process the variable 'MAX_GENERALIZATION_SIZE'";
                $Response->ResponseStandard = ResponseStandard::IntellivoidAPI;

                KimchiAPI::handleResponse($Response);
            }

            if($GeneralizationSize > (int)$this->AccessRecord->Variables["MAX_GENERALIZATION_SIZE"])
            {

                $Response = new Response();
                $Response->Success = false;
                $Response->ResponseCode = 500;
                $Response->ErrorCode = -1;
                $Response->ErrorMessage = "You cannot exceed a generalization size of '" . $this->AccessRecord->Variables["MAX_GENERALIZATION_SIZE"] . "' (Subscription restriction)";
                $Response->ResponseStandard = ResponseStandard::IntellivoidAPI;

                KimchiAPI::handleResponse($Response);
            }

            return $coffeeHouse->getLargeGeneralizedClassificationManager()->create($GeneralizationSize);
        }

    }