<?php


    namespace IntellivoidSubscriptionManager\Utilities;

    /**
     * Class Hashing
     * @package IntellivoidSubscriptionManager\Utilities
     */
    class Hashing
    {
        /**
         * Calculates a unique public ID for the subscription
         *
         * @param int $subscription_plan_id
         * @param string $promotion_code
         * @return string
         */
        public static function SubscriptionPromotionPublicID(int $subscription_plan_id, string $promotion_code): string
        {
            return hash('crc32b', $subscription_plan_id) . hash('sha256', $promotion_code);
        }
    }