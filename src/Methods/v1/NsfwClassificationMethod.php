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
    use CoffeeHouse\CoffeeHouse;
    use CoffeeHouse\Exceptions\CoffeeHouseUtilsNotReadyException;
    use CoffeeHouse\Exceptions\DatabaseException;
    use CoffeeHouse\Exceptions\InvalidSearchMethodException;
    use CoffeeHouse\Exceptions\NoResultsFoundException;
    use CoffeeHouse\Exceptions\UnsupportedImageTypeException;
    use CoffeeHouse\Objects\LargeGeneralization;
    use CoffeeHouse\Objects\Results\NsfwClassificationResults;
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
    use Methods\Classes\Utilities;
    use RuntimeException;

    class NsfwClassificationMethod extends Method
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
            if(isset($this->AccessRecord->Variables["NFW_CHECKS"]) == false)
            {
                $this->AccessRecord->setVariable("NFW_CHECKS", 0);
            }

            // If the user has unlimited, ignore the check.
            if((int)$this->AccessRecord->Variables["MAX_NSFW_CHECKS"] > 0)
            {
                // If the current sessions are equal or greater
                if($this->AccessRecord->Variables["NFW_CHECKS"] >= $this->AccessRecord->Variables["MAX_NSFW_CHECKS"])
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
         * @param CoffeeHouse $coffeeHouse
         * @return LargeGeneralization|null
         * @throws DatabaseException
         * @throws InvalidSearchMethodException
         * @throws NoResultsFoundException
         * @throws Exception
         * @noinspection DuplicatedCode
         * @noinspection PhpUndefinedVariableInspection
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
                    "safe",
                    "unsafe"
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

        /**
         * Processes the image upload
         *
         * @param CoffeeHouse $coffeeHouse
         * @param Response $Response
         * @param string $input
         * @return NsfwClassificationResults|null
         */
        public function processClassification(CoffeeHouse $coffeeHouse, Response &$Response, string $input): ?NsfwClassificationResults
        {
            try
            {
                if(file_exists($input))
                {
                    $results = $coffeeHouse->getNsfwClassification()->classifyImageFile($input, True);

                    try
                    {
                        // Save disk-space
                        unlink($input);
                    }
                    catch(Exception $e)
                    {
                        unset($e);
                        // Do nothing!
                    }
                }
                else
                {
                    $results = $coffeeHouse->getNsfwClassification()->classifyImage($input, True);
                }

                $Response->ResultData = [
                    'nsfw_classification' => [
                        'content_hash' => $results->ContentHash,
                        'content_type' => $results->ImageType,
                        'safe_prediction' => $results->SafePrediction * 100,
                        'unsafe_prediction' => $results->UnsafePrediction * 100,
                        'is_nsfw' => $results->IsNSFW
                    ],
                    'generalization' => null
                ];

                $coffeeHouse->getDeepAnalytics()->tally('coffeehouse_api', 'nsfw_classifications', 0);
                $coffeeHouse->getDeepAnalytics()->tally('coffeehouse_api', 'nsfw_classifications', $this->AccessRecord->ID);

                return $results;
            }
            catch (CoffeeHouseUtilsNotReadyException $e)
            {
                $Response->Success = false;
                $Response->ResponseCode = 503;
                $Response->Exception =  $e;
                $Response->ErrorMessage = 'CoffeeHouse-Utils is temporarily unavailable';
                $Response->ErrorCode = 13;

                return null;

            }
            catch (UnsupportedImageTypeException $e)
            {
                $Response->Success = false;
                $Response->ResponseCode = 400;
                $Response->Exception =  $e;
                $Response->ErrorMessage = "The file type isn't supported for this method";
                $Response->ErrorCode = 12;

                return null;
            }
            catch(Exception $e)
            {
                $Response->Success = false;
                $Response->ResponseCode = 500;
                $Response->Exception =  $e;
                $Response->ErrorMessage = 'There was an unexpected error while trying to process your request';
                $Response->ErrorCode = -1;

                return null;
            }
        }

        /**
         * Checks if the base64 field is valid or not
         *
         * @return string
         */
        public function checkBase64Field(): string
        {
            $Parameters = Request::getParameters();
            if(isset($Parameters['image']) == false)
            {
                throw new RuntimeException('File not uploaded', 50);
            }

            $Data =  $Parameters['image'];

            try
            {
                $content = base64_decode($Data, true);

                if($content == false)
                    throw new RuntimeException('Invalid base64 data', 53);
            }
            catch(Exception $e)
            {
                throw new RuntimeException('Invalid base64 data', 53);
            }

            if(strlen($content) > 8388608)
            {
                throw new RuntimeException('Exceeded filesize limit.', 51);
            }

            return $content;
        }

        /**
         * Checks if a file has been uploaded using the `image` field
         *
         * @return string
         */
        public function checkUpload(): string
        {
            if(isset($_FILES['image']) == false)
            {
                throw new RuntimeException('File not uploaded', 50);
            }

            // Undefined | Multiple Files | $_FILES Corruption Attack
            // If this request falls under any of them, treat it invalid.
            if (!isset($_FILES['image']['error']) || is_array($_FILES['image']['error']))
            {
                throw new RuntimeException('File not uploaded', 50);
            }

            // Check $_FILES['upfile']['error'] value.
            switch ($_FILES['image']['error'])
            {
                case UPLOAD_ERR_OK:
                    break;

                case UPLOAD_ERR_INI_SIZE:
                case UPLOAD_ERR_FORM_SIZE:
                    throw new RuntimeException('Exceeded filesize limit.', 51);

                default:
                    throw new RuntimeException('File upload error', 52);
            }

            // You should also check filesize here.
            if ($_FILES['image']['size'] > 8388608)
            {
                throw new RuntimeException('Exceeded filesize limit.', 51);
            }

            return $_FILES['image']['tmp_name'];
        }

        /**
         * @return Response
         * @throws AccessRecordNotFoundException
         * @throws \IntellivoidAPI\Exceptions\DatabaseException
         * @throws InvalidRateLimitConfiguration
         * @throws \IntellivoidAPI\Exceptions\InvalidSearchMethodException
         * @throws AccessKeyNotProvidedException
         * @throws ApiException
         * @throws UnsupportedResponseStandardException
         * @throws UnsupportedResponseTypeExceptions
         * @noinspection DuplicatedCode
         */
        public function execute(): Response
        {
            $IntellivoidAPI = new IntellivoidAPI();
            $CoffeeHouse = new CoffeeHouse();
            $this->AccessRecord = Utilities::authenticateUser($IntellivoidAPI, ResponseStandard::IntellivoidAPI);
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


            $image_content = null;

            try
            {
                $image_content = $this->checkUpload();
            }
            catch(RuntimeException $e)
            {
                $Response = new Response();
                $Response->Success = false;
                $Response->ResponseCode = 400;
                $Response->Exception = $e;
                $Response->ResponseStandard = ResponseStandard::IntellivoidAPI;

                switch($e->getCode())
                {
                    case 51:
                        $Response->ResponseCode = 413;
                        $Response->ErrorCode = 9;
                        $Response->ErrorMessage = 'File content too large';
                        return $Response;

                    case 52:
                        $Response->ResponseCode = 500;
                        $Response->ErrorCode = 10;
                        $Response->ErrorMessage = 'File upload error';
                        return $Response;

                    default:
                        break;
                }
            }

            if($image_content == null)
            {
                try
                {
                    $image_content = $this->checkBase64Field();
                }
                catch(RuntimeException $e)
                {
                    $Response = new Response();
                    $Response->Success = false;
                    $Response->ResponseCode = 400;
                    $Response->Exception = $e;
                    $Response->ResponseStandard = ResponseStandard::IntellivoidAPI;

                    switch($e->getCode())
                    {
                        case 51:
                            $Response->ResponseCode = 413;
                            $Response->ErrorCode = 9;
                            $Response->ErrorMessage = 'File content too large';
                            return $Response;

                        case 52:
                            $Response->ResponseCode = 500;
                            $Response->ErrorCode = 10;
                            $Response->ErrorMessage = 'File upload error';
                            return $Response;

                        case 53:
                            $Response->ResponseCode = 400;
                            $Response->ErrorCode = 11;
                            $Response->ErrorMessage = 'Invalid base64 data';
                            return $Response;

                        default:
                            break;
                    }
                }
            }

            $Response = new Response();
            $Response->Success = true;
            $Response->ResponseCode = 200;
            $Response->ResponseStandard = ResponseStandard::IntellivoidAPI;

            if($image_content == null)
            {
                $Response->Success = false;
                $Response->ResponseCode = 400;
                $Response->ErrorCode = 14;
                $Response->ErrorMessage = "Missing file upload or data field 'image'";
                $Response->ResponseStandard = ResponseStandard::IntellivoidAPI;

                return $Response;
            }

            try
            {
                $classificationResults = $this->processClassification($CoffeeHouse, $Response, $image_content);
            }
            catch(Exception $e)
            {
                KimchiAPI::handleException($e);
            }

            if($Response->Success == false)
                return $Response;

            try
            {
                $generalization = $this->processGeneralization($CoffeeHouse);

                if($generalization !== null)
                {
                    $generalization = $CoffeeHouse->getNsfwClassification()->generalize($generalization, $classificationResults);
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

                    $Response->ResultData["generalization"] = [
                        "id" => $generalization->PublicID,
                        "size" => $generalization->MaxProbabilitiesSize,
                        "top_label" => $generalization->TopLabel,
                        "top_probability" => $generalization->TopProbability,
                        "probabilities" => $probabilities_data
                    ];
                }
            }
            catch(Exception $e)
            {
                KimchiAPI::handleException($e);
            }

            $this->AccessRecord->Variables['NFW_CHECKS'] += 1;
            $CoffeeHouse->getDeepAnalytics()->tally('coffeehouse_api', 'nsfw_classifications', 0);
            $CoffeeHouse->getDeepAnalytics()->tally('coffeehouse_api', 'nsfw_classifications', $this->AccessRecord->ID);
            $IntellivoidAPI->getAccessKeyManager()->updateAccessRecord($this->AccessRecord);

            return $Response;
        }
    }