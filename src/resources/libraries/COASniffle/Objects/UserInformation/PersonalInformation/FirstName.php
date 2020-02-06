<?php


    namespace COASniffle\Objects\UserInformation\PersonalInformation;

    /**
     * Class FirstName
     * @package COASniffle\Objects\UserInformation\PersonalInformation
     */
    class FirstName
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
         * @return FirstName
         */
        public static function fromArray(array $data): FirstName
        {
            $FirstNameData = new FirstName();

            if(isset($data['available']))
            {
                $FirstNameData->Available = (bool)$data['available'];
            }
            else
            {
                $FirstNameData->Available = false;
            }

            if(isset($data['value']))
            {
                $FirstNameData->Value = (string)$data['value'];
            }
            else
            {
                $FirstNameData->Value = null;
            }

            return $FirstNameData;
        }
    }