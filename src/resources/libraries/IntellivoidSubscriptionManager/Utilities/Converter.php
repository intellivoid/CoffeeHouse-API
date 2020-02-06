<?php


    namespace IntellivoidSubscriptionManager\Utilities;

    use IntellivoidSubscriptionManager\Objects\Subscription\Feature;

    /**
     * Class Converter
     * @package IntellivoidSubscriptionManager\Utilities
     */
    class Converter
    {
        /**
         * Converts promotion code to standard promotion
         *
         * @param string $input
         * @return string
         */
        public static function subscriptionPromotionCode(string $input): string
        {
            $input = strtoupper($input);
            $input = str_ireplace(' ', '_', $input);
            return $input;
        }

        /**
         * Converts array of features to a simplified array
         *
         * @param array $features
         * @param bool $is_object
         * @return array
         */
        public static function featuresToSA(array $features, bool $is_object=True): array
        {
            $Results = array();

            if($is_object)
            {
                /** @var Feature $feature */
                foreach($features as $feature)
                {
                    $Results[$feature->Name] = $feature->Value;
                }

                return $Results;
            }

            foreach($features as $feature)
            {
                $name = null;
                $value = null;

                if(isset($feature['Name']))
                {
                    $name = $feature['Name'];
                }

                if(isset($feature['name']))
                {
                    $name = $feature['name'];
                }

                if(isset($feature['Value']))
                {
                    $value = $feature['Value'];
                }

                if(isset($feature['value']))
                {
                    $value = $feature['value'];
                }

                $Results[$name] = $value;
            }

            return $Results;
        }

        /**
         * Features to Multi-Dimensional array
         *
         * @param array $features
         * @param bool $as_objects
         * @return array
         */
        public static function featuresToMDA(array $features, bool $as_objects=True): array
        {
            $Results = array();

            foreach($features as $name => $value)
            {
                $FeatureObject = new Feature();
                $FeatureObject->Name = $name;
                $FeatureObject->Value = $value;

                if($as_objects)
                {
                    $Results[] = $FeatureObject;
                }
                else
                {
                    $Results[] = $FeatureObject->toArray();
                }
            }

            return $Results;
        }

        /**
         * Validates the features from an array
         *
         * @param array $data
         * @return bool
         */
        public static function validateFeatures(array $data): bool
        {
            foreach($data as $feature)
            {
                $name = null;
                $value = null;

                if(isset($feature['name']))
                {
                    $name = $feature['name'];
                }

                if(isset($feature['Name']))
                {
                    $name = $feature['Name'];
                }

                if(isset($feature['value']))
                {
                    $value = $feature['value'];
                }

                if(isset($feature['Value']))
                {
                    $value = $feature['Value'];
                }

                if(is_null($name))
                {
                    return false;
                }

                if(is_null($value))
                {
                    return false;
                }
            }

            return true;
        }
    }