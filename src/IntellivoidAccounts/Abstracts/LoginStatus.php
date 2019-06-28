<?php

    namespace IntellivoidAccounts\Abstracts;

    /**
     * Class LoginStatus
     * @package IntellivoidAccounts\Abstracts
     */
    abstract class LoginStatus
    {
        const Successful = 0;

        const IncorrectCredentials = 1;

        const IncorrectVerificationCode = 2;
    }