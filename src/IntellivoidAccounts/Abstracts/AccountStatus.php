<?php

    namespace IntellivoidAccounts\Abstracts;

    /**
     * Class AccountStatus
     * @package IntellivoidAccounts\Abstracts
     */
    abstract class AccountStatus
    {
        const Active = 0;

        const Suspended = 1;

        const Limited = 2;

        const VerificationRequired = 3;
    }