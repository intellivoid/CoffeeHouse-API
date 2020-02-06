<?php


    namespace IntellivoidSubscriptionManager\Objects;


    use IntellivoidSubscriptionManager\Objects\Subscription\Properties;

    /**
     * Class Subscription
     * @package IntellivoidSubscriptionManager\Objects
     */
    class Subscription
    {
        /**
         * Private internal unique database ID for this subscription record
         *
         * @var int
         */
        public $ID;

        /**
         * The unique Public ID for this subscription record
         *
         * @var string
         */
        public $PublicID;

        /**
         * The ID of the subscription plan this subscription is affiliated to
         *
         * @var int
         */
        public $SubscriptionPlanID;

        /**
         * The ID of the account that this subscription is tied to
         *
         * @var int
         */
        public $AccountID;

        /**
         * Indicates if this subscription is active or not
         *
         * @var bool
         */
        public $Active;

        /**
         * The interval for the billing cycle
         *
         * @var int
         */
        public $BillingCycle;

        /**
         * The Unix Timestamp which indicates the next billing cycle
         *
         * @var int
         */
        public $NextBillingCycle;

        /**
         * Properties for this subscription
         *
         * @var Properties
         */
        public $Properties;

        /**
         * The Unix Timestamp for when this subscription record has been created
         *
         * @var int
         */
        public $CreatedTimestamp;

        /**
         * Flags associated with this subscription
         *
         * @var array
         */
        public $Flags;

        /**
         * Determines if the flag is already applied
         *
         * @param string $flag
         * @return bool
         */
        public function hasFlag(string $flag): bool
        {
            $flag = str_ireplace(' ', '_', strtoupper($flag));

            if(in_array($flag, $this->Flags))
            {
                return true;
            }

            return false;
        }

        /**
         * Applies a flag
         *
         * @param string $flag
         * @return bool
         */
        public function applyFlag(string $flag): bool
        {
            $flag = str_ireplace(' ', '_', strtoupper($flag));

            if($this->hasFlag($flag))
            {
                return false;
            }

            $this->Flags[] = $flag;
            return true;
        }

        /**
         * Removes an existing flag
         *
         * @param string $flag
         * @return bool
         */
        public function removeFlag(string $flag)
        {
            $flag = str_ireplace(' ', '_', strtoupper($flag));

            if($this->hasFlag($flag) == false)
            {
                return false;
            }

            $this->Flags = array_diff($this->Flags, [$flag]);
            return true;
        }

        /**
         * Returns an array which represents this object
         *
         * @return array
         */
        public function toArray(): array
        {
            return array(
                'id' => (int)$this->ID,
                'public_id' => $this->PublicID,
                'subscription_plan_id' => (int)$this->SubscriptionPlanID,
                'account_id' => (int)$this->AccountID,
                'active' => (bool)$this->Active,
                'billing_cycle' => (int)$this->BillingCycle,
                'next_billing_cycle' => (int)$this->NextBillingCycle,
                'properties' => $this->Properties->toArray(),
                'created_timestamp' => (int)$this->CreatedTimestamp,
                'flags' => $this->Flags
            );
        }

        /**
         * Constructs object from array
         *
         * @param array $data
         * @return Subscription
         */
        public static function fromArray(array $data): Subscription
        {
            $SubscriptionObject = new Subscription();

            if(isset($data['id']))
            {
                $SubscriptionObject->ID = (int)$data['id'];
            }

            if(isset($data['public_id']))
            {
                $SubscriptionObject->PublicID = $data['public_id'];
            }

            if(isset($data['subscription_plan_id']))
            {
                $SubscriptionObject->SubscriptionPlanID = (int)$data['subscription_plan_id'];
            }

            if(isset($data['account_id']))
            {
                $SubscriptionObject->AccountID = (int)$data['account_id'];
            }

            if(isset($data['active']))
            {
                $SubscriptionObject->Active = (bool)$data['active'];
            }

            if(isset($data['billing_cycle']))
            {
                $SubscriptionObject->BillingCycle = (int)$data['billing_cycle'];
            }

            if(isset($data['next_billing_cycle']))
            {
                $SubscriptionObject->NextBillingCycle = (int)$data['next_billing_cycle'];
            }

            if(isset($data['properties']))
            {
                $SubscriptionObject->Properties = Properties::fromArray($data['properties']);
            }

            if(isset($data['created_timestamp']))
            {
                $SubscriptionObject->CreatedTimestamp = (int)$data['created_timestamp'];
            }

            if(isset($data['flags']))
            {
                $SubscriptionObject->Flags = $data['flags'];
            }
            else
            {
                $SubscriptionObject->Flags = [];
            }

            return $SubscriptionObject;
        }
    }