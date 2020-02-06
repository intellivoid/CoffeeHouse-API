<?php


    namespace IntellivoidSubscriptionManager\Objects\Subscription;

    /**
     * Class Feature
     * @package IntellivoidSubscriptionManager\Objects\Subscription
     */
    class Feature
    {
        /**
         * The name of the feature
         *
         * @var string
         */
        public $Name;

        /**
         * The value for this feature
         *
         * @var string|int|bool
         */
        public $Value;

        /**
         * Returns an array which represents this object
         *
         * @return array
         */
        public function toArray(): array
        {
            return array(
                'name' => $this->Name,
                'value' => $this->Value
            );
        }

        /**
         * Constructs object from array
         *
         * @param array $data
         * @return Feature
         */
        public static function fromArray(array $data): Feature
        {
            $FeatureObject = new Feature();

            if(isset($data['name']))
            {
                $FeatureObject->Name = $data['name'];
            }

            if(isset($data['value']))
            {
                if(is_string($data['value']))
                {
                    $FeatureObject->Value = (string)$data['value'];
                }

                if(is_int($data['value']))
                {
                    $FeatureObject->Value = (int)$data['value'];
                }

                if(is_bool($data['value']))
                {
                    $FeatureObject->Value = (bool)$data['value'];
                }

            }

            return $FeatureObject;
        }
    }