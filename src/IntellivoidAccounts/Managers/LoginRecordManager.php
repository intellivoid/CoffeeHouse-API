<?php

    namespace IntellivoidAccounts\Managers;

    use IntellivoidAccounts\Abstracts\LoginStatus;
    use IntellivoidAccounts\Exceptions\AccountNotFoundException;
    use IntellivoidAccounts\Exceptions\DatabaseException;
    use IntellivoidAccounts\Exceptions\InvalidIpException;
    use IntellivoidAccounts\Exceptions\InvalidLoginStatusException;
    use IntellivoidAccounts\Exceptions\InvalidSearchMethodException;
    use IntellivoidAccounts\IntellivoidAccounts;
    use IntellivoidAccounts\Utilities\Hashing;
    use IntellivoidAccounts\Utilities\Validate;

    /**
     * Class LoginRecordManager
     * @package IntellivoidAccounts\Managers
     */
    class LoginRecordManager
    {
        /**
         * @var IntellivoidAccounts
         */
        private $intellivoidAccounts;

        /**
         * LoginRecordManager constructor.
         * @param IntellivoidAccounts $intellivoidAccounts
         */
        public function __construct(IntellivoidAccounts $intellivoidAccounts)
        {
            $this->intellivoidAccounts = $intellivoidAccounts;
        }

        /**
         * Creates a new Login Record in the database
         *
         * @param int $account_id
         * @param string $ip_address
         * @param int $status
         * @param string $origin
         * @return bool
         * @throws AccountNotFoundException
         * @throws DatabaseException
         * @throws InvalidIpException
         * @throws InvalidLoginStatusException
         * @throws InvalidSearchMethodException
         */
        public function createLoginRecord(int $account_id, string $ip_address, int $status, string $origin): bool
        {
            if(Validate::ip($ip_address) == false)
            {
                throw new InvalidIpException();
            }

            if($this->intellivoidAccounts->getAccountManager()->IdExists($account_id) == false)
            {
                throw new AccountNotFoundException();
            }

            switch($status)
            {
                case LoginStatus::Successful:
                    break;

                case LoginStatus::IncorrectCredentials:
                    break;

                case LoginStatus::IncorrectVerificationCode:
                    break;

                default:
                    throw new InvalidLoginStatusException();
            }

            $account_id = $this->intellivoidAccounts->database->real_escape_string($account_id);
            $ip_address = $this->intellivoidAccounts->database->real_escape_string($ip_address);
            $login_status = (int)$status;
            $origin = $this->intellivoidAccounts->database->real_escape_string($origin);
            $time = (int)time();
            $public_id = Hashing::loginPublicID($account_id, $time, $login_status, $origin, $ip_address);
            $public_id = $this->intellivoidAccounts->database->real_escape_string($public_id);

            $Query = "INSERT INTO `login_records` (public_id, account_id, ip_address, origin, time, status) VALUES ('$public_id', $account_id, '$ip_address', '$origin', $time, $status)";
            $QueryResults = $this->intellivoidAccounts->database->query($Query);

            if($QueryResults == true)
            {
                return true;
            }
            else
            {
                throw new DatabaseException($Query, $this->intellivoidAccounts->database->error);
            }

        }
    }