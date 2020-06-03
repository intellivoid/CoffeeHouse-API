<?php


    namespace COASniffle\Objects;

    /**
     * Class OtlUserResponse
     * @package COASniffle\Objects
     */
    class OtlUserResponse
    {
        /**
         * Internal private ID of the user
         *
         * @var string
         */
        public $ID;

        /**
         * The username of the user
         *
         * @var string
         */
        public $Username;

        /**
         * The email of the user
         *
         * @var string
         */
        public $Email;

        /**
         * Avatars that are available
         *
         * @var array
         */
        public $Avatars;

        /**
         * The roles that this user has
         *
         * @var array
         */
        public $Roles;

        /**
         * Returns an array which represents this object
         *
         * @return array
         */
        public function toArray(): array
        {
            return array(
                'id' => $this->ID,
                'username' => $this->Username,
                'email' => $this->Email,
                'avatars' => $this->Avatars,
                'roles' => $this->Roles
            );
        }

        /**
         * Constructs object from array
         *
         * @param array $data
         * @return OtlUserResponse
         */
        public static function fromArray(array $data): OtlUserResponse
        {
            $OtlUserObject = new OtlUserResponse();

            if(isset($data['id']))
            {
                $OtlUserObject->ID = $data['id'];
            }

            if(isset($data['username']))
            {
                $OtlUserObject->Username = $data['username'];
            }

            if(isset($data['email']))
            {
                $OtlUserObject->Email = $data['email'];
            }

            if(isset($data['avatars']))
            {
                $OtlUserObject->Avatars = $data['avatars'];
            }

            if(isset($data['roles']))
            {
                $OtlUserObject->Roles = $data['roles'];
            }

            return $OtlUserObject;
        }
    }