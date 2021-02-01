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
     * Class ModuleListingResponse
     * @package Handler\GenericResponses
     */
    class ModuleListingResponse
    {
        /**
         * @param array $modules
         */
        public static function executeResponse(array $modules)
        {
            /** @noinspection DuplicatedCode */
            $ResponsePayload = array(
                'success' => true,
                'response_code' => 200,
                'modules' => $modules
            );
            $ResponseBody = json_encode($ResponsePayload);

            http_response_code(200);
            header('Content-Type: application/json');
            header('Content-Size: ' . strlen($ResponseBody));
            print($ResponseBody);
        }
    }