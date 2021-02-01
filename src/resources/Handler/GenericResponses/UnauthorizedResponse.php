<?php
    /*
     * Copyright (c) 2017-2021. Intellivoid Technologies
     *
     * All rights reserved, this is a closed-source solution written by Zi Xing Narrakas,
     *  under no circumstances is any entity with access to this file should redistribute
     *  without written permission from Intellivoid and or the original Author.
     */

namespace Handler\GenericResponses;

    /**
     * Class UnauthorizedResponse
     * @package Handler\GenericResponses
     */
    class UnauthorizedResponse
    {
        /**
         * Returns the generic 401 Unauthorized error to the client
         */
        public static function executeResponse()
        {
            $ResponsePayload = array(
                'success' => false,
                'response_code' => 401,
                'error' => array(
                    'error_code' => 0,
                    'type' => "CLIENT",
                    "message" => "Unauthorized Access, Authentication is required"
                )
            );
            $ResponseBody = json_encode($ResponsePayload);

            header('HTTP/1.0 401 Unauthorized');
            header('WWW-Authenticate: Basic realm="API Authentication"');
            header('Content-Type: application/json');
            header('Content-Size: ' . strlen($ResponseBody));
            print($ResponseBody);
        }
    }