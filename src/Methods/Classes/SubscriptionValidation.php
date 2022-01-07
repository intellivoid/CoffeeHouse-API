<?php

    /** @noinspection PhpMissingFieldTypeInspection */

    namespace Methods\Classes;

    use COASniffle\COASniffle;
    use COASniffle\Exceptions\CoaAuthenticationException;
    use COASniffle\Handlers\COA;
    use CoffeeHouse\Abstracts\UserSubscriptionSearchMethod;
    use CoffeeHouse\CoffeeHouse;
    use CoffeeHouse\Exceptions\UserSubscriptionNotFoundException;
    use CoffeeHouse\Objects\UserSubscription;
    use Exception;
    use IntellivoidAPI\Exceptions\AccessRecordNotFoundException;
    use IntellivoidAPI\Exceptions\DatabaseException;
    use IntellivoidAPI\Exceptions\InvalidRateLimitConfiguration;
    use IntellivoidAPI\Exceptions\InvalidSearchMethodException;
    use IntellivoidAPI\IntellivoidAPI;
    use IntellivoidAPI\Objects\AccessRecord;
    use IntellivoidSubscriptionManager\Abstracts\SearchMethods\SubscriptionSearchMethod;
    use IntellivoidSubscriptionManager\Exceptions\SubscriptionNotFoundException;
    use IntellivoidSubscriptionManager\IntellivoidSubscriptionManager;
    use IntellivoidSubscriptionManager\Objects\Subscription;
    use IntellivoidSubscriptionManager\Utilities\Converter;
    use KimchiAPI\Abstracts\ResponseStandard;
    use KimchiAPI\Exceptions\ApiException;
    use KimchiAPI\Exceptions\UnsupportedResponseStandardException;
    use KimchiAPI\Exceptions\UnsupportedResponseTypeExceptions;
    use KimchiAPI\KimchiAPI;
    use KimchiAPI\Objects\Response;

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
         * @param IntellivoidAPI $intellivoidAPI
         * @param AccessRecord $access_record
         * @throws ApiException
         * @throws UnsupportedResponseStandardException
         * @throws UnsupportedResponseTypeExceptions
         * @throws AccessRecordNotFoundException
         * @throws DatabaseException
         * @throws InvalidRateLimitConfiguration
         * @throws InvalidSearchMethodException
         */
        public function validateUserSubscription(CoffeeHouse $coffeeHouse, IntellivoidAPI $intellivoidAPI, AccessRecord &$access_record)
        {
            try
            {
                $UserSubscription = $coffeeHouse->getUserSubscriptionManager()->getUserSubscription(
                    UserSubscriptionSearchMethod::byAccessRecordID, $access_record->ID
                );
            }
            catch (UserSubscriptionNotFoundException $e)
            {
                $Response = new Response();
                $Response->Success = false;
                $Response->ResponseCode = 500;
                $Response->ErrorMessage = 'There was an error while trying to verify your subscription, the system couldn\'t find your subscription.';
                $Response->ErrorCode = 0;
                $Response->Exception = $e;
                $Response->ResponseStandard = ResponseStandard::IntellivoidAPI;

                KimchiAPI::handleResponse($Response);
            }
            catch(Exception $e)
            {
                KimchiAPI::handleException($e, ResponseStandard::IntellivoidAPI);
            }

            $IntellivoidSubscriptionManager = new IntellivoidSubscriptionManager();
            $this->intellivoid_subscription_manager = $IntellivoidSubscriptionManager;

            try
            {
                /** @noinspection PhpUndefinedVariableInspection */
                $Subscription = $IntellivoidSubscriptionManager->getSubscriptionManager()->getSubscription(
                    SubscriptionSearchMethod::byId, $UserSubscription->SubscriptionID
                );
            }
            catch (SubscriptionNotFoundException $e)
            {
                $Response = new Response();
                $Response->Success = false;
                $Response->ResponseCode = 403;
                $Response->ErrorMessage = 'You do not have an active subscription with this service';
                $Response->ErrorCode = 0;
                $Response->Exception = $e;
                $Response->ResponseStandard = ResponseStandard::IntellivoidAPI;

                KimchiAPI::handleResponse($Response);
            }
            catch(Exception $e)
            {
                KimchiAPI::handleException($e, ResponseStandard::IntellivoidAPI);
            }

            // PATCH: Sometimes if a user wants to upgrade their subscription, the features are not yet applied.
            /** @noinspection PhpUndefinedVariableInspection */
            $Features = Converter::featuresToSA($Subscription->Properties->Features, true);

            if(self::updateSubscriptionFeatures($Features, $access_record) == false)
            {
                $Response = new Response();
                $Response->Success = false;
                $Response->ResponseCode = 403;
                $Response->ErrorMessage = 'There are new updates to your subscription, login to the dashboard to update your subscription';
                $Response->ErrorCode = 0;
                $Response->ResponseStandard = ResponseStandard::IntellivoidAPI;

                KimchiAPI::handleResponse($Response);
            }

            /** @noinspection PhpCastIsUnnecessaryInspection */
            if((int)time() > $Subscription->NextBillingCycle)
            {
                new COASniffle();

                try
                {
                    $BillingProcessed = COA::processSubscriptionBilling($Subscription->ID);
                }
                catch (CoaAuthenticationException $e)
                {
                    $Response = new Response();
                    $Response->Success = false;
                    $Response->ResponseCode = 500;
                    $Response->ErrorMessage = $e->getMessage();
                    $Response->ErrorCode = 0;
                    $Response->Exception = $e;
                    $Response->ResponseStandard = ResponseStandard::IntellivoidAPI;

                    KimchiAPI::handleResponse($Response);
                }
                catch(Exception $e)
                {
                    KimchiAPI::handleException($e, ResponseStandard::IntellivoidAPI);
                }

                /** @noinspection PhpUndefinedVariableInspection */
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

            $intellivoidAPI->getAccessKeyManager()->updateAccessRecord($access_record);

            $this->subscription = $Subscription;
            $this->user_subscription = $UserSubscription;
        }

        /**
         * Updates all the API Variables from the subscription to make sure it's up-to-date.
         *
         * @param array $features
         * @param AccessRecord $access_record
         * @return bool
         * @noinspection PhpParameterByRefIsNotUsedAsReferenceInspection
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
    }