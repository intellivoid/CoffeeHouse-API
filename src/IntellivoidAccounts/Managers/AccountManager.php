<?php

    namespace IntellivoidAccounts\Managers;

    use IntellivoidAccounts\Abstracts\AccountStatus;
    use IntellivoidAccounts\Abstracts\SearchMethods\AccountSearchMethod;
    use IntellivoidAccounts\Exceptions\AccountNotFoundException;
    use IntellivoidAccounts\Exceptions\AccountSuspendedException;
    use IntellivoidAccounts\Exceptions\DatabaseException;
    use IntellivoidAccounts\Exceptions\EmailAlreadyExistsException;
    use IntellivoidAccounts\Exceptions\IncorrectLoginDetailsException;
    use IntellivoidAccounts\Exceptions\InvalidAccountStatusException;
    use IntellivoidAccounts\Exceptions\InvalidEmailException;
    use IntellivoidAccounts\Exceptions\InvalidPasswordException;
    use IntellivoidAccounts\Exceptions\InvalidSearchMethodException;
    use IntellivoidAccounts\Exceptions\InvalidUsernameException;
    use IntellivoidAccounts\Exceptions\UsernameAlreadyExistsException;
    use IntellivoidAccounts\IntellivoidAccounts;
    use IntellivoidAccounts\Objects\Account;
    use IntellivoidAccounts\Utilities\Hashing;
    use IntellivoidAccounts\Utilities\Validate;
    use ZiProto\ZiProto;

    /**
     * Class AccountManager
     * @package IntellivoidAccounts\Managers
     */
    class AccountManager
    {
        /**
         * @var IntellivoidAccounts
         */
        private $intellivoidAccounts;

        /**
         * AccountManager constructor.
         * @param IntellivoidAccounts $intellivoidAccounts
         */
        public function __construct(IntellivoidAccounts $intellivoidAccounts)
        {
            $this->intellivoidAccounts = $intellivoidAccounts;
        }

        /**
         * Registers a new Account into the Database
         *
         * @param string $username
         * @param string $email
         * @param string $password
         * @return Account
         * @throws AccountNotFoundException
         * @throws DatabaseException
         * @throws EmailAlreadyExistsException
         * @throws InvalidEmailException
         * @throws InvalidPasswordException
         * @throws InvalidSearchMethodException
         * @throws InvalidUsernameException
         * @throws UsernameAlreadyExistsException
         */
        public function registerAccount(string $username, string $email, string $password): Account
        {
            if(Validate::username($username) == false)
            {
                throw new InvalidUsernameException();
            }

            if(Validate::email($email) == false)
            {
                throw new InvalidEmailException();
            }

            if(Validate::password($password) == false)
            {
                throw new InvalidPasswordException();
            }

            if($this->usernameExists($username) == true)
            {
                throw new UsernameAlreadyExistsException();
            }

            if($this->emailExists($email) == true)
            {
                throw new EmailAlreadyExistsException();
            }

            $public_id = Hashing::publicID($username, $password, $email);
            $username = $this->intellivoidAccounts->database->real_escape_string($username);
            $email = $this->intellivoidAccounts->database->real_escape_string($email);
            $password = $this->intellivoidAccounts->database->real_escape_string(Hashing::password($password));
            $status = (int)AccountStatus::Active;
            $personal_information = new Account\PersonalInformation();
            $personal_information = $this->intellivoidAccounts->database->real_escape_string(ZiProto::encode($personal_information->toArray()));
            $configuration = new Account\Configuration();
            $configuration = $this->intellivoidAccounts->database->real_escape_string(ZiProto::encode($configuration->toArray()));
            $last_login_id = (int)0;
            $creation_date = (int)time();

            $Query = "INSERT INTO `users` (public_id, username, email, password, status, personal_information, configuration, last_login_id, creation_date) VALUES ('$public_id', '$username', '$email', '$password', $status, '$personal_information', '$configuration', $last_login_id, $creation_date)";
            $QueryResults = $this->intellivoidAccounts->database->query($Query);

            if($QueryResults == true)
            {
                return $this->getAccount(AccountSearchMethod::byPublicID, $public_id);
            }
            else
            {
                throw new DatabaseException($Query, $this->intellivoidAccounts->database->error);
            }
        }

        /**
         * Returns an existing Account from the Database
         *
         * @param string $search_method
         * @param string $input
         * @return Account
         * @throws AccountNotFoundException
         * @throws DatabaseException
         * @throws InvalidSearchMethodException
         */
        public function getAccount(string $search_method, string $input): Account
        {
            switch($search_method)
            {
                case AccountSearchMethod::byId:
                    $input = (int)$input;
                    $search_method = $this->intellivoidAccounts->database->real_escape_string($search_method);
                    break;

                case AccountSearchMethod::byPublicID:
                    $input = $this->intellivoidAccounts->database->real_escape_string($input);
                    $search_method = $this->intellivoidAccounts->database->real_escape_string($search_method);
                    break;

                case AccountSearchMethod::byUsername:
                    $input = $this->intellivoidAccounts->database->real_escape_string($input);
                    $search_method = $this->intellivoidAccounts->database->real_escape_string($search_method);
                    break;

                case AccountSearchMethod::byEmail:
                    $input = $this->intellivoidAccounts->database->real_escape_string($input);
                    $search_method = $this->intellivoidAccounts->database->real_escape_string($search_method);
                    break;

                default:
                    throw new InvalidSearchMethodException();
            }

            $query = "SELECT id, public_id, username, email, password, status, personal_information, configuration, last_login_id, creation_date FROM `users` WHERE $search_method='$input'";
            $query_results = $this->intellivoidAccounts->database->query($query);

            if($query_results == false)
            {
                throw new DatabaseException($query, $this->intellivoidAccounts->database->error);
            }
            else
            {
                if($query_results->num_rows !== 1)
                {
                    throw new AccountNotFoundException();
                }

                $Row = $query_results->fetch_array(MYSQLI_ASSOC);
                $Row['personal_information'] = ZiProto::decode($Row['personal_information']);
                $Row['configuration'] = ZiProto::decode($Row['configuration']);
                return Account::fromArray($Row);
            }
        }

        /**
         * Updates an existing account in teh database
         *
         * @param Account $account
         * @return bool
         * @throws AccountNotFoundException
         * @throws DatabaseException
         * @throws InvalidEmailException
         * @throws InvalidPasswordException
         * @throws InvalidSearchMethodException
         * @throws InvalidUsernameException
         * @throws InvalidAccountStatusException
         */
        public function updateAccount(Account $account): bool
        {
            if($this->IdExists($account->ID) == false)
            {
                throw new AccountNotFoundException();
            }

            if(Validate::email($account->Email) == false)
            {
                throw new InvalidEmailException();
            }

            if(Validate::username($account->Username) == false)
            {
                throw new InvalidUsernameException();
            }

            switch($account->Status)
            {
                case AccountStatus::Active: break;
                case AccountStatus::Suspended: break;
                case AccountStatus::Limited: break;
                case AccountStatus::VerificationRequired: break;
                default: throw new InvalidAccountStatusException();
            }

            $ID = (int)$account->ID;
            $PublicID = $this->intellivoidAccounts->database->real_escape_string($account->PublicID);
            $Username = $this->intellivoidAccounts->database->real_escape_string($account->Username);
            $Email = $this->intellivoidAccounts->database->real_escape_string($account->Email);
            $Status = (int)$account->Status;
            $PersonalInformation = $this->intellivoidAccounts->database->real_escape_string(
                ZiProto::encode($account->PersonalInformation->toArray())
            );
            $Configuration = $this->intellivoidAccounts->database->real_escape_string(
                ZiProto::encode($account->Configuration->toArray())
            );
            $LastLoginId = (int)$account->LastLoginID;

            $Query = sprintf(
                "UPDATE `users` SET public_id='%s', username='%s', email='%s', status=%s, personal_information='%s', configuration='%s', last_login_id=%s WHERE id=%s",
                $PublicID, $Username, $Email, $Status, $PersonalInformation, $Configuration, $LastLoginId, $ID
            );
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

        /**
         * Checks the login details of the account
         *
         * @param string $username_or_email
         * @param string $password
         * @return bool
         * @throws AccountNotFoundException
         * @throws AccountSuspendedException
         * @throws DatabaseException
         * @throws IncorrectLoginDetailsException
         * @throws InvalidSearchMethodException
         */
        public function checkLogin(string $username_or_email, string $password): bool
        {
            $account_details = null;

            if($this->usernameExists($username_or_email) == true)
            {
                $account_details = $this->getAccount(AccountSearchMethod::byUsername, $username_or_email);
            }
            elseif($this->emailExists($username_or_email) == true)
            {
                $account_details = $this->getAccount(AccountSearchMethod::byEmail, $username_or_email);
            }
            else
            {
                throw new IncorrectLoginDetailsException();
            }

            if($account_details->Status == AccountStatus::Suspended)
            {
                throw new AccountSuspendedException();
            }

            if($account_details->Password !== Hashing::password($password))
            {
                throw new IncorrectLoginDetailsException();
            }

            return true;
        }

        /**
         * Determines if the Email exists on the Database
         *
         * @param string $email
         * @return bool
         * @throws DatabaseException
         * @throws InvalidSearchMethodException
         */
        public function emailExists(string $email): bool
        {
            try
            {
                $this->getAccount(AccountSearchMethod::byEmail, $email);
                return true;
            }
            catch(AccountNotFoundException $accountNotFoundException)
            {
                return false;
            }
        }

        /**
         * Determines if the Username exists on the Database
         *
         * @param string $username
         * @return bool
         * @throws DatabaseException
         * @throws InvalidSearchMethodException
         */
        public function usernameExists(string $username): bool
        {
            try
            {
                $this->getAccount(AccountSearchMethod::byUsername, $username);
                return true;
            }
            catch(AccountNotFoundException $accountNotFoundException)
            {
                return false;
            }
        }

        /**
         * Determines if the Public ID exists on the database
         *
         * @param string $public_id
         * @return bool
         * @throws DatabaseException
         * @throws InvalidSearchMethodException
         */
        public function publicIdExists(string $public_id): bool
        {
            try
            {
                $this->getAccount(AccountSearchMethod::byPublicID, $public_id);
                return true;
            }
            catch(AccountNotFoundException $accountNotFoundException)
            {
                return false;
            }
        }

        /**
         * Determines if the ID exists on the database
         *
         * @param int $id
         * @return bool
         * @throws DatabaseException
         * @throws InvalidSearchMethodException
         */
        public function IdExists(int $id): bool
        {
            try
            {
                $this->getAccount(AccountSearchMethod::byId, $id);
                return true;
            }
            catch(AccountNotFoundException $accountNotFoundException)
            {
                return false;
            }
        }
    }