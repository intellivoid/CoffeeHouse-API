<?php


    namespace COASniffle\Objects\UserInformation;

    /**
     * Class Avatar
     * @package COASniffle\Objects\UserInformation
     */
    class Avatar
    {
        /**
         * @var string
         */
        public $Normal;

        /**
         * @var string
         */
        public $Original;

        /**
         * @var string
         */
        public $Preview;

        /**
         * @var string
         */
        public $Small;

        /**
         * @var string
         */
        public $Tiny;

        /**
         * Returns an array which represents this objects
         *
         * @return array
         */
        public function toArray(): array
        {
            return array(
                'normal' => $this->Normal,
                'original' => $this->Original,
                'preview' => $this->Preview,
                'small' => $this->Small,
                'tiny' => $this->Tiny
            );
        }

        /**
         * Constructs object from array
         *
         * @param array $data
         * @return Avatar
         */
        public static function fromArray(array $data): Avatar
        {
            $AvatarObject = new Avatar();

            if(isset($data['normal']))
            {
                $AvatarObject->Normal = (string)$data['normal'];
            }

            if(isset($data['original']))
            {
                $AvatarObject->Original = (string)$data['original'];
            }

            if(isset($data['preview']))
            {
                $AvatarObject->Preview = (string)$data['preview'];
            }

            if(isset($data['small']))
            {
                $AvatarObject->Small = (string)$data['small'];
            }

            if(isset($data['tiny']))
            {
                $AvatarObject->Tiny = (string)$data['tiny'];
            }

            return $AvatarObject;
        }
    }