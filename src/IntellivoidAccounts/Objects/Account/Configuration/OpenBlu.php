<?php

    namespace IntellivoidAccounts\Objects\Account\Configuration;

    use IntellivoidAccounts\Abstracts\OpenBluPlan;

    /**
     * Class OpenBlu
     * @package IntellivoidAccounts\Objects\Account\Configuration
     */
    class OpenBlu
    {
        /**
         * Indicates if a promotion code is used or not
         *
         * @var bool
         */
        public $CodeUsed;

        /**
         * The current plan of the API
         *
         * @var OpenBluPlan|int
         */
        public $CurrentPlan;

        /**
         * The next billing cycle
         *
         * @var int
         */
        public $NexCycle;

        /**
         * The price that gets charged each month
         *
         * @var float
         */
        public $Price;

        /**
         * The amount of monthly calls that are available, 0 is for unlimited
         *
         * @var int
         */
        public $CallsMonthly;

        /**
         * Indicates if the current plan is active or not, this can
         * deactivate due to not paying the billing cycle
         *
         * @var bool
         */
        public $Active;

        /**
         * The access key associated with this account, by default
         * if none, it's set to 0.
         *
         * @var string
         */
        public $AccessKeyPublicID;

        /**
         * OpenBlu constructor.
         */
        public function __construct()
        {
            $this->CodeUsed = false;
            $this->CurrentPlan = OpenBluPlan::None;
            $this->NexCycle = 0;
            $this->Price = 0;
            $this->CallsMonthly = 0;
            $this->Active = false;
        }

        /**
         * Converts object to array
         *
         * @return array
         */
        public function toArray(): array
        {
            return array(
                'code_used' => (bool)$this->CodeUsed,
                'current_plan' => (int)$this->CurrentPlan,
                'next_cycle' => (int)$this->NexCycle,
                'price' => (float)$this->Price,
                'calls_monthly' => (int)$this->CallsMonthly,
                'active' => (bool)$this->Active,
                'access_key_public_id' => $this->AccessKeyPublicID
            );
        }

        /**
         * Creates object from array
         *
         * @param array $data
         * @return OpenBlu
         */
        public static function fromArray(array $data): OpenBlu
        {
            $ConfigurationObject = new OpenBlu();

            if(isset($data['code_used']))
            {
                $ConfigurationObject->CodeUsed = (bool)$data['code_used'];
            }

            if(isset($data['current_plan']))
            {
                $ConfigurationObject->CurrentPlan = (int)$data['current_plan'];
            }

            if(isset($data['next_cycle']))
            {
                $ConfigurationObject->NexCycle = (int)$data['next_cycle'];
            }

            if(isset($data['price']))
            {
                $ConfigurationObject->Price = (float)$data['price'];
            }

            if(isset($data['calls_monthly']))
            {
                $ConfigurationObject->CallsMonthly = (int)$data['calls_monthly'];
            }

            if(isset($data['active']))
            {
                $ConfigurationObject->Active = (bool)$data['active'];
            }

            if(isset($data['auto_renew']))
            {
                $ConfigurationObject->AutoRenew = (bool)$data['auto_renew'];
            }

            if(isset($data['access_key_public_iid']))
            {
                $ConfigurationObject->AccessKeyPublicID = $data['access_key_public_id'];
            }

            return $ConfigurationObject;
        }
    }