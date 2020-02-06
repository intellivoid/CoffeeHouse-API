<?php


    namespace COASniffle\Objects\UserInformation;


    use COASniffle\Objects\UserInformation\PersonalInformation\Birthday;
    use COASniffle\Objects\UserInformation\PersonalInformation\FirstName;
    use COASniffle\Objects\UserInformation\PersonalInformation\LastName;

    class PersonalInformation
    {
        /**
         * Indicates if this property is available or not
         *
         * @var bool
         */
        public $Available;

        /**
         * The first name of the user
         *
         * @var FirstName
         */
        public $FirstName;

        /**
         * The last name of the user
         *
         * @var LastName
         */
        public $LastName;

        /**
         * The user's birthday
         *
         * @var Birthday
         */
        public $Birthday;

        public function toArray(): array
        {
            return array(
                'available' => (bool)$this->Available,
                'first_name' => $this->FirstName->toArray(),
                'last_name' => $this->LastName->toArray(),
                'birthday' => $this->Birthday->toArray()
            );
        }

        /**
         * Constructs object from array
         *
         * @param array $data
         * @return PersonalInformation
         */
        public static function fromArray(array $data): PersonalInformation
        {
            $PersonalInformationObject = new PersonalInformation();

            if(is_null($data['available']) == false)
            {
                $PersonalInformationObject->Available = (bool)$data['available'];
            }

            if(isset($data['first_name']))
            {
                $PersonalInformationObject->FirstName = FirstName::fromArray($data['first_name']);
            }
            else
            {
                $PersonalInformationObject->FirstName = new FirstName();
                $PersonalInformationObject->FirstName->Available = false;
                $PersonalInformationObject->FirstName->Value = null;
            }

            if(isset($data['last_name']))
            {
                $PersonalInformationObject->LastName = LastName::fromArray($data['last_name']);
            }
            else
            {
                $PersonalInformationObject->LastName = new LastName();
                $PersonalInformationObject->LastName->Available = false;
                $PersonalInformationObject->LastName->Value = null;
            }

            if(isset($data['birthday']))
            {
                $PersonalInformationObject->Birthday = Birthday::fromArray($data['birthday']);
            }
            else
            {
                $PersonalInformationObject->Birthday = new Birthday();
                $PersonalInformationObject->Birthday->Available = false;
                $PersonalInformationObject->Birthday->Day = 0;
                $PersonalInformationObject->Birthday->Month = 0;
                $PersonalInformationObject->Birthday->Year = 0;
            }

            return $PersonalInformationObject;
        }
    }