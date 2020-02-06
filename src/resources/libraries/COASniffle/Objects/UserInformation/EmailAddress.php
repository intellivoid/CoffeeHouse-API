<?php


    namespace COASniffle\Objects\UserInformation;


    /**
     * The email address property
     *
     * Class EmailAddress
     * @package COASniffle\Objects\UserInformation
     */
    class EmailAddress
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
         * @return EmailAddress
         */
        public static function fromArray(array $data): EmailAddress
        {
            $EmailAddressData = new EmailAddress();

            if(isset($data['available']))
            {
                $EmailAddressData->Available = (bool)$data['available'];
            }
            else
            {
                $EmailAddressData->Available = false;
            }

            if(isset($data['value']))
            {
                $EmailAddressData->Value = (string)$data['value'];
            }
            else
            {
                $EmailAddressData->Value = null;
            }

            return $EmailAddressData;
        }
    }