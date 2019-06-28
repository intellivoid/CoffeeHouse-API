<?php

    namespace IntellivoidAccounts\Utilities;

    /**
     * Class Hashing
     * @package IntellivoidAccounts\Utilities
     */
    class Hashing
    {
        /**
         * Peppers a hash using whirlpool
         *
         * @param string $Data The hash to pepper
         * @param int $Min Minimal amounts of executions
         * @param int $Max Maximum amount of executions
         * @return string
         */
        public static function pepper(string $Data, int $Min = 100, int $Max = 1000): string
        {
            $n = rand($Min, $Max);
            $res = '';
            $Data = hash('whirlpool', $Data);
            for ($i=0,$l=strlen($Data) ; $l ; $l--)
            {
                $i = ($i+$n-1) % $l;
                $res = $res . $Data[$i];
                $Data = ($i ? substr($Data, 0, $i) : '') . ($i < $l-1 ? substr($Data, $i+1) : '');
            }
            return($res);
        }

        /**
         * Calculates the Public ID of the Account
         *
         * @param string $username
         * @param string $password
         * @param string $email
         * @return string
         */
        public static function publicID(string $username, string $password, string $email): string
        {
            $username = hash('haval256,3', $username);
            $password = hash('haval192,4', $password);
            $email = hash('haval256,5', $email);

            $crc_2 = hash('haval160,3', $username . $email);
            $crc_3 = hash('haval128,3', $username . $password);

            return hash('ripemd320', $crc_2 . $crc_3);
        }

        /**
         * Hashes the password
         *
         * @param string $password
         * @return string
         */
        public static function password(string $password)
        {
            return hash('sha512', $password) .  hash('haval256,5', $password);
        }

        /**
         * Creates a public ID for a login record
         *
         * @param int $account_id
         * @param int $unix_timestamp
         * @param int $status
         * @param string $origin
         * @param string $ip_address
         * @return string
         */
        public static function loginPublicID(int $account_id, int $unix_timestamp, int $status, string $origin, string $ip_address)
        {
            $account_id = hash('haval256,5', $account_id);
            $unix_timestamp = hash('haval256,5', $unix_timestamp);
            $status = hash('haval256,5', $status);
            $origin = hash('haval256,5', $origin);
            $ip_address = hash('haval256,5', $ip_address);

            $crc1 = hash('sha256', $account_id . $unix_timestamp . $status);
            $crc2 = hash('sha256', $origin, $ip_address);

            return $crc1 . $crc2;
        }

        /**
         * Creates a public ID for a balance transaction record
         *
         * @param int $account_id
         * @param int $unix_timestamp
         * @param int $amount
         * @param string $source
         * @return string
         */
        public static function balanceTransactionPublicID(int $account_id, int $unix_timestamp, int $amount, string $source): string
        {
            $builder = self::pepper($source);
            $builder .= hash('crc32', $account_id);
            $builder .= hash('crc32', $unix_timestamp);
            $builder .= hash('crc32', $amount);
            $builder .= hash('crc32', $source);

            return $builder;
        }

        public static function transactionRecordPublicID(int $account_id, int $unix_timestamp, float $amount, string $vendor, int $operator_type): string
        {
            $builder = self::pepper($vendor);

            $builder .= hash('crc32', $account_id);
            $builder .= hash('crc32', $unix_timestamp - 100);
            $builder .= hash('crc32', $amount + 200);
            $builder .= hash('crc32', $operator_type + 5);

            return $builder;
        }
    }