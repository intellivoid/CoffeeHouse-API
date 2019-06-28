<?php

    namespace IntellivoidAccounts;

    use acm\acm;
    use Exception;
    use IntellivoidAccounts\Exceptions\ConfigurationNotFoundException;
    use IntellivoidAccounts\Managers\AccountManager;
    use IntellivoidAccounts\Managers\LoginRecordManager;
    use IntellivoidAccounts\Managers\TransactionRecordManager;
    use mysqli;

    include_once(__DIR__ . DIRECTORY_SEPARATOR . 'Abstracts' . DIRECTORY_SEPARATOR . 'SearchMethods' . DIRECTORY_SEPARATOR . 'AccountSearchMethod.php');
    include_once(__DIR__ . DIRECTORY_SEPARATOR . 'Abstracts' . DIRECTORY_SEPARATOR . 'SearchMethods' . DIRECTORY_SEPARATOR . 'TransactionRecordSearchMethod.php');
    include_once(__DIR__ . DIRECTORY_SEPARATOR . 'Abstracts' . DIRECTORY_SEPARATOR . 'AccountStatus.php');
    include_once(__DIR__ . DIRECTORY_SEPARATOR . 'Abstracts' . DIRECTORY_SEPARATOR . 'ExceptionCodes.php');
    include_once(__DIR__ . DIRECTORY_SEPARATOR . 'Abstracts' . DIRECTORY_SEPARATOR . 'LoginStatus.php');
    include_once(__DIR__ . DIRECTORY_SEPARATOR . 'Abstracts' . DIRECTORY_SEPARATOR . 'OpenBluPlan.php');
    include_once(__DIR__ . DIRECTORY_SEPARATOR . 'Abstracts' . DIRECTORY_SEPARATOR . 'OperatorType.php');
    include_once(__DIR__ . DIRECTORY_SEPARATOR . 'Abstracts' . DIRECTORY_SEPARATOR . 'TransactionType.php');

    include_once(__DIR__ . DIRECTORY_SEPARATOR . 'Exceptions' . DIRECTORY_SEPARATOR . 'AccountLimitedException.php');
    include_once(__DIR__ . DIRECTORY_SEPARATOR . 'Exceptions' . DIRECTORY_SEPARATOR . 'AccountNotFoundException.php');
    include_once(__DIR__ . DIRECTORY_SEPARATOR . 'Exceptions' . DIRECTORY_SEPARATOR . 'AccountSuspendedException.php');
    include_once(__DIR__ . DIRECTORY_SEPARATOR . 'Exceptions' . DIRECTORY_SEPARATOR . 'ConfigurationNotFoundException.php');
    include_once(__DIR__ . DIRECTORY_SEPARATOR . 'Exceptions' . DIRECTORY_SEPARATOR . 'DatabaseException.php');
    include_once(__DIR__ . DIRECTORY_SEPARATOR . 'Exceptions' . DIRECTORY_SEPARATOR . 'EmailAlreadyExistsException.php');
    include_once(__DIR__ . DIRECTORY_SEPARATOR . 'Exceptions' . DIRECTORY_SEPARATOR . 'IncorrectLoginDetailsException.php');
    include_once(__DIR__ . DIRECTORY_SEPARATOR . 'Exceptions' . DIRECTORY_SEPARATOR . 'InsufficientFundsException.php');
    include_once(__DIR__ . DIRECTORY_SEPARATOR . 'Exceptions' . DIRECTORY_SEPARATOR . 'InvalidAccountStatusException.php');
    include_once(__DIR__ . DIRECTORY_SEPARATOR . 'Exceptions' . DIRECTORY_SEPARATOR . 'InvalidEmailException.php');
    include_once(__DIR__ . DIRECTORY_SEPARATOR . 'Exceptions' . DIRECTORY_SEPARATOR . 'InvalidIpException.php');
    include_once(__DIR__ . DIRECTORY_SEPARATOR . 'Exceptions' . DIRECTORY_SEPARATOR . 'InvalidLoginStatusException.php');
    include_once(__DIR__ . DIRECTORY_SEPARATOR . 'Exceptions' . DIRECTORY_SEPARATOR . 'InvalidPasswordException.php');
    include_once(__DIR__ . DIRECTORY_SEPARATOR . 'Exceptions' . DIRECTORY_SEPARATOR . 'InvalidSearchMethodException.php');
    include_once(__DIR__ . DIRECTORY_SEPARATOR . 'Exceptions' . DIRECTORY_SEPARATOR . 'InvalidTransactionTypeException.php');
    include_once(__DIR__ . DIRECTORY_SEPARATOR . 'Exceptions' . DIRECTORY_SEPARATOR . 'InvalidUsernameException.php');
    include_once(__DIR__ . DIRECTORY_SEPARATOR . 'Exceptions' . DIRECTORY_SEPARATOR . 'InvalidVendorException.php');
    include_once(__DIR__ . DIRECTORY_SEPARATOR . 'Exceptions' . DIRECTORY_SEPARATOR . 'UsernameAlreadyExistsException.php');

    include_once(__DIR__ . DIRECTORY_SEPARATOR . 'Managers' . DIRECTORY_SEPARATOR . 'AccountManager.php');
    include_once(__DIR__ . DIRECTORY_SEPARATOR . 'Managers' . DIRECTORY_SEPARATOR . 'LoginRecordManager.php');
    include_once(__DIR__ . DIRECTORY_SEPARATOR . 'Managers' . DIRECTORY_SEPARATOR . 'TransactionRecordManager.php');

    include_once(__DIR__ . DIRECTORY_SEPARATOR . 'Objects' . DIRECTORY_SEPARATOR . 'Account' . DIRECTORY_SEPARATOR . 'Configuration' . DIRECTORY_SEPARATOR . 'OpenBlu.php');
    include_once(__DIR__ . DIRECTORY_SEPARATOR . 'Objects' . DIRECTORY_SEPARATOR . 'Account' . DIRECTORY_SEPARATOR . 'PersonalInformation' . DIRECTORY_SEPARATOR . 'BirthDate.php');
    include_once(__DIR__ . DIRECTORY_SEPARATOR . 'Objects' . DIRECTORY_SEPARATOR . 'Account' . DIRECTORY_SEPARATOR . 'Configuration.php');
    include_once(__DIR__ . DIRECTORY_SEPARATOR . 'Objects' . DIRECTORY_SEPARATOR . 'Account' . DIRECTORY_SEPARATOR . 'PersonalInformation.php');
    include_once(__DIR__ . DIRECTORY_SEPARATOR . 'Objects' . DIRECTORY_SEPARATOR . 'Account.php');
    include_once(__DIR__ . DIRECTORY_SEPARATOR . 'Objects' . DIRECTORY_SEPARATOR . 'ApplicationConfiguration.php');
    include_once(__DIR__ . DIRECTORY_SEPARATOR . 'Objects' . DIRECTORY_SEPARATOR . 'LoginRecord.php');
    include_once(__DIR__ . DIRECTORY_SEPARATOR . 'Objects' . DIRECTORY_SEPARATOR . 'TransactionRecord.php');

    include_once(__DIR__ . DIRECTORY_SEPARATOR . 'Utilities' . DIRECTORY_SEPARATOR . 'Hashing.php');
    include_once(__DIR__ . DIRECTORY_SEPARATOR . 'Utilities' . DIRECTORY_SEPARATOR . 'Validate.php');

    if(class_exists('ZiProto\ZiProto') == false)
    {
        include_once(__DIR__ . DIRECTORY_SEPARATOR . 'ZiProto' . DIRECTORY_SEPARATOR . 'ZiProto.php');
    }

    if(class_exists('BasicCalculator\BC') == false)
    {
        include_once(__DIR__ . DIRECTORY_SEPARATOR . 'BasicCalculator' . DIRECTORY_SEPARATOR . 'BC.php');
    }

    if(class_exists('acm\acm') == false)
    {
        include_once(__DIR__ . DIRECTORY_SEPARATOR . 'acm' . DIRECTORY_SEPARATOR . 'acm.php');
    }

    include(__DIR__ . DIRECTORY_SEPARATOR . 'AutoConfig.php');

    /**
     * Class IntellivoidAccounts
     * @package IntellivoidAccounts
     */
    class IntellivoidAccounts
    {

        /**
         * @var mysqli
         */
        public $database;

        /**
         * @var LoginRecordManager
         */
        private $LoginRecordManager;

        /**
         * @var AccountManager
         */
        private $AccountManager;

        /**
         * @var TransactionRecordManager
         */
        private $TransactionRecordManager;

        /**
         * @var acm
         */
        private $acm;

        /**
         * @var mixed
         */
        private $DatabaseConfiguration;

        /**
         * IntellivoidAccounts constructor.
         * @throws ConfigurationNotFoundException
         * @throws Exception
         */
        public function __construct()
        {
            $this->acm = new acm(__DIR__, 'Intellivoid Accounts');
            $this->DatabaseConfiguration = $this->acm->getConfiguration('Database');

            $this->database = new mysqli(
                $this->DatabaseConfiguration['Host'],
                $this->DatabaseConfiguration['Username'],
                $this->DatabaseConfiguration['Password'],
                $this->DatabaseConfiguration['Name'],
                $this->DatabaseConfiguration['Port']
            );

            $this->AccountManager = new AccountManager($this);
            $this->LoginRecordManager = new LoginRecordManager($this);
            $this->TransactionRecordManager = new TransactionRecordManager($this);
        }

        /**
         * @return LoginRecordManager
         */
        public function getLoginRecordManager(): LoginRecordManager
        {
            return $this->LoginRecordManager;
        }

        /**
         * @return AccountManager
         */
        public function getAccountManager(): AccountManager
        {
            return $this->AccountManager;
        }

        /**
         * @return TransactionRecordManager
         */
        public function getTransactionRecordManager(): TransactionRecordManager
        {
            return $this->TransactionRecordManager;
        }

    }