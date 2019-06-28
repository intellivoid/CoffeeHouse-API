<?php

    namespace IntellivoidAccounts\Objects\Account;
    use IntellivoidAccounts\Objects\Account\PersonalInformation\BirthDate;

    /**
     * Class PersonalInformation
     * @package IntellivoidAccounts\Objects\Account
     */
    class PersonalInformation
    {
        /**
         * PersonalInformation constructor.
         */
        public function __construct()
        {
            $this->BirthDate = new BirthDate();
        }

        /**
         * The first name of the user
         *
         * @var string
         */
        public $FirstName;

        /**
         * The last name of the user
         *
         * @var string
         */
        public $LastName;

        /**
         * The country that this user is located in
         *
         * @var string
         */
        public $Country;

        /**
         * The date of birth
         *
         * @var BirthDate
         */
        public $BirthDate;

        /**
         * User's phone number
         *
         * @var string
         */
        public $PhoneNumber;

        /**
         * Converts object to array
         *
         * @return array
         */
        public function toArray(): array
        {
            return array(
                'first_name' => $this->FirstName,
                'last_name' => $this->LastName,
                'country' => $this->Country,
                'birth_date' => $this->BirthDate->toArray(),
                'phone_number' => $this->PhoneNumber
            );
        }

        public static function fromArray(array $data): PersonalInformation
        {
            $PersonalInformationObject = new PersonalInformation();

            if(isset($data['first_name']))
            {
                $PersonalInformationObject->FirstName = $data['first_name'];
            }
            else
            {
                $PersonalInformationObject->FirstName = null;
            }

            if(isset($data['last_name']))
            {
                $PersonalInformationObject->LastName = $data['last_name'];
            }
            else
            {
                $PersonalInformationObject->LastName = null;
            }

            if(isset($data['country']))
            {
                $PersonalInformationObject->Country = $data['country'];
            }
            else
            {
                $PersonalInformationObject->Country = null;
            }

            if(isset($data['birth_date']))
            {
                $PersonalInformationObject->BirthDate = BirthDate::fromArray($data['birth_date']);
            }
            else
            {
                $PersonalInformationObject->BirthDate = new BirthDate();
            }

            if(isset($data['phone_number']))
            {
                $PersonalInformationObject->PhoneNumber = $data['phone_number'];
            }
            else
            {
                $PersonalInformationObject->PhoneNumber = null;
            }

            return $PersonalInformationObject;
        }
    }