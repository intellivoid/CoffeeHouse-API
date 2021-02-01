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
     * Class UnsupportedRequestMethod
     * @package Handler\GenericResponses
     */
    class UnsupportedRequestMethod
    {
        /**
         * Returns a generic response stating the request method is unsupported
         */
        public static function executeResponse()
        {
            $ResponsePayload = array(
                'success' => true,
                'response_code' => 400,
                'error' => array(
                    'error_code' => 0,
                    'type' => "CLIENT",
                    "message" => "The given request method is unsupported"
                )
            );
            $ResponseBody = json_encode($ResponsePayload);

            http_response_code(400);
            header('Content-Type: application/json');
            header('Content-Size: ' . strlen($ResponseBody));
            print($ResponseBody);
        }
    }