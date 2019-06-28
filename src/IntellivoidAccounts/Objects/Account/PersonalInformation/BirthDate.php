<?php

    namespace IntellivoidAccounts\Objects\Account\PersonalInformation;

    /**
     * Class BirthDate
     * @package IntellivoidAccounts\Objects\Account\PersonalInformation
     */
    class BirthDate
    {
        /**
         * The day of birth
         *
         * @var int|null
         */
        public $Day;

        /**
         * Month of birth
         *
         * @var int|null
         */
        public $Month;

        /**
         * Year of birth
         *
         * @var int|null
         */
        public $Year;

        /**
         * Converts object to array
         *
         * @return array
         */
        public function toArray(): array
        {
            return array(
                'day' => $this->Day,
                'month' => $this->Month,
                'year' => $this->Year
            );
        }

        /**
         * Creates object from array
         *
         * @param array $data
         * @return BirthDate
         */
        public static function fromArray(array $data): BirthDate
        {
            $BirthDateObject = new BirthDate();

            if(isset($data['day']))
            {
                if($data['day'] == null)
                {
                    $BirthDateObject->Day = null;
                }
                else
                {
                    $BirthDateObject->Day = (int)$data['day'];
                }
            }
            else
            {
                $BirthDateObject->Day = 0;
            }

            if(isset($data['month']))
            {
                if($data['month'] == null)
                {
                    $BirthDateObject->Month = null;
                }
                else
                {
                    $BirthDateObject->Month = (int)$data['month'];
                }
            }
            else
            {
                $BirthDateObject->Month = 0;
            }

            if(isset($data['year']))
            {
                if($data['year'] == null)
                {
                    $BirthDateObject->Year = null;
                }
                else
                {
                    $BirthDateObject->Year = (int)$data['year'];
                }
            }
            else
            {
                $BirthDateObject->Year = 0;
            }

            return $BirthDateObject;
        }
    }