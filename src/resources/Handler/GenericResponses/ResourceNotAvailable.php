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
     * Class ResourceNotAvailable
     * @package Handler\GenericResponses
     */
    class ResourceNotAvailable
    {
        /**
         * Returns a module not available response
         * @param string $message
         */
        public static function executeResponse(string $message)
        {
            $ResponsePayload = array(
                'success' => false,
                'response_code' => 403,
                'error' => array(
                    'error_code' => 2,
                    'type' => "SERVICE",
                    "message" => $message
                )
            );
            $ResponseBody = json_encode($ResponsePayload);

            http_response_code(403);
            header('Content-Type: application/json');
            header('Content-Size: ' . strlen($ResponseBody));
            print($ResponseBody);
        }
}