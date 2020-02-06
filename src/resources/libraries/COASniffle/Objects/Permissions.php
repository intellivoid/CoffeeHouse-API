<?php


    namespace COASniffle\Objects;

    /**
     * Class Permissions
     * @package COASniffle\Objects
     */
    class Permissions
    {
        /**
         * Can view public information like the user's username and avatar
         *
         * @var bool
         */
        public $ViewPublicInformation;

        /**
         * Can view the user's Email Address
         *
         * @var bool
         */
        public $ViewEmailAddress;

        /**
         * Can view personal information like First and Last name and the users Birthday
         *
         * @var bool
         */
        public $ReadPersonalInformation;

        /**
         * Can send notifications via Telegram
         *
         * @var bool
         */
        public $SendTelegramNotifications;

        /**
         * Can make purchases or start subscriptions on the user's behalf
         *
         * @var bool
         */
        public $MakePurchases;

        /**
         * @return array
         */
        public function toArray()
        {
            return array(
                'view_public_information' => $this->ViewPublicInformation,
                'view_email_address' => $this->ViewEmailAddress,
                'read_personal_information' => $this->ReadPersonalInformation,
                'send_telegram_notifications' => $this->SendTelegramNotifications,
                'make_purchases' => $this->MakePurchases
            );
        }

        /**
         * Constructs object from array
         *
         * @param array $data
         * @return Permissions
         */
        public static function fromArray(array $data): Permissions
        {
            $PermissionsObject = new Permissions();

            if(isset($data['view_public_information']))
            {
                $PermissionsObject->ViewPublicInformation = $data['view_public_information'];
            }
            else
            {
                $PermissionsObject->ViewPublicInformation = false;
            }

            if(isset($data['view_email_address']))
            {
                $PermissionsObject->ViewEmailAddress = $data['view_email_address'];
            }
            else
            {
                $PermissionsObject->ViewEmailAddress = false;
            }

            if(isset($data['read_personal_information']))
            {
                $PermissionsObject->ReadPersonalInformation = $data['read_personal_information'];
            }
            else
            {
                $PermissionsObject->ReadPersonalInformation = false;
            }

            if(isset($data['send_telegram_notifications']))
            {
                $PermissionsObject->SendTelegramNotifications = $data['send_telegram_notifications'];
            }
            else
            {
                $PermissionsObject->SendTelegramNotifications = false;
            }

            if(isset($data['make_purchases']))
            {
                $PermissionsObject->MakePurchases = $data['make_purchases'];
            }
            else
            {
                $PermissionsObject->MakePurchases = false;
            }

            return $PermissionsObject;
        }
    }