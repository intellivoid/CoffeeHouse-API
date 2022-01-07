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

    class LanguageDetectionMethod extends Method
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
            if(isset($this->AccessRecord->Variables['LANGUAGE_CHECKS']) == false)
            {
                $this->AccessRecord->setVariable('LANGUAGE_CHECKS', 0);
            }

            // If the user has unlimited, ignore the check.
            if((int)$this->AccessRecord->Variables['MAX_LANGUAGE_CHECKS'] > 0)
            {
                // If the current sessions are equal or greater
                if($this->AccessRecord->Variables['LANGUAGE_CHECKS'] >= $this->AccessRecord->Variables['MAX_LANGUAGE_CHECKS'])
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
                        $missing_items += 1;
                    }
                }

                // If there's more than or equal to 5 missing items then this generalization may be invalid.
                if($missing_items >= 10)
                {
                    $Response = new Response();
                    $Response->Success = false;
                    $Response->ResponseCode = 400;
                    $Response->ErrorCode = 19;
                    $Response->ErrorMessage = 'This generalization set does not apply to this method';
                    $Response->ResponseStandard = ResponseStandard::IntellivoidAPI;

                    KimchiAPI::handleResponse($Response);
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

        /**
         * @return Response
         * @throws InvalidLanguageException
         * @throws AccessRecordNotFoundException
         * @throws \IntellivoidAPI\Exceptions\DatabaseException
         * @throws InvalidRateLimitConfiguration
         * @throws \IntellivoidAPI\Exceptions\InvalidSearchMethodException
         * @throws AccessKeyNotProvidedException
         * @throws ApiException
         * @throws UnsupportedResponseStandardException
         * @throws UnsupportedResponseTypeExceptions
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

            $validateInputResults = $this->validateNlpInput($Parameters['input']);
            if($validateInputResults !== null)
                return $validateInputResults;

            $SentenceSplit = false;

            if(isset($Parameters['sentence_split']))
            {
                if((bool)strtolower($Parameters['sentence_split']) == true)
                {
                    $SentenceSplit = true;
                }
            }

            try
            {
                if($SentenceSplit)
                {
                    $LanguageSentencesResults = $CoffeeHouse->getLanguagePrediction()->predictSentences($Parameters['input'], true);
                }
                else
                {
                    $LanguageResults = $CoffeeHouse->getLanguagePrediction()->predict($Parameters['input']);
                }
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
                $Response->ErrorCode = 13;
                $Response->ErrorMessage = 'There was an error while trying to auto-detect the language';
                $Response->ResponseStandard = ResponseStandard::IntellivoidAPI;
                $Response->Exception = $e;
                return $Response;
            }
            catch(Exception $e)
            {
                KimchiAPI::handleException($e);
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
                        'text' => $languagePredictionSentence->Text,
                        'offset_begin' => $languagePredictionSentence->OffsetBegin,
                        'offset_end' => $languagePredictionSentence->OffsetEnd,
                        'language_detection' => [
                            'language' => $combined_results[0]->Language,
                            'prediction' => $combined_results[0]->Probability * 100,
                            'predictions' => $predictions
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

            $Response = new Response();
            $Response->Success = true;
            $Response->ResponseCode = 200;

            if($SentenceSplit)
            {
                $Response->ResultData = [
                    'text' => $LanguageSentencesResults->Text,
                    'language_detection' => [
                        'language' => Utilities::convertToISO6391($combined_results[0]->Language),
                        'prediction' => $combined_results[0]->Probability * 100,
                        'predictions' => $predictions
                    ],
                    'sentences' => $SentencesResults,
                    'generalization' => null
                ];
            }
            else
            {
                $Response->ResultData = [
                    'text' => $Parameters['input'],
                    'language_detection' => [
                        'language' => Utilities::convertToISO6391($combined_results[0]->Language),
                        'prediction' => $combined_results[0]->Probability * 100,
                        'predictions' => $predictions
                    ],
                    'generalization' => null
                ];
            }

            try
            {
                $generalization = $this->processGeneralization($CoffeeHouse);

                if($generalization !== null)
                {
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
                    /** @noinspection DuplicatedCode */
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

            $this->AccessRecord->Variables['LANGUAGE_CHECKS'] += 1;
            $CoffeeHouse->getDeepAnalytics()->tally('coffeehouse_api', 'language_checks', 0);
            $CoffeeHouse->getDeepAnalytics()->tally('coffeehouse_api', 'language_checks', $this->AccessRecord->ID);
            $IntellivoidAPI->getAccessKeyManager()->updateAccessRecord($this->AccessRecord);

            return $Response;
        }

    }