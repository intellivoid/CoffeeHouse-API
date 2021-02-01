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
     * Class InvalidUserAgentResponse
     * @package Handler\GenericResponses
     */
    class InvalidUserAgentResponse
    {
        /**
         * Executes the response for when the user-agent header is invalid or missing
         */
        public static function executeResponse()
        {
            $ResponsePayload = array(
                'success' => true,
                'response_code' => 400,
                'error' => array(
                    'error_code' => 0,
                    'type' => "CLIENT",
                    "message" => "The given user-agent header is invalid/missing"
                )
            );
            $ResponseBody = json_encode($ResponsePayload);

            http_response_code(400);
            header('Content-Type: application/json');
            header('Content-Size: ' . strlen($ResponseBody));
            print($ResponseBody);
        }
    }