<?php


    namespace COASniffle\Objects\SubscriptionPurchaseResults;

    /**
     * Class SubscriptionPlanPromotion
     * @package COASniffle\Objects\SubscriptionPurchaseResults
     */
    class SubscriptionPlanPromotion
    {
        /**
         * The ID of the subscription promotion
         *
         * @var string
         */
        public $ID;

        /**
         * The promotional code that's used
         *
         * @var string
         */
        public $Code;

        /**
         * Array of features that this subscription plan can offer
         *
         * @var array
         */
        public $Features;

        /**
         * The price for the initial price of the subscription
         *
         * @var float
         */
        public $InitialPrice;

        /**
         * The price for each billing cycle of tLFAyou he subscription
         *
         * @var float
         */
        public $CyclePrice;

        /**
         * The Unix Timestamp
         *
         * @var int
         */
        public $LastUpdated;

        /**
         * Unix Timestamp of when this Promotion was created
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
                'id' => $this->ID,
                'code' => $this->Code,
                'features' => $this->Features,
                'initial_price' => (float)$this->InitialPrice,
                'cycle_price' => (float)$this->CyclePrice,
                'last_updated' => (int)$this->LastUpdated,
                'created' => (int)$this->Created
            );
        }

        /**
         * Constructs object from array
         *
         * @param array $data
         * @return SubscriptionPlanPromotion
         */
        public static function fromArray(array $data): SubscriptionPlanPromotion
        {
            $SubscriptionPlanPromotionObject = new SubscriptionPlanPromotion();

            if(isset($data['id']))
            {
                $SubscriptionPlanPromotionObject->ID = $data['id'];
            }

            if(isset($data['code']))
            {
                $SubscriptionPlanPromotionObject->Code = $data['code'];
            }

            if(isset($data['features']))
            {
                $SubscriptionPlanPromotionObject->Features = $data['features'];
            }

            if(isset($data['initial_price']))
            {
                $SubscriptionPlanPromotionObject->InitialPrice = (float)$data['initial_price'];
            }

            if(isset($data['cycle_price']))
            {
                $SubscriptionPlanPromotionObject->CyclePrice = (float)$data['cycle_price'];
            }

            if(isset($data['last_updated']))
            {
                $SubscriptionPlanPromotionObject->LastUpdated = (int)$data['last_updated'];
            }

            if(isset($data['created']))
            {
                $SubscriptionPlanPromotionObject->Created = (int)$data['created'];
            }

            return $SubscriptionPlanPromotionObject;
        }
    }