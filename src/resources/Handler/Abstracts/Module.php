<?php


    namespace Handler\Abstracts;

    use Handler\Interfaces\Response;
    use IntellivoidAPI\Objects\AccessRecord;

    /**
     * Class Module
     * @package Handler\Abstracts
     */
    abstract class Module implements Response
    {
        /**
         * The name of this module
         *
         * @var string
         */
        public string $name;

        /**
         * The description of the module
         *
         * @var string
         */
        public string $description;

        /**
         * The version of this module
         *
         * @var string
         */
        public string $version;

        /**
         * Optional access record object, null =  not set
         *
         * @var AccessRecord
         */
        public AccessRecord $access_record;
    }