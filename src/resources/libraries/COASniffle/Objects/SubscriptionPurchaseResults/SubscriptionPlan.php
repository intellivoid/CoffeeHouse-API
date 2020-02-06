<?php


    namespace COASniffle\Objects\SubscriptionPurchaseResults;


    class SubscriptionPlan
    {
        /**
         * The ID of the subscription plan
         *
         * @var string
         */
        public $ID;

        /**
         * The name of the Subscription Plan
         *
         * @var string
         */
        public $Name;

        /**
         * Array of features that this subscription plan offers
         *
         * @var array
         */
        public $Features;

        /**
         * The initial price of the subscription
         *
         * @var float
         */
        public $InitialPrice;

        /**
         * The cycle price that this subscription plan offers
         *
         * @var float
         */
        public $CyclePrice;

        /**
         * The cycle for when the billing will be processed in a Unix Timestamp
         *
         * @var int
         */
        public $BillingCycle;

        /**
         * The Unix Timestamp of when this subscription plan was last updated
         *
         * @var int
         */
        public $LastUpdated;

        /**
         * The Unix Timestamp of when this subscription plan was created
         *
         * @var int
         */
        public $Created;

        /**
         * Returns an array which represents this object
         *
         * @return array
         */
        public function toArray(): array
        {
            return array(
                'id' => (int)$this->ID,
                'name' => $this->Name,
                'features' => $this->Features,
                'initial_price' => (float)$this->InitialPrice,
                'cycle_price' => (float)$this->CyclePrice,
                'billing_cycle' => (int)$this->BillingCycle,
                'last_updated' => (int)$this->LastUpdated,
                'created' => $this->Created
            );
        }

        /**
         * Constructs object from array
         *
         * @param array $data
         * @return SubscriptionPlan
         */
        public static function fromArray(array $data): SubscriptionPlan
        {
            $SubscriptionPlanObject = new SubscriptionPlan();

            if(isset($data['id']))
            {
                $SubscriptionPlanObject->ID = (int)$data['id'];
            }

            if(isset($data['name']))
            {
                $SubscriptionPlanObject->Name = $data['name'];
            }

            if(isset($data['features']))
            {
                $SubscriptionPlanObject->Features = $data['features'];
            }

            if(isset($data['initial_price']))
            {
                $SubscriptionPlanObject->InitialPrice = (float)$data['initial_price'];
            }

            if(isset($data['cycle_price']))
            {
                $SubscriptionPlanObject->CyclePrice = (float)$data['cycle_price'];
            }

            if(isset($data['billing_cycle']))
            {
                $SubscriptionPlanObject->BillingCycle = (int)$data['billing_cycle'];
            }

            if(isset($data['last_updated']))
            {
                $SubscriptionPlanObject->LastUpdated = (int)$data['last_updated'];
            }

            if(isset($data['created']))
            {
                $SubscriptionPlanObject->Created = (int)$data['created'];
            }

            return $SubscriptionPlanObject;
        }
    }