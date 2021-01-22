<?php

    use COASniffle\Exceptions\CoaAuthenticationException;
    use COASniffle\Handlers\COA;
    use CoffeeHouse\Abstracts\UserSubscriptionSearchMethod;
    use CoffeeHouse\CoffeeHouse;
    use CoffeeHouse\Exceptions\UserSubscriptionNotFoundException;
    use CoffeeHouse\Objects\UserSubscription;
    use Handler\GenericResponses\InternalServerError;
    use Handler\Handler;
    use IntellivoidAPI\Exceptions\AccessRecordNotFoundException;
    use IntellivoidAPI\Exceptions\InvalidRateLimitConfiguration;
    use IntellivoidAPI\Objects\AccessRecord;
    use IntellivoidSubscriptionManager\Abstracts\SearchMethods\SubscriptionSearchMethod;
    use IntellivoidSubscriptionManager\Exceptions\DatabaseException;
    use IntellivoidSubscriptionManager\Exceptions\InvalidSearchMethodException;
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
         * @param AccessRecord $accessRecord
         * @return null|array
         * @throws AccessRecordNotFoundException
         * @throws DatabaseException
         * @throws InvalidRateLimitConfiguration
         * @throws InvalidSearchMethodException
         * @throws SubscriptionNotFoundException
         * @throws \IntellivoidAPI\Exceptions\DatabaseException
         * @throws \IntellivoidAPI\Exceptions\InvalidSearchMethodException
         */
        public function validateUserSubscription(CoffeeHouse $coffeeHouse, AccessRecord $accessRecord): ?array
        {
            try
            {
                $UserSubscription = $coffeeHouse->getUserSubscriptionManager()->getUserSubscription(
                    UserSubscriptionSearchMethod::byAccessRecordID, $accessRecord->ID
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
                        "access_record" => $accessRecord->toArray()
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
            catch (SubscriptionNotFoundException)
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
                        "access_record" => $accessRecord->toArray(),
                        "user_subscription" => $UserSubscription->toArray()
                    )
                );
            }
            catch(Exception $e)
            {
                InternalServerError::executeResponse($e);
                exit();
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
                                "error_code" => $e->getCode(),
                                "type" => "SUBSCRIPTION",
                                "message" => $e->getMessage()
                            )
                        ), 500, array(
                            "access_record" => $accessRecord->toArray(),
                            "user_subscription" => $UserSubscription->toArray()
                        )
                    );
                }
                catch(Exception $e)
                {
                    InternalServerError::executeResponse($e);
                    exit();
                }

                $Subscription = $IntellivoidSubscriptionManager->getSubscriptionManager()->getSubscription(
                    SubscriptionSearchMethod::byId, $UserSubscription->SubscriptionID
                );

                // PATCH: Sometimes if a user wants to upgrade their subscription, the features are not yet applied.
                $Features = Converter::featuresToSA($Subscription->Properties->Features, true);

                if(isset($Features["LYDIA_SESSIONS"]))
                    $accessRecord->Variables["MAX_LYDIA_SESSIONS"] = (int)$Features["LYDIA_SESSIONS"];

                if($BillingProcessed)
                {
                    $accessRecord->Variables["LYDIA_SESSIONS"] = 0;
                }

                $IntellivoidAPI = Handler::getIntellivoidAPI();
                $IntellivoidAPI->getAccessKeyManager()->updateAccessRecord($accessRecord);

            }

            $this->subscription = $Subscription;
            $this->user_subscription = $UserSubscription;

            return null;
        }

        /**
         * Builds a standard response which is understood by modules
         *
         * @param array $response_content
         * @param int $response_code
         * @param array $debugging_info
         * @return array
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

