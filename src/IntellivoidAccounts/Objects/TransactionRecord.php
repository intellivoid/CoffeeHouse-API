<?php


    namespace IntellivoidAccounts\Objects;


    use IntellivoidAccounts\Abstracts\OperatorType;
    use IntellivoidAccounts\Abstracts\TransactionType;

    class TransactionRecord
    {
        /**
         * Internal Transaction ID
         *
         * @var int
         */
        public $ID;

        /**
         * The Public Transaction ID
         *
         * @var string
         */
        public $PublicID;

        /**
         * The Account ID that's involved with this transaction
         *
         * @var int
         */
        public $AccountID;

        /**
         * The amount that's involved in this transaction
         *
         * @var float
         */
        public $Amount;

        /**
         * @var OperatorType
         */
        public $OperatorType;

        /**
         * @var TransactionType
         */
        public $Type;

        /**
         * @var string
         */
        public $Vendor;

        /**
         * @var int
         */
        public $Timestamp;

        /**
         * Creates array from object
         *
         * @return array
         */
        public function toArray(): array
        {
            return array(
                'id' => (int)$this->ID,
                'public_id' => $this->PublicID,
                'account_id' => (int)$this->AccountID,
                'amount' => (float)$this->Amount,
                'operator_type' => (int)$this->OperatorType,
                'type' => (int)$this->Type,
                'vendor' => $this->Vendor,
                'timestamp' => (int)$this->Timestamp
            );
        }

        /**
         * Creates object form array
         *
         * @param array $data
         * @return TransactionRecord
         */
        public static function fromArray(array $data): TransactionRecord
        {
            $TransactionRecordObject = new TransactionRecord();

            if(isset($data['id']))
            {
                $TransactionRecordObject->ID = (int)$data['id'];
            }

            if(isset($data['public_id']))
            {
                $TransactionRecordObject->PublicID = $data['public_id'];
            }

            if(isset($data['account_id']))
            {
                $TransactionRecordObject->AccountID = (int)$data['account_id'];
            }

            if(isset($data['amount']))
            {
                $TransactionRecordObject->Amount = (float)$data['amount'];
            }

            if(isset($data['operator_type']))
            {
                $TransactionRecordObject->OperatorType = (int)$data['operator_type'];
            }

            if(isset($data['type']))
            {
                $TransactionRecordObject->Type = (int)$data['type'];
            }

            if(isset($data['vendor']))
            {
                $TransactionRecordObject->Vendor = $data['vendor'];
            }

            if(isset($data['timestamp']))
            {
                $TransactionRecordObject->Timestamp = (int)$data['timestamp'];
            }

            return $TransactionRecordObject;
        }
    }