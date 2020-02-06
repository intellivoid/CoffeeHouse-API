<?php


    namespace COASniffle\Objects\UserInformation\PersonalInformation;


    class Birthday
    {
        /**
         * Indicates if this property is available or not
         *
         * @var bool
         */
        public $Available;

        /**
         * The day of the user's birthday
         *
         * @var null|int
         */
        public $Day;

        /**
         * The month of the user's birthday
         *
         * @var null|int
         */
        public $Month;

        /**
         * The year of the user's birthday
         *
         * @var null|int
         */
        public $Year;

        /**
         * Returns an array which represents this object
         *
         * @return array
         */
        public function toArray(): array
        {
            return array(
                'available' => (bool)$this->Available,
                'day' => $this->Day,
                'month' => $this->Month,
                'year' => $this->Year
            );
        }

        /**
         * Constructs object from array
         *
         * @param array $data
         * @return Birthday
         */
        public static function fromArray(array $data): Birthday
        {
            $BirthdayObject = new Birthday();

            if(isset($data['available']))
            {
                $BirthdayObject->Available = (bool)$data['available'];
            }
            else
            {
                $BirthdayObject->Available = false;
            }

            if(is_null($data['day']) == false)
            {
                $BirthdayObject->Day = (int)$data['day'];
            }
            else
            {
                $BirthdayObject->Day = null;
            }

            if(is_null($data['month']) == false)
            {
                $BirthdayObject->Month = (int)$data['month'];
            }
            else
            {
                $BirthdayObject->Month = null;
            }

            if(is_null($data['year']) == false)
            {
                $BirthdayObject->Year = (int)$data['year'];
            }
            else
            {
                $BirthdayObject->Year = null;
            }

            return $BirthdayObject;
        }
    }