<?php


    namespace COASniffle\Objects;


    /**
     * Class AccessInformation
     * @package COASniffle\Objects
     */
    class AccessInformation
    {
        /**
         * The application tag ID
         *
         * @var int
         */
        public $AppTag;

        /**
         * Unix Timestamp for when this Access expires
         *
         * @var int
         */
        public $Expires;

        /**
         * Creates array from object
         *
         * @return array
         */
        public function toArray(): array
        {
            return array(
                'app_tag' => (int)$this->AppTag,
                'expires' => (int)$this->Expires
            );
        }

        /**
         * Constructs object from array
         *
         * @param array $data
         * @return AccessInformation
         */
        public static function fromArray(array $data): AccessInformation
        {
            $AccessInformationObject = new AccessInformation();

            if(isset($data['app_tag']))
            {
                $AccessInformationObject->AppTag = (int)$data['app_tag'];
            }

            if(isset($data['expires']))
            {
                $AccessInformationObject->Expires = (int)$data['expires'];
            }

            return $AccessInformationObject;
        }
    }