<?php
    /*
     * Copyright (c) 2017-2021. Intellivoid Technologies
     *
     * All rights reserved, this is a closed-source solution written by Zi Xing Narrakas,
     *  under no circumstances is any entity with access to this file should redistribute
     *  without written permission from Intellivoid and or the original Author.
     */

namespace Handler\Interfaces;

    /**
     * Response interface
     *
     * Interface Response
     * @package Handler\Interfaces
     */
    interface Response
    {
        /**
         * Returns the content type which is used for the header
         *
         * @return string|null
         */
        public function getContentType(): ?string;

        /**
         * Returns the content length
         *
         * @return int|null
         */
        public function getContentLength(): ?int;

        /**
         * Returns the body content
         *
         * @return string|null
         */
        public function getBodyContent(): ?string;

        /**
         * Returns the HTTP response code
         *
         * @return int|null
         */
        public function getResponseCode(): ?int;

        /**
         * Indicates if the response is a file download
         *
         * @return bool|null
         */
        public function isFile(): ?bool;

        /**
         * Returns the file name if the response is a file download
         *
         * @return string|null
         */
        public function getFileName(): ?string;

        /**
         * Main execution point, it processes the request before it determines the values for this request
         *
         * @return mixed
         */
        public function processRequest();
    }