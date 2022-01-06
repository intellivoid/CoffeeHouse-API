<?php

    namespace Methods\Classes;

    class Utilities
    {
        /**
         * @return string[]
         */
        public static function getSupportedLanguages(): array
        {
            return [
                "en", // English
                "zh", // Chinese
                "de", // German
                "fr", // French
                "pl", // Polish
                "hi", // Hindi
                "hr", // Croatian
                "es", // Spanish
                "ru", // Russian
                "it" // Italian
            ];
        }
    }