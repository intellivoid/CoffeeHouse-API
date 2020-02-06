<?php


    namespace IntellivoidSubscriptionManager\Objects;

    use IntellivoidSubscriptionManager\Objects\Subscription\Feature;

    /**
     * Class SubscriptionPlan
     * @package IntellivoidSubscriptionManager\Objects
     */
    class SubscriptionPlan
    {
        /**
         * Unique internal database ID for this record
         *
         * @var int
         */
        public $ID;

        /**
         * Unique public ID for this record
         *
         * @var string
         */
        public $PublicID;

        /**
         * The Application ID that this plan is applicable to
         *
         * @var int
         */
        public $ApplicationID;

        /**
         * The name of the plan
         *
         * @var string
         */
        public $PlanName;

        /**
         * The list of features to be applied for the subscription
         *
         * @var array(Feature)
         */
        public $Features;

        /**
         * The initial price to start the subscription
         *
         * @var float
         */
        public $InitialPrice;

        /**
         * The price to charge per cycle
         *
         * @var float
         */
        public $CyclePrice;

        /**
         * The amount of time per cycle
         *
         * @var int
         */
        public $BillingCycle;

        /**
         * The status of this subscription plan
         *
         * @var int
         */
        public $Status;

        /**
         * Flags associated with the subscription plan
         *
         * @var array
         */
        public $Flags;

        /**
         * The Unix Timestamp of when this record was last updated
         *
         * @var int
         */
        public $LastUpdated;

        /**
         * The Unix Timestamp of when this record was created
         *
         * @var int
         */
        public $CreatedTimestamp;

        /**
         * Returns an array which represents this object's structure
         *
         * @return array
         */
        public function toArray()
        {
            $features = array();

            /** @var Feature $feature */
            foreach($this->Features as $feature)
            {
                $features[] = $feature->toArray();
            }

            return array(
                'id' => (int)$this->ID,
                'public_id' => $this->PublicID,
                'application_id' => (int)$this->ApplicationID,
                'plan_name' => $this->PlanName,
                'features' => $features,
                'initial_price' => (float)$this->InitialPrice,
                'cycle_price' => (float)$this->CyclePrice,
                'billing_cycle' => (int)$this->BillingCycle,
                'status' => (int)$this->Status,
                'flags' => $this->Flags,
                'last_updated' => (int)$this->LastUpdated,
                'created' => (int)$this->CreatedTimestamp
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

            if(isset($data['public_id']))
            {
                $SubscriptionPlanObject->PublicID = $data['public_id'];
            }

            if(isset($data['application_id']))
            {
                $SubscriptionPlanObject->ApplicationID = (int)$data['application_id'];
            }

            if(isset($data['plan_name']))
            {
                $SubscriptionPlanObject->PlanName = $data['plan_name'];
            }

            if(isset($data['features']))
            {
                foreach($data['features'] as $feature)
                {
                    $SubscriptionPlanObject->Features[] = Feature::fromArray($feature);
                }
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

            if(isset($data['status']))
            {
                $SubscriptionPlanObject->Status = (int)$data['status'];
            }

            if(isset($data['flags']))
            {
                $SubscriptionPlanObject->Flags = $data['flags'];
            }

            if(isset($data['last_updated']))
            {
                $SubscriptionPlanObject->LastUpdated = (int)$data['last_updated'];
            }

            if(isset($data['created_timestamp']))
            {
                $SubscriptionPlanObject->CreatedTimestamp = (int)$data['created_timestamp'];
            }

            return $SubscriptionPlanObject;
        }
    }