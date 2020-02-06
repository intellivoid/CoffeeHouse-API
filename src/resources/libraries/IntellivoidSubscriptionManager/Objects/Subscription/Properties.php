<?php


    namespace IntellivoidSubscriptionManager\Objects\Subscription;


    /**
     * Class Properties
     * @package IntellivoidSubscriptionManager\Objects\Subscription
     */
    class Properties
    {
        /**
         * The initial price to start the subscription, 0 = Free
         *
         * @var float
         */
        public $InitialPrice;

        /**
         * The amount to charge the user per billing cycle
         *
         * @var float
         */
        public $CyclePrice;

        /**
         * The features that this subscription provides
         *
         * @var array(Feature)
         */
        public $Features;

        /**
         * The ID of the promotional code used for this subscription
         *
         * 0 = None
         *
         * @var int
         */
        public $PromotionID;

        /**
         * Adds a feature to the subscription property
         *
         * @param Feature $feature
         */
        public function addFeature(Feature $feature)
        {
            $id = hash('crc32', $feature->Name);
            $this->Features[$id] = $feature;
        }

        /**
         * Removes a feature from the subscription property
         *
         * @param Feature $feature
         */
        public function removeFeature(Feature $feature)
        {
            $id = hash('crc32', $feature->Name);
            unset($this->Features[$id]);
        }

        /**
         * Returns an array which represents this object
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
                'initial_price' => $this->InitialPrice,
                'cycle_price' => $this->CyclePrice,
                'promotion_code' => (int)$this->PromotionID,
                'features' => $features
            );
        }

        /**
         * Creates object from array
         *
         * @param array $data
         * @return Properties
         */
        public static function fromArray(array $data): Properties
        {
            $PropertiesObject = new Properties();

            if(isset($data['initial_price']))
            {
                $PropertiesObject->InitialPrice = (float)$data['initial_price'];
            }

            if(isset($data['cycle_price']))
            {
                $PropertiesObject->CyclePrice = (float)$data['cycle_price'];
            }

            if(isset($data['promotion_code']))
            {
                $PropertiesObject->PromotionID = (int)$data['promotion_code'];
            }

            foreach($data['features'] as $feature)
            {
                $PropertiesObject->addFeature(Feature::fromArray($feature));
            }

            return $PropertiesObject;
        }
    }