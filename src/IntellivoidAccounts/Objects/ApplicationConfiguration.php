<?php

    namespace IntellivoidAccounts\Objects;

    /**
     * Class ApplicationConfiguration
     * @package IntellivoidAccounts\Objects
     */
    class ApplicationConfiguration
    {
        /**
         * The ID of the Application Configuration for the Account
         *
         * @var int
         */
        public $ID;

        /**
         * The Public ID of this configuration
         *
         * @var string
         */
        public $PublicID;

        /**
         * The name of the application that operates this configuration
         *
         * @var string
         */
        public $ApplicationName;

        /**
         * The account that this configuration is linked to
         *
         * @var int
         */
        public $AccountID;

        /**
         * The data associated with this configuration
         *
         * @var array
         */
        public $Data;

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
                'application_name' => $this->ApplicationName,
                'account_id' => $this->AccountID,
                'data' => $this->Data
            );
        }

        /**
         * Creates object from array
         *
         * @param array $data
         * @return ApplicationConfiguration
         */
        public static function fromArray(array $data): ApplicationConfiguration
        {
            $ApplicationConfigurationObject = new ApplicationConfiguration();

            if(isset($data['id']))
            {
                $ApplicationConfigurationObject->ID = (int)$data['id'];
            }

            if(isset($data['public_id']))
            {
                $ApplicationConfigurationObject->PublicID = $data['public_id'];
            }

            if(isset($data['application_name']))
            {
                $ApplicationConfigurationObject->ApplicationName = $data['application_name'];
            }

            if(isset($data['account_id']))
            {
                $ApplicationConfigurationObject->AccountID = (int)$data['account_id'];
            }

            if(isset($data['data']))
            {
                $ApplicationConfigurationObject->Data = $data['data'];
            }
            else
            {
                $ApplicationConfigurationObject->Data = array();
            }

            return $ApplicationConfigurationObject;
        }
    }