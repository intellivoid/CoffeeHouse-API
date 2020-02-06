<?php


    namespace COASniffle\Objects;


    use COASniffle\Objects\UserInformation\Avatar;
    use COASniffle\Objects\UserInformation\EmailAddress;
    use COASniffle\Objects\UserInformation\PersonalInformation;

    /**
     * Class UserInformation
     * @package COASniffle\Objects
     */
    class UserInformation
    {
        /**
         * Unique Tag ID for the user
         *
         * @var int
         */
        public $Tag;

        /**
         * The public ID for the user
         *
         * @var string
         */
        public $PublicID;

        /**
         * The user's Username
         *
         * @var string
         */
        public $Username;

        /**
         * The avatar's that are available with this user
         *
         * @var Avatar
         */
        public $Avatar;

        /**
         * The user's Email Address
         *
         * @var EmailAddress
         */
        public $EmailAddress;

        /**
         * The user's Personal Information
         *
         * @var PersonalInformation
         */
        public $PersonalInformation;

        /**
         * Returns an array which represents this
         *
         * @return array
         */
        public function toArray(): array
        {
            return array(
                'tag' => (int)$this->Tag,
                'public_id' => $this->PublicID,
                'username' => $this->Username,
                'avatar' => $this->Avatar->toArray(),
                'email_address' => $this->EmailAddress->toArray(),
                'personal_information' => $this->PersonalInformation->toArray()
            );
        }

        /**
         * Constructs object from array
         *
         * @param array $data
         * @return UserInformation
         */
        public static function fromArray(array $data): UserInformation
        {
            $UserInformationObject = new UserInformation();

            if(isset($data['tag']))
            {
                $UserInformationObject->Tag = (int)$data['tag'];
            }

            if(isset($data['public_id']))
            {
                $UserInformationObject->PublicID = $data['public_id'];
            }

            if(isset($data['username']))
            {
                $UserInformationObject->Username = $data['username'];
            }

            if(isset($data['avatar']))
            {
                $UserInformationObject->Avatar = Avatar::fromArray($data['avatar']);
            }
            else
            {
                $UserInformationObject->Avatar = Avatar::fromArray(array());
            }

            if(isset($data['email_address']))
            {
                $UserInformationObject->EmailAddress = EmailAddress::fromArray($data['email_address']);
            }
            else
            {
                $UserInformationObject->EmailAddress = EmailAddress::fromArray(array());
            }

            if(isset($data['personal_information']))
            {
                $UserInformationObject->PersonalInformation = PersonalInformation::fromArray($data['personal_information']);
            }
            else
            {
                $UserInformationObject->PersonalInformation = PersonalInformation::fromArray(array());
            }

            return $UserInformationObject;
        }
    }