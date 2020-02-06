<?php


    namespace IntellivoidSubscriptionManager\Utilities;

    /**
     * Class Validate
     * @package IntellivoidSubscriptionManager\Utilities
     */
    class Validate
    {
        /**
         * Validates if the promotion code for the subscription is valid or not
         *
         * @param string $input
         * @return bool
         */
        public static function subscriptionPromotionCode(string $input): bool
        {
            if(strlen($input) > 120)
            {
                return false;
            }

            if(strlen($input) < 3)
            {
                return false;
            }

            if(preg_match("/^[a-zA-Z0-9 ]*$/", $input))
            {
                return true;
            }

            return false;
        }
    }