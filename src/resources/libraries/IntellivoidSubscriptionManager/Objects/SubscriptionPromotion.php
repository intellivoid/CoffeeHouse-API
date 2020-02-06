<?php


    namespace IntellivoidSubscriptionManager\Objects;


    use IntellivoidSubscriptionManager\Abstracts\SubscriptionPromotionStatus;
    use IntellivoidSubscriptionManager\Objects\Subscription\Feature;

    class SubscriptionPromotion
    {
        /**
         * The internal unique database ID for this record
         *
         * @var int
         */
        public $ID;

        /**
         * The unique public ID for this promotion
         *
         * @var string
         */
        public $PublicID;

        /**
         * User-friendly promotion code for this promotion
         *
         * @var string
         */
        public $PromotionCode;

        /**
         * The subscription plan ID that this promotion is applicable to
         *
         * @var int
         */
        public $SubscriptionPlanID;

        /**
         * The initial price that this promotion is offering
         *
         * @var float
         */
        public $InitialPrice;

        /**
         * The cycle share that this promotion is offering for every billing cycle
         *
         * @var float
         */
        public $CyclePrice;

        /**
         * The account ID that this promotion is affiliated with
         * 0 = None
         *
         * @var int
         */
        public $AffiliationAccountID;

        /**
         * The share of initial purchase to give to the affiliated account
         *
         * @var float
         */
        public $AffiliationInitialShare;

        /**
         * The share of billing cycles to give to the affiliated account
         *
         * @var float
         */
        public $AffiliationCycleShare;

        /**
         * Array of new features or features to override
         *
         * @var array(Feature)
         */
        public $Features;

        /**
         * The status of this promotion code
         *
         * @var int|SubscriptionPromotionStatus
         */
        public $Status;

        /**
         * Flags associated with this promotion code
         *
         * @var array
         */
        public $Flags;

        /**
         * The Unix Timestamp of when this promotion was last updated
         *
         * @var int
         */
        public $LastUpdatedTimestamp;

        /**
         * The Unix Timestamp of when this record was created
         *
         * @var int
         */
        public $CreatedTimestamp;

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
         * Returns an array which represents the structure of this object
         *
         * @return array
         */
        public function toArray(): array
        {
            $features = array();

            /** @var Feature $feature */
            foreach($this->Features as $feature)
            {
                $features[] = $feature->toArray();
            }

            return array(
                'id' => (int)$this->ID,
                'public_id' => (int)$this->PublicID,
                'promotion_code' => $this->PromotionCode,
                'subscription_plan_id' => (int)$this->SubscriptionPlanID,
                'initial_price' => (float)$this->InitialPrice,
                'cycle_price' => (float)$this->CyclePrice,
                'affiliation_account_id' => (int)$this->AffiliationAccountID,
                'affiliation_initial_share' => (float)$this->AffiliationInitialShare,
                'affiliation_cycle_share' => (float)$this->AffiliationCycleShare,
                'features' => $features,
                'status' => (int)$this->Status,
                'flags' => $this->Flags,
                'last_updated_timestamp' => $this->LastUpdatedTimestamp,
                'created_timestamp' => $this->CreatedTimestamp
            );
        }

        /**
         * Constructs object from array
         *
         * @param array $data
         * @return SubscriptionPromotion
         */
        public static function fromArray(array $data): SubscriptionPromotion
        {
            $SubscriptionPromotionObject = new SubscriptionPromotion();

            if(isset($data['id']))
            {
                $SubscriptionPromotionObject->ID = (int)$data['id'];
            }

            if(isset($data['public_id']))
            {
                $SubscriptionPromotionObject->PublicID = $data['public_id'];
            }

            if(isset($data['promotion_code']))
            {
                $SubscriptionPromotionObject->PromotionCode = $data['promotion_code'];
            }

            if(isset($data['subscription_plan_id']))
            {
                $SubscriptionPromotionObject->SubscriptionPlanID = (int)$data['subscription_plan_id'];
            }

            if(isset($data['initial_price']))
            {
                $SubscriptionPromotionObject->InitialPrice = (float)$data['initial_price'];
            }

            if(isset($data['cycle_price']))
            {
                $SubscriptionPromotionObject->CyclePrice = (float)$data['cycle_price'];
            }

            if(isset($data['affiliation_account_id']))
            {
                $SubscriptionPromotionObject->AffiliationAccountID = (int)$data['affiliation_account_id'];

            }
            else
            {
                $SubscriptionPromotionObject->AffiliationAccountID = 0;
            }

            if(isset($data['affiliation_initial_share']))
            {
                $SubscriptionPromotionObject->AffiliationInitialShare = (float)$data['affiliation_initial_share'];
            }
            else
            {
                $SubscriptionPromotionObject->AffiliationInitialShare = (float)0;
            }

            if(isset($data['affiliation_cycle_share']))
            {
                $SubscriptionPromotionObject->AffiliationCycleShare = (float)$data['affiliation_cycle_share'];
            }
            else
            {
                $SubscriptionPromotionObject->AffiliationCycleShare = (float)0;
            }

            if(isset($data['features']))
            {
                $SubscriptionPromotionObject->Features = [];

                foreach($data['features'] as $feature)
                {
                    $feature = Feature::fromArray($feature);
                    $SubscriptionPromotionObject->Features[] = $feature;
                }
            }
            else
            {
                $SubscriptionPromotionObject->Features = [];
            }

            if(isset($data['status']))
            {
                $SubscriptionPromotionObject->Status = (int)$data['status'];
            }

            if(isset($data['flags']))
            {
                $SubscriptionPromotionObject->Flags = $data['flags'];
            }

            if(isset($data['last_updated_timestamp']))
            {
                $SubscriptionPromotionObject->LastUpdatedTimestamp = (int)$data['last_updated_timestamp'];
            }

            if(isset($data['created_timestamp']))
            {
                $SubscriptionPromotionObject->CreatedTimestamp = (int)$data['created_timestamp'];
            }

            return $SubscriptionPromotionObject;
        }

    }