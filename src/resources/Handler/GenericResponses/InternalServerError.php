<?php
    /*
     * Copyright (c) 2017-2021. Intellivoid Technologies
     *
     * All rights reserved, this is a closed-source solution written by Zi Xing Narrakas,
     *  under no circumstances is any entity with access to this file should redistribute
     *  without written permission from Intellivoid and or the original Author.
     */

namespace Handler\GenericResponses;


    use Exception;
    use Handler\Handler;

    /**
     * Class InternalServerError
     * @package Handler\GenericResponses
     */
    class InternalServerError
    {
        /**
         * Executes the response for a generic internal server error
         *
         * If 'DEBUG_EXCEPTIONS' is enabled, then details about the exception
         * would be shown.
         *
         * @param Exception $exception
         */
        public static function executeResponse(Exception $exception)
        {
            $error_details = array();

            if(Handler::$MainConfiguration->DebugExceptions)
            {
                $error_details = array(
                    "error_code" => $exception->getCode(),
                    "type" => 'SERVICE',
                    "message" => $exception->getMessage(),
                    "file" => $exception->getFile(),
                    "line" => $exception->getLine(),
                    "code" => $exception->getCode(),
                    "trace" => $exception->getTrace()
                );
            }
            else
            {
                $error_details =  array(
                    "error_code" => -1,
                    "type" => "SERVICE",
                    "message" => "There was an internal server error when trying to process your request"
                );
            }

            $ResponsePayload = array(
                "success" => false,
                "response_code" => 500,
                "error" => $error_details
            );
            $ResponseBody = json_encode($ResponsePayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            http_response_code(500);
            header('Content-Type: application/json');
            print($ResponseBody);
        }
    }