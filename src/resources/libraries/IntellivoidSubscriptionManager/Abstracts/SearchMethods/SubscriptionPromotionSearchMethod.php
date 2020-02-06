<?php


    namespace IntellivoidSubscriptionManager\Abstracts\SearchMethods;


    /**
     * Class SubscriptionPromotionSearchMethod
     * @package IntellivoidSubscriptionManager\Abstracts\SearchMethods
     */
    abstract class SubscriptionPromotionSearchMethod
    {
        const byId = 'id';

        const byPublicId = 'public_id';

        const byPromotionCode = 'promotion_code';
    }