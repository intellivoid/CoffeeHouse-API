<?php


    namespace IntellivoidAccounts\Managers;

    use BasicCalculator\BC;
    use IntellivoidAccounts\Abstracts\OperatorType;
    use IntellivoidAccounts\Abstracts\SearchMethods\AccountSearchMethod;
    use IntellivoidAccounts\Abstracts\SearchMethods\TransactionRecordSearchMethod;
    use IntellivoidAccounts\Abstracts\TransactionType;
    use IntellivoidAccounts\Exceptions\AccountNotFoundException;
    use IntellivoidAccounts\Exceptions\DatabaseException;
    use IntellivoidAccounts\Exceptions\InsufficientFundsException;
    use IntellivoidAccounts\Exceptions\InvalidAccountStatusException;
    use IntellivoidAccounts\Exceptions\InvalidEmailException;
    use IntellivoidAccounts\Exceptions\InvalidPasswordException;
    use IntellivoidAccounts\Exceptions\InvalidSearchMethodException;
    use IntellivoidAccounts\Exceptions\InvalidTransactionTypeException;
    use IntellivoidAccounts\Exceptions\InvalidUsernameException;
    use IntellivoidAccounts\Exceptions\InvalidVendorException;
    use IntellivoidAccounts\Exceptions\TransactionRecordNotFoundException;
    use IntellivoidAccounts\IntellivoidAccounts;
    use IntellivoidAccounts\Objects\TransactionRecord;
    use IntellivoidAccounts\Utilities\Hashing;
    use IntellivoidAccounts\Utilities\Validate;

    /**
     * Class TransactionRecordManager
     * @package IntellivoidAccounts\Managers
     */
    class TransactionRecordManager
    {
        /**
         * @var IntellivoidAccounts
         */
        private $intellivoidAccounts;

        /**
         * TransactionRecordManager constructor.
         * @param IntellivoidAccounts $intellivoidAccounts
         */
        public function __construct(IntellivoidAccounts $intellivoidAccounts)
        {
            $this->intellivoidAccounts = $intellivoidAccounts;
        }

        /**
         * @param int $account_id
         * @param float $amount
         * @param string $vendor
         * @param int $type
         * @return TransactionRecord
         * @throws AccountNotFoundException
         * @throws DatabaseException
         * @throws InsufficientFundsException
         * @throws InvalidSearchMethodException
         * @throws InvalidTransactionTypeException
         * @throws InvalidVendorException
         * @throws InvalidAccountStatusException
         * @throws InvalidEmailException
         * @throws InvalidPasswordException
         * @throws InvalidUsernameException
         * @throws TransactionRecordNotFoundException
         */
        public function createTransaction(int $account_id, float $amount, string $vendor, int $type): TransactionRecord
        {
            if(Validate::vendor($vendor) == false)
            {
                throw new InvalidVendorException();
            }

            switch($type)
            {
                case TransactionType::Payment:
                    $type = (int)$type;
                    break;

                case TransactionType::SubscriptionPayment:
                    $type = (int)$type;
                    break;

                case TransactionType::Deposit:
                    $type = (int)$type;
                    break;

                case TransactionType::Withdraw:
                    $type = (int)$type;
                    break;

                case TransactionType::Refund:
                    $type = (int)$type;
                    break;

                default:
                    throw new InvalidTransactionTypeException();
            }

            $Account = $this->intellivoidAccounts->getAccountManager()->getAccount(AccountSearchMethod::byId, $account_id);

            $OperatorType = OperatorType::None;

            if($amount > 0)
            {
                $OperatorType = OperatorType::Deposit;
                $Account->Configuration->Balance = (float)BC::add($Account->Configuration->Balance, abs($amount), 2);
            }
            elseif($amount < 0)
            {
                $OperatorType = OperatorType::Withdraw;
                $Calculation = (float)BC::sub($Account->Configuration->Balance, abs($amount), 2);

                if($Calculation < 0)
                {
                    throw new InsufficientFundsException();
                }

                $Account->Configuration->Balance = (float)BC::sub($Account->Configuration->Balance, abs($amount), 2);
            }

            $Timestamp = (int)time();
            $PublicID = Hashing::transactionRecordPublicID(
                $account_id, $Timestamp, abs($amount), $vendor, $OperatorType
            );
            $PublicID = $this->intellivoidAccounts->database->real_escape_string($PublicID);
            $AccountID = (int)$account_id;
            $Amount = abs($amount);
            $OperatorType = (int)$OperatorType;
            $Type = (int)$type;
            $Vendor = $this->intellivoidAccounts->database->real_escape_string($vendor);

            $Query = "INSERT INTO `transaction_records` (public_id, account_id, amount, operator_type, type, vendor, timestamp) VALUES ('$PublicID', $AccountID, $Amount, $OperatorType, $Type, '$Vendor', $Timestamp)";
            $QueryResults = $this->intellivoidAccounts->database->query($Query);

            if($QueryResults == false)
            {
                throw new DatabaseException($Query, $this->intellivoidAccounts->database->error);
            }

            $this->intellivoidAccounts->getAccountManager()->updateAccount($Account);
            return $this->getTransactionRecord(TransactionRecordSearchMethod::byPublicId, $PublicID);
        }

        /**
         * Returns an existing transaction record from the database
         *
         * @param string $search_method
         * @param string $value
         * @return TransactionRecord
         * @throws DatabaseException
         * @throws InvalidSearchMethodException
         * @throws TransactionRecordNotFoundException
         */
        public function getTransactionRecord(string $search_method, string $value): TransactionRecord
        {
            switch($search_method)
            {
                case TransactionRecordSearchMethod::byId:
                    $search_method = $this->intellivoidAccounts->database->real_escape_string($search_method);
                    $value = (int)$value;
                    break;

                case TransactionRecordSearchMethod::byPublicId:
                    $search_method = $this->intellivoidAccounts->database->real_escape_string($search_method);
                    $value = "'" . $this->intellivoidAccounts->database->real_escape_string($value) . "'";
                    break;

                default:
                    throw new InvalidSearchMethodException();
            }

            $Query = "SELECT id, public_id, account_id, amount, operator_type, type, vendor, timestamp FROM `transaction_records` where $search_method=$value";
            $QueryResults = $this->intellivoidAccounts->database->query($Query);

            if($QueryResults == false)
            {
                throw new DatabaseException($Query, $this->intellivoidAccounts->database->error);
            }
            else
            {
                if($QueryResults->num_rows !== 1)
                {
                    throw new TransactionRecordNotFoundException();
                }

                return TransactionRecord::fromArray($QueryResults->fetch_array(MYSQLI_ASSOC));
            }
        }
    }