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
    use KimchiAPI\Abstracts\Method;
    use KimchiAPI\Objects\Response;
    use RuntimeException;
    use SubscriptionValidation;

    class NsfwClassificationMethod extends Method
    {
        /**
         * Process the quota for the subscription, returns false if the quota limit has been reached.
         *
         * @return bool
         */
        private function processQuota(): bool
        {
            // Set the current quota if it doesn't exist
            if(isset($this->access_record->Variables["NFW_CHECKS"]) == false)
            {
                $this->access_record->setVariable("NFW_CHECKS", 0);
            }

            // If the user has unlimited, ignore the check.
            if((int)$this->access_record->Variables["MAX_NSFW_CHECKS"] > 0)
            {
                // If the current sessions are equal or greater
                if($this->access_record->Variables["NFW_CHECKS"] >= $this->access_record->Variables["MAX_NSFW_CHECKS"])
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

        /**
         * Processes the image upload
         *
         * @param CoffeeHouse $coffeeHouse
         * @param string $input
         * @return NsfwClassificationResults
         * @throws Exception
         */
        public function processClassification(CoffeeHouse $coffeeHouse, string $input): NsfwClassificationResults
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
                        // Do nothing!
                    }
                }
                else
                {
                    $results = $coffeeHouse->getNsfwClassification()->classifyImage($input, True);
                }

                $ResponsePayload = array(
                    "success" => true,
                    "response_code" => 200,
                    "results" => array(
                        "nsfw_classification" => [
                            "content_hash" => $results->ContentHash,
                            "content_type" => $results->ImageType,
                            "safe_prediction" => $results->SafePrediction * 100,
                            "unsafe_prediction" => $results->UnsafePrediction * 100,
                            "is_nsfw" => $results->IsNSFW
                        ],
                        "generalization" => null,
                    )
                );

                $coffeeHouse->getDeepAnalytics()->tally("coffeehouse_api", "nsfw_classifications", 0);
                $coffeeHouse->getDeepAnalytics()->tally("coffeehouse_api", "nsfw_classifications", $this->access_record->ID);
                $this->response_content = json_encode($ResponsePayload);
                $this->response_code = (int)$ResponsePayload["response_code"];

                return $results;
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

                throw new Exception($ResponsePayload["error"]["message"], $ResponsePayload["error"]["error_code"]);

            }
            catch (UnsupportedImageTypeException $e)
            {
                $ResponsePayload = array(
                    "success" => false,
                    "response_code" => 400,
                    "error" => array(
                        "error_code" => 12,
                        "type" => "CLIENT",
                        "message" => "The file type isn't supported for this method"
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
                        "message" => "There was an unexpected error while trying to process your request"
                    )
                );
                $this->response_content = json_encode($ResponsePayload);
                $this->response_code = (int)$ResponsePayload["response_code"];

                throw new Exception($ResponsePayload["error"]["message"], $ResponsePayload["error"]["error_code"]);
            }
        }

        /**
         * Checks if the base64 field is valid or not
         *
         * @return string
         */
        public function checkBase64Field(): string
        {
            if(isset(Handler::getParameters(true, true)["image"]) == false)
            {
                throw new RuntimeException("File not uploaded", 50);
            }

            $Data =  Handler::getParameters(true, true)["image"];

            try
            {
                $content = base64_decode($Data, true);

                if($content == false)
                    throw new RuntimeException("Invalid base64 data", 53);
            }
            catch(Exception $e)
            {
                throw new RuntimeException("Invalid base64 data", 53);
            }

            if(strlen($content) > 8388608)
            {
                throw new RuntimeException("Exceeded filesize limit.", 51);
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
            if(isset($_FILES["image"]) == false)
            {
                throw new RuntimeException("File not uploaded", 50);
            }

            // Undefined | Multiple Files | $_FILES Corruption Attack
            // If this request falls under any of them, treat it invalid.
            if (!isset($_FILES["image"]["error"]) || is_array($_FILES["image"]["error"]))
            {
                throw new RuntimeException("File not uploaded", 50);
            }

            // Check $_FILES['upfile']['error'] value.
            switch ($_FILES["image"]["error"])
            {
                case UPLOAD_ERR_OK:
                    break;

                case UPLOAD_ERR_INI_SIZE:
                case UPLOAD_ERR_FORM_SIZE:
                    throw new RuntimeException("Exceeded filesize limit.", 51);

                default:
                    throw new RuntimeException("File upload error", 52);
            }

            // You should also check filesize here.
            if ($_FILES['image']['size'] > 8388608)
            {
                throw new RuntimeException("Exceeded filesize limit.", 51);
            }

            return $_FILES["image"]["tmp_name"];
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

            $image_content = null;

            try
            {
                $image_content = $this->checkUpload();
            }
            catch(RuntimeException $e)
            {
                $ResponsePayload = array(
                    "success" => false,
                    "response_code" => null,
                    "error" => array(
                        "error_code" => null,
                        "type" => "CLIENT",
                        "message" => null
                    )
                );

                switch($e->getCode())
                {
                    case 51:
                        $ResponsePayload["response_code"] = 413;
                        $ResponsePayload["error"]["error_code"] = 9;
                        $ResponsePayload["error"]["message"] = "File content too large";

                        $this->response_content = json_encode($ResponsePayload);
                        $this->response_code = (int)$ResponsePayload["response_code"];

                        return false;

                    case 52:
                        $ResponsePayload["response_code"] = 500;
                        $ResponsePayload["error"]["error_code"] = 10;
                        $ResponsePayload["error"]["message"] = "File upload error";

                        $this->response_content = json_encode($ResponsePayload);
                        $this->response_code = (int)$ResponsePayload["response_code"];

                        return false;

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
                    $ResponsePayload = array(
                        "success" => false,
                        "response_code" => null,
                        "error" => array(
                            "error_code" => null,
                            "type" => "CLIENT",
                            "message" => null
                        )
                    );

                    switch($e->getCode())
                    {
                        case 51:
                            $ResponsePayload["response_code"] = 413;
                            $ResponsePayload["error"]["error_code"] = 9;
                            $ResponsePayload["error"]["message"] = "File content too large";

                            $this->response_content = json_encode($ResponsePayload);
                            $this->response_code = (int)$ResponsePayload["response_code"];

                            return false;

                        case 52:
                            $ResponsePayload["response_code"] = 500;
                            $ResponsePayload["error"]["error_code"] = 10;
                            $ResponsePayload["error"]["message"] = "File upload error";

                            $this->response_content = json_encode($ResponsePayload);
                            $this->response_code = (int)$ResponsePayload["response_code"];

                            return false;

                        case 53:
                            $ResponsePayload["response_code"] = 400;
                            $ResponsePayload["error"]["error_code"] = 11;
                            $ResponsePayload["error"]["message"] = "Invalid base64 data";

                            $this->response_content = json_encode($ResponsePayload);
                            $this->response_code = (int)$ResponsePayload["response_code"];

                            return false;

                        default:
                            break;
                    }
                }
            }


            if($image_content == null)
            {
                $ResponsePayload = array(
                    "success" => false,
                    "response_code" => 400,
                    "error" => array(
                        "error_code" => 14,
                        "type" => "CLIENT",
                        "message" => "Missing file upload or data field 'image'"
                    )
                );
                $this->response_content = json_encode($ResponsePayload);
                $this->response_code = (int)$ResponsePayload["response_code"];

                return false;
            }


            try
            {
                $classificationResults = $this->processClassification($CoffeeHouse, $image_content);
            }
            catch(Exception $e)
            {
                // The request failed, already responded.
                return false;
            }

            try
            {
                $generalization = $this->processGeneralization($CoffeeHouse);

                if($generalization !== null)
                {
                    $generalization = $CoffeeHouse->getNsfwClassification()->generalize($generalization, $classificationResults);

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

                    $ResponsePayload = json_decode($this->response_content, true);
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

            $this->access_record->Variables["NFW_CHECKS"] += 1;
            $CoffeeHouse->getDeepAnalytics()->tally("coffeehouse_api", "nsfw_classifications", 0);
            $CoffeeHouse->getDeepAnalytics()->tally("coffeehouse_api", "nsfw_classifications", $this->access_record->ID);

            return true;
        }
    }