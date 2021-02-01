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
     * Class ResourceNotFound
     * @package Handler\GenericResponses
     */
    class ResourceNotFound
    {
        /**
         * Returns a generic error response for a missing resource
         */
        public static function executeResponse()
        {
            $ResponsePayload = array(
                'success' => false,
                'response_code' => 404,
                'error' => array(
                    'error_code' => 0,
                    'type' => "SERVER",
                    "message" => "The requested resource/action is invalid or not found"
                )
            );
            $ResponseBody = json_encode($ResponsePayload);

            http_response_code(404);
            header('Content-Type: application/json');
            header('Content-Size: ' . strlen($ResponseBody));
            print($ResponseBody);
        }
    }