<?php

    namespace IntellivoidAccounts\Objects;
    use IntellivoidAccounts\Objects\Account\Configuration;
    use IntellivoidAccounts\Objects\Account\PersonalInformation;

    /**
     * Class Account
     * @package IntellivoidAccounts\Objects
     */
    class Account
    {
        /**
         * The unique ID which identifies the account in the database
         *
         * @var int
         */
        public $ID;

        /**
         * The public ID for this Account
         *
         * @var string
         */
        public $PublicID;

        /**
         * The username for this account
         *
         * @var string
         */
        public $Username;

        /**
         * The Email Address for this Account
         *
         * @var string
         */
        public $Email;

        /**
         * The access password for this account (hashed)
         *
         * @var string
         */
        public $Password;

        /**
         * The status of the account
         *
         * @var int
         */
        public $Status;

        /**
         * Personal information related to the user
         *
         * @var PersonalInformation
         */
        public $PersonalInformation;

        /**
         * Account Configuration for various properties
         *
         * @var Configuration
         */
        public $Configuration;

        /**
         * The ID which points to the last login record in the database
         *
         * @var int
         */
        public $LastLoginID;

        /**
         * The date that this account was created
         *
         * @var int
         */
        public $CreationDate;

        /**
         * Account constructor.
         */
        public function __construct()
        {
            $this->PersonalInformation = new PersonalInformation();
            $this->Configuration = new Configuration();
        }

        /**
         * Converts object to array
         *
         * @return array
         */
        public function toArray(): array
        {
            return array(
                'id' => (int)$this->ID,
                'public_id' => $this->PublicID,
                'username' => $this->Username,
                'email' => $this->Email,
                'password' => $this->Password,
                'status' => (int)$this->Status,
                'personal_information' => $this->PersonalInformation->toArray(),
                'configuration' => $this->Configuration->toArray(),
                'last_login_id' => $this->LastLoginID,
                'creation_date' => (int)$this->CreationDate
            );
        }

        /**
         * Creates object from array
         *
         * @param array $data
         * @return Account
         */
        public static function fromArray(array $data): Account
        {
            $AccountObject = new Account();

            if(isset($data['id']))
            {
                $AccountObject->ID = (int)$data['id'];
            }

            if(isset($data['public_id']))
            {
                $AccountObject->PublicID = $data['public_id'];
            }

            if(isset($data['username']))
            {
                $AccountObject->Username = $data['username'];
            }

            if(isset($data['email']))
            {
                $AccountObject->Email = $data['email'];
            }

            if(isset($data['password']))
            {
                $AccountObject->Password = $data['password'];
            }

            if(isset($data['status']))
            {
                $AccountObject->Status = (int)$data['status'];
            }

            if(isset($data['personal_information']))
            {
                $AccountObject->PersonalInformation = PersonalInformation::fromArray($data['personal_information']);
            }
            else
            {
                $AccountObject->PersonalInformation = new PersonalInformation();
            }

            if(isset($data['configuration']))
            {
                $AccountObject->Configuration = Configuration::fromArray($data['configuration']);
            }
            else
            {
                $AccountObject->Configuration = new Configuration();
            }

            if(isset($data['last_login_id']))
            {
                $AccountObject->LastLoginID = (int)$data['last_login_id'];
            }

            if(isset($data['creation_date']))
            {
                $AccountObject->CreationDate = (int)$data['creation_date'];
            }

            return $AccountObject;
        }

    }