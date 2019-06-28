<?php

    namespace IntellivoidAccounts\Utilities;

    /**
     * Class Validate
     * @package IntellivoidAccounts\Utilities
     */
    class Validate
    {
        /**
         * Determines if the given username is valid
         *
         * Must be alphanumeric, greater than 5 characters but no greater than 64
         *
         * @param string $input
         * @return bool
         */
        public static function username(string $input): bool
        {
            if(!preg_match('/^[a-zA-Z0-9]{5,}$/', $input))
            {
                return false;
            }

            if(strlen($input) > 64)
            {
                return false;
            }

            return true;
        }

        /**
         * Determines if the password is valid
         *
         * must be greater than 8 characters but no greater than 128
         *
         * @param string $input
         * @return bool
         */
        public static function password(string $input): bool
        {
            if(strlen($input) < 8)
            {
                return false;
            }

            if(strlen($input) > 128)
            {
                return false;
            }

            return true;
        }

        /**
         * Determines if the email is valid
         *
         * @param string $input
         * @return bool
         */
        public static function email(string $input): bool
        {
            if(!filter_var($input, FILTER_VALIDATE_EMAIL))
            {
                return false;
            }

            if(strlen($input) > 128)
            {
                return false;
            }

            return true;
        }

        /**
         * Validates if the given IP is ipv4 and or ipv6 valid
         *
         * @param string $ip_address
         * @return bool
         */
        public static function ip(string $ip_address): bool
        {
            if(filter_var($ip_address, FILTER_VALIDATE_IP,FILTER_FLAG_IPV4) == true)
            {
                return true;
            }

            if(filter_var($ip_address, FILTER_VALIDATE_IP,FILTER_FLAG_IPV6) == true)
            {
                return true;
            }

            return false;
        }

        /**
         * Validates if a vendor's name is valid or not
         *
         * @param string $input
         * @return bool
         */
        public static function vendor(string $input): bool
        {
            if(strlen($input) == 0)
            {
                return false;
            }

            if(strlen($input) > 200)
            {
                return false;
            }

            return true;
        }
    }