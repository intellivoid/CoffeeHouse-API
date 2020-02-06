<?php


    namespace COASniffle\Objects\UserInformation\PersonalInformation;

    /**
     * Class LastName
     * @package COASniffle\Objects\UserInformation\PersonalInformation
     */
    class LastName
    {
        /**
         * Indicates if this property is available or not
         *
         * @var bool
         */
        public $Available;

        /**
         * The value of this property
         *
         * @var null|string
         */
        public $Value;

        /**
         * Returns an array which represents this object
         *
         * @return array
         */
        public function toArray(): array
        {
            return array(
                'available' => (bool)$this->Available,
                'value' => $this->Value
            );
        }

        /**
         * Constructs object from array
         *
         * @param array $data
         * @return LastName
         */
        public static function fromArray(array $data): LastName
        {
            $LastNameData = new LastName();

            if(isset($data['available']))
            {
                $LastNameData->Available = (bool)$data['available'];
            }
            else
            {
                $LastNameData->Available = false;
            }

            if(isset($data['value']))
            {
                $LastNameData->Value = (string)$data['value'];
            }
            else
            {
                $LastNameData->Value = null;
            }

            return $LastNameData;
        }
    }