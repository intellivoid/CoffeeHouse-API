<?php


    namespace IntellivoidSubscriptionManager\Abstracts;


    abstract class SubscriptionPromotionStatus
    {
        /**
         * Indicates if this promotion code is active
         */
        const Active = 0;

        /**
         * Indicates if this promotion code is disabled
         */
        const Disabled = 1;

        /**
         * Indicates if this promotion code is expired
         */
        const Expired = 2;
    }