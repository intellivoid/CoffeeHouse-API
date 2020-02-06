<?php


    namespace COASniffle\Objects\SubscriptionPurchaseResults;


    /**
     * Class SubscriptionDetails
     * @package COASniffle\Objects\SubscriptionPurchaseResults
     */
    class SubscriptionDetails
    {
        /**
         * The Timestamp for when this Subscription will process it's billing cycle
         *
         * @var int
         */
        public $BillingCycle;

        /**
         * The name of the plan that this subcription is using
         *
         * @var string
         */
        public $PlanName;

        /**
         * The ID of the plan that this subscription is using
         *
         * @var string
         */
        public $PlanID;

        /**
         * Array of features that this subscription will offer
         *
         * @var array
         */
        public $Features;

        /**
         * The initial price to start the subscription
         *
         * @var float
         */
        public $InitialPrice;

        /**
         * The price for each billing cycle
         *
         * @var float
         */
        public $CyclePrice;

        /**
         * The code for the promotion if any is used
         *
         * @var string
         */
        public $PromotionCode;

        /**
         * The ID of the promotion if any is used
         *
         * @var string
         */
        public $PromotionID;

        /**
         * Returns an array which represents this object
         *
         * @return array
         */
        public function toArray(): array
        {
            return array(
                'billing_cycle' => (int)$this->BillingCycle,
                'plan_name' => $this->PlanName,
                'plan_id' => $this->PlanID,
                'features' => $this->Features,
                'initial_price' => (float)$this->InitialPrice,
                'cycle_price' => (float)$this->CyclePrice,
                'promotion_code' => $this->PromotionCode,
                'promotion_id' => $this->PromotionID
            );
        }

        /**
         * Constructs object from array
         *
         * @param array $data
         * @return SubscriptionDetails
         */
        public static function fromArray(array $data): SubscriptionDetails
        {
            $SubscriptionDetailsObject = new SubscriptionDetails();

            if(isset($data['billing_cycle']))
            {
                $SubscriptionDetailsObject->BillingCycle = (int)$data['billing_cycle'];
            }

            if(isset($data['plan_name']))
            {
                $SubscriptionDetailsObject->PlanName = $data['plan_name'];
            }

            if(isset($data['plan_id']))
            {
                $SubscriptionDetailsObject->PlanID = $data['plan_id'];
            }

            if(isset($data['features']))
            {
                $SubscriptionDetailsObject->Features = $data['features'];
            }

            if(isset($data['initial_price']))
            {
                $SubscriptionDetailsObject->InitialPrice = (float)$data['initial_price'];
            }

            if(isset($data['cycle_price']))
            {
                $SubscriptionDetailsObject->CyclePrice = (float)$data['cycle_price'];
            }

            if(isset($data['promotion_code']))
            {
                $SubscriptionDetailsObject->PromotionCode = $data['promotion_code'];
            }

            if(isset($data['promotion_id']))
            {
                $SubscriptionDetailsObject->PromotionID = $data['promotion_id'];
            }

            return $SubscriptionDetailsObject;
        }
    }