<?php


    namespace COASniffle\Objects;


    use COASniffle\Objects\SubscriptionPurchaseResults\SubscriptionDetails;
    use COASniffle\Objects\SubscriptionPurchaseResults\SubscriptionPlan;
    use COASniffle\Objects\SubscriptionPurchaseResults\SubscriptionPlanPromotion;

    /**
     * Class SubscriptionPurchaseResults
     * @package COASniffle\Objects
     */
    class SubscriptionPurchaseResults
    {
        /**
         * The details about the subscription plan
         *
         * @var SubscriptionPlan
         */
        public $SubscriptionPlan;

        /**
         * The promotion details if any has been provided
         *
         * @var SubscriptionPlanPromotion
         */
        public $SubscriptionPromotion;

        /**
         * The details about the subscription the system has determined for the user
         *
         * @var SubscriptionDetails
         */
        public $SubscriptionDetails;

        /**
         * URL to process the transaction
         *
         * @var string
         */
        public $ProcessTransactionURL;

        /**
         * Returns an array which represents this object
         *
         * @return array
         */
        public function toArray(): array
        {
            return array(
                'subscription_plan' => $this->SubscriptionPlan->toArray(),
                'subscription_promotion' => $this->SubscriptionPromotion->toArray(),
                'subscription_details' => $this->SubscriptionDetails->toArray(),
                'process_transaction_url' => $this->ProcessTransactionURL
            );
        }

        /**
         * Constructs object from array
         *
         * @param array $data
         * @return SubscriptionPurchaseResults
         */
        public static function fromArray(array $data): SubscriptionPurchaseResults
        {
            $SubscriptionPurchaseResultsObject = new SubscriptionPurchaseResults();

            if(is_null($data['subscription_plan']))
            {
                $SubscriptionPurchaseResultsObject->SubscriptionPlan = SubscriptionPlan::fromArray(array());
            }
            else
            {
                $SubscriptionPurchaseResultsObject->SubscriptionPlan = SubscriptionPlan::fromArray($data['subscription_plan']);
            }

            if(is_null($data['subscription_promotion']))
            {
                $SubscriptionPurchaseResultsObject->SubscriptionPromotion = SubscriptionPlanPromotion::fromArray(array());
            }
            else
            {
                $SubscriptionPurchaseResultsObject->SubscriptionPromotion = SubscriptionPlanPromotion::fromArray($data['subscription_promotion']);
            }

            if(is_null($data['subscription_details']))
            {
                $SubscriptionPurchaseResultsObject->SubscriptionDetails = SubscriptionDetails::fromArray(array());
            }
            else
            {
                $SubscriptionPurchaseResultsObject->SubscriptionDetails = SubscriptionDetails::fromArray($data['subscription_details']);
            }

            if(is_null($data['process_transaction_url']))
            {
                $SubscriptionPurchaseResultsObject->ProcessTransactionURL = null;
            }
            else
            {
                $SubscriptionPurchaseResultsObject->ProcessTransactionURL = $data['process_transaction_url'];
            }

            return $SubscriptionPurchaseResultsObject;
        }
    }