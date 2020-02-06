<?php


    namespace COASniffle\Abstracts;

    /**
     * Class ApplicationType
     * @package COASniffle\Abstracts
     */
    abstract class ApplicationType
    {
        /**
         * Redirects the user to a placeholder after authentication
         */
        const Redirect = "REDIRECT";

        /**
         * The user will see a generic placeholder until the Application is done
         * processing the authentication
         */
        const ApplicationPlaceholder = "APPLICATION_PLACEHOLDER";

        /**
         * The user will need to copy an access token and paste into the Application
         */
        const Code = "CODE";
    }