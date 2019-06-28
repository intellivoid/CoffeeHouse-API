<?php

    namespace IntellivoidAccounts\Objects;

    use IntellivoidAccounts\Abstracts\LoginStatus;

    /**
     * Class LoginRecord
     * @package IntellivoidAccounts\Objects
     */
    class LoginRecord
    {
        /**
         * The ID of the login record
         *
         * @var int
         */
        public $ID;

        /**
         * The Public ID of the Login Record
         *
         * @var string
         */
        public $PublicID;

        /**
         * The account ID associated with this Login Record
         *
         * @var int
         */
        public $AccountID;

        /**
         * The IP Address
         *
         * @var string
         */
        public $IPAddress;

        /**
         * The origin of the login
         *
         * @var string
         */
        public $Origin;

        /**
         * The Unix Timestamp of when this login has been established
         *
         * @var int
         */
        public $Time;

        /**
         * The status of the login
         *
         * @var int|LoginStatus
         */
        public $Status;

        /**
         * Converts object to array
         *
         * @return array
         */
        public function toArray(): array
        {
            return array(
                'id' => $this->ID,
                'public_id' => $this->PublicID,
                'account_id' => $this->AccountID,
                'ip_address' => $this->IPAddress,
                'origin' => $this->Origin,
                'time' => $this->Time,
                'status' => $this->Status
            );
        }

        /**
         * Creates object from array
         *
         * @param array $data
         * @return LoginRecord
         */
        public static function fromArray(array $data): LoginRecord
        {
            $LoginRecordObject = new LoginRecord();

            if(isset($data['id']))
            {
                $LoginRecordObject->ID = (int)$data['id'];
            }

            if(isset($data['public_id']))
            {
                $LoginRecordObject->PublicID = $data['public_id'];
            }

            if(isset($data['account_id']))
            {
                $LoginRecordObject->AccountID = (int)$data['account_id'];
            }

            if(isset($data['ip_address']))
            {
                $LoginRecordObject->IPAddress = $data['ip_address'];
            }

            if(isset($data['origin']))
            {
                $LoginRecordObject->Origin = $data['origin'];
            }

            if(isset($data['time']))
            {
                $LoginRecordObject->Time = (int)$data['time'];
            }

            if(isset($data['status']))
            {
                $LoginRecordObject->Status = (int)$data['status'];
            }

            return $LoginRecordObject;
        }
    }