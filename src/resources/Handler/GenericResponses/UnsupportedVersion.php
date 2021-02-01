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
     * Class UnsupportedVersion
     * @package Handler\GenericResponses
     */
    class UnsupportedVersion
    {
        /**
         * Executes the generic error response for a unsupported version
         */
        public static function executeResponse()
        {
            $ResponsePayload = array(
                'success' => false,
                'response_code' => 400,
                'error' => array(
                    'error_code' => 1,
                    'type' => "SERVER",
                    "message" => "The given version for this API is not supported"
                )
            );
            $ResponseBody = json_encode($ResponsePayload);

            http_response_code(400);
            header('Content-Type: application/json');
            header('Content-Size: ' . strlen($ResponseBody));
            print($ResponseBody);
        }
    }