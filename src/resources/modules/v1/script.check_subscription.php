<?php     /*
     * Copyright (c) 2017-2021. Intellivoid Technologies
     *
     * All rights reserved, this is a closed-source solution written by Zi Xing Narrakas,
     *  under no circumstances is any entity with access to this file should redistribute
     *  without written permission from Intellivoid and or the original Author.
     */ /** @noinspection PhpMissingFieldTypeInspection */

    use COASniffle\Exceptions\CoaAuthenticationException;
    use COASniffle\Handlers\COA;
    use CoffeeHouse\Abstracts\UserSubscriptionSearchMethod;
    use CoffeeHouse\CoffeeHouse;
    use CoffeeHouse\Exceptions\UserSubscriptionNotFoundException;
    use CoffeeHouse\Objects\UserSubscription;
    use Handler\GenericResponses\InternalServerError;
    use Handler\Handler;
    use IntellivoidAPI\Exceptions\AccessRecordNotFoundException;
    use IntellivoidAPI\Exceptions\DatabaseException;
    use IntellivoidAPI\Exceptions\InvalidRateLimitConfiguration;
    use IntellivoidAPI\Exceptions\InvalidSearchMethodException;
    use IntellivoidAPI\Objects\AccessRecord;
    use IntellivoidSubscriptionManager\Abstracts\SearchMethods\SubscriptionSearchMethod;
    use IntellivoidSubscriptionManager\Exceptions\SubscriptionNotFoundException;
    use IntellivoidSubscriptionManager\IntellivoidSubscriptionManager;
    use IntellivoidSubscriptionManager\Objects\Subscription;
    use IntellivoidSubscriptionManager\Utilities\Converter;

    /**
     * This script gets executed by the main modules to determine if the subscription
     * is active or not. This has been written by Zi Xing for IVA2.0 & COA
     *
     * Version 1.0.0.0
     */
    class SubscriptionValidation
    {
        /**
         * @var Subscription
         */
        public $subscription;

        /**
         * @var UserSubscription
         */
        public $user_subscription;

        /**
         * @var IntellivoidSubscriptionManager
         */
        public $intellivoid_subscription_manager;

        /**
         * Processes the access key and determines if it used against a valid subscription.
         *
         * @param CoffeeHouse $coffeeHouse
         * @param AccessRecord $access_record
         * @return null|array
         * @throws AccessRecordNotFoundException
         * @throws InvalidRateLimitConfiguration
         * @throws DatabaseException
         * @throws InvalidSearchMethodException
         */
        public function validateUserSubscription(CoffeeHouse $coffeeHouse, AccessRecord $access_record): ?array
        {
            try
            {
                $UserSubscription = $coffeeHouse->getUserSubscriptionManager()->getUserSubscription(
                    UserSubscriptionSearchMethod::byAccessRecordID, $access_record->ID
                );
            }
            catch (UserSubscriptionNotFoundException $e)
            {
                return $this::buildResponse(array(
                    "success" => false,
                    "response_code" => 500,
                    "error" => array(
                            "error_code" => 0,
                            "type" => "SUBSCRIPTION",
                            "message" => "There was an error while trying to verify your subscription, the system couldn't find your subscription."
                        )
                    ),
                    500,
                    array(
                        "access_record" => $access_record->toArray()
                    )
                );

            }
            catch(Exception $e)
            {
                InternalServerError::executeResponse($e);
                exit();
            }

            $IntellivoidSubscriptionManager = new IntellivoidSubscriptionManager();
            $this->intellivoid_subscription_manager = $IntellivoidSubscriptionManager;

            try
            {
                $Subscription = $IntellivoidSubscriptionManager->getSubscriptionManager()->getSubscription(
                    SubscriptionSearchMethod::byId, $UserSubscription->SubscriptionID
                );
            }
            catch (SubscriptionNotFoundException $e)
            {
                return $this::buildResponse(array(
                    "success" => false,
                    "response_code" => 403,
                    "error" => array(
                            "error_code" => 0,
                            "type" => "SUBSCRIPTION",
                            "message" => "You do not have an active subscription with this service"
                        )
                    ),
                    403,
                    array(
                        "access_record" => $access_record->toArray(),
                        "user_subscription" => $UserSubscription->toArray()
                    )
                );
            }
            catch(Exception $e)
            {
                InternalServerError::executeResponse($e);
                exit();
            }

            // PATCH: Sometimes if a user wants to upgrade their subscription, the features are not yet applied.
            $Features = Converter::featuresToSA($Subscription->Properties->Features, true);

            if(self::updateSubscriptionFeatures($Features, $access_record) == false)
            {
                return $this::buildResponse(array(
                    "success" => false,
                    "response_code" => 403,
                    "error" => array(
                        "error_code" => 0,
                        "type" => "SUBSCRIPTION",
                        "message" => "There are new updates to your subscription, login to the dashboard to update your subscription."
                    )
                ), 403, array(
                        "access_record" => $access_record->toArray(),
                        "user_subscription" => $UserSubscription->toArray()
                    )
                );
            }

            if((int)time() > $Subscription->NextBillingCycle)
            {
                new COASniffle\COASniffle();

                try
                {
                    $BillingProcessed = COA::processSubscriptionBilling($Subscription->ID);
                }
                catch (CoaAuthenticationException $e)
                {
                    return $this::buildResponse(array(
                        "success" => false,
                        "response_code" => 500,
                        "error" => array(
                                "error_code" => 0,
                                "type" => "SUBSCRIPTION",
                                "message" => $e->getMessage()
                            )
                        ), 500, array(
                            "access_record" => $access_record->toArray(),
                            "user_subscription" => $UserSubscription->toArray()
                        )
                    );
                }
                catch(Exception $e)
                {
                    InternalServerError::executeResponse($e);
                    exit();
                }

                if($BillingProcessed)
                {
                    // Reset all counters
                    $access_record->Variables["LYDIA_SESSIONS"] = 0;
                    $access_record->Variables["NFW_CHECKS"] = 0;
                    $access_record->Variables["POS_CHECKS"] = 0;
                    $access_record->Variables["SENTIMENT_CHECKS"] = 0;
                    $access_record->Variables["EMOTION_CHECKS"] = 0;
                    $access_record->Variables["SPAM_CHECKS"] = 0;
                    $access_record->Variables["SENTENCE_SPLITS"] = 0;
                    $access_record->Variables["LANGUAGE_CHECKS"] = 0;
                    $access_record->Variables["NER_CHECKS"] = 0;
                }
            }

            $IntellivoidAPI = Handler::getIntellivoidAPI();
            $IntellivoidAPI->getAccessKeyManager()->updateAccessRecord($access_record);

            $this->subscription = $Subscription;
            $this->user_subscription = $UserSubscription;

            return null;
        }

        /**
         * Updates all the API Variables from the subscription to make sure it's up to date.
         *
         * @param array $features
         * @param AccessRecord $access_record
         * @return bool
         */
        private static function updateSubscriptionFeatures(array $features, AccessRecord &$access_record): bool
        {
            if(isset($features["LYDIA_SESSIONS"]))
            {
                $access_record->Variables["MAX_LYDIA_SESSIONS"] = (int)$features["LYDIA_SESSIONS"];

                if(isset($access_record->Variables["LYDIA_SESSIONS"]) == false)
                    $access_record->Variables["LYDIA_SESSIONS"] = 0;
            }
            else
            {
                return false;
            }

            if(isset($features["MAX_NLP_CHARACTERS"]))
            {
                $access_record->Variables["MAX_NLP_CHARACTERS"] = (int)$features["MAX_NLP_CHARACTERS"];
            }
            else
            {
               return false;
            }

            if(isset($features["MAX_GENERALIZATION_SIZE"]))
            {
                $access_record->Variables["MAX_GENERALIZATION_SIZE"] = (int)$features["MAX_GENERALIZATION_SIZE"];
            }
            else
            {
                return false;
            }

            if(isset($features["MAX_NSFW_CHECKS"]))
            {
                $access_record->Variables["MAX_NSFW_CHECKS"] = (int)$features["MAX_NSFW_CHECKS"];

                if(isset($access_record->Variables["NFW_CHECKS"]) == false)
                    $access_record->Variables["NFW_CHECKS"] = 0;
            }
            else
            {
                return false;
            }

            if(isset($features["LIMITED_NAMED_ENTITIES"]))
            {
                // True = 8 Named Entities
                // False = 19 Named Entities
                $access_record->Variables["LIMITED_NAMED_ENTITIES"] = (bool)$features["LIMITED_NAMED_ENTITIES"];
            }
            else
            {
                return false;
            }

            if(isset($features["MAX_POS_CHECKS"]))
            {
                $access_record->Variables["MAX_POS_CHECKS"] = (int)$features["MAX_POS_CHECKS"];

                if(isset($access_record->Variables["POS_CHECKS"]) == false)
                    $access_record->Variables["POS_CHECKS"] = 0;
            }
            else
            {
                return false;
            }

            if(isset($features["MAX_SENTIMENT_CHECKS"]))
            {
                $access_record->Variables["MAX_SENTIMENT_CHECKS"] = (int)$features["MAX_SENTIMENT_CHECKS"];

                if(isset($access_record->Variables["SENTIMENT_CHECKS"]) == false)
                    $access_record->Variables["SENTIMENT_CHECKS"] = 0;
            }
            else
            {
                return false;
            }

            if(isset($features["MAX_EMOTION_CHECKS"]))
            {
                $access_record->Variables["MAX_EMOTION_CHECKS"] = (int)$features["MAX_EMOTION_CHECKS"];

                if(isset($access_record->Variables["EMOTION_CHECKS"]) == false)
                    $access_record->Variables["EMOTION_CHECKS"] = 0;
            }
            else
            {
                return false;
            }

            if(isset($features["MAX_SPAM_CHECKS"]))
            {
                $access_record->Variables["MAX_SPAM_CHECKS"] = (int)$features["MAX_SPAM_CHECKS"];
                if(isset($access_record->Variables["SPAM_CHECKS"]) == false)
                    $access_record->Variables["SPAM_CHECKS"] = 0;
            }
            else
            {
                return false;
            }

            if(isset($features["MAX_SENTENCE_SPLITS"]))
            {
                $access_record->Variables["MAX_SENTENCE_SPLITS"] = (int)$features["MAX_SENTENCE_SPLITS"];
                if(isset($access_record->Variables["SENTENCE_SPLITS"]) == false)
                    $access_record->Variables["SENTENCE_SPLITS"] = 0;
            }
            else
            {
                return false;
            }

            if(isset($features["MAX_LANGUAGE_CHECKS"]))
            {
                $access_record->Variables["MAX_LANGUAGE_CHECKS"] = (int)$features["MAX_LANGUAGE_CHECKS"];
                if(isset($access_record->Variables["LANGUAGE_CHECKS"]) == false)
                    $access_record->Variables["LANGUAGE_CHECKS"] = 0;
            }
            else
            {
                return false;
            }

            if(isset($features["MAX_NER_CHECKS"]))
            {
                $access_record->Variables["MAX_NER_CHECKS"] = (int)$features["MAX_NER_CHECKS"];
                if(isset($access_record->Variables["NER_CHECKS"]) == false)
                    $access_record->Variables["NER_CHECKS"] = 0;
            }
            else
            {
                return false;
            }

            return true;
        }

        /**
         * Builds a standard response which is understood by modules
         *
         * @param array $response_content
         * @param int $response_code
         * @param array $debugging_info
         * @return array
         * @noinspection PhpArrayShapeAttributeCanBeAddedInspection
         */
        private static function buildResponse(array $response_content, int $response_code, array $debugging_info): array
        {
            return array(
                "response" => $response_content,
                "response_code" => (int)$response_code,
                "debug" => $debugging_info
            );
        }
    }

