<?php
    /*
     * Copyright (c) 2017-2021. Intellivoid Technologies
     *
     * All rights reserved, this is a closed-source solution written by Zi Xing Narrakas,
     *  under no circumstances is any entity with access to this file should redistribute
     *  without written permission from Intellivoid and or the original Author.
     */

namespace Handler\GenericResponses;


    use Handler\Handler;

    /**
     * Class Root
     * @package Handler\GenericResponses
     */
    class Root
    {
        /**
         * Executes the generic response for root '/'
         */
        public static function executeResponse()
        {
            $ResponsePayload = array(
                'success' => true,
                'response_code' => 200,
                'payload' => array(
                    'service_name' => Handler::$MainConfiguration->Name,
                    'documentation' => Handler::$MainConfiguration->DocumentationUrl
                )
            );
            $ResponseBody = json_encode($ResponsePayload);

            http_response_code(200);
            header('Content-Type: application/json');
            header('Content-Size: ' . strlen($ResponseBody));
            print($ResponseBody);
        }
    }