<?php

    namespace IntellivoidAccounts\Abstracts\SearchMethods;

    /**
     * Class AccountSearchMethod
     * @package IntellivoidAccounts\Abstracts\SearchMethods
     */
    abstract class AccountSearchMethod
    {
        const byId = 'id';

        const byPublicID = 'public_id';

        const byUsername = 'username';

        const byEmail = 'email';
    }