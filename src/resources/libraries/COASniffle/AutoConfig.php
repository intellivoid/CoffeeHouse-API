<?php

    use acm\acm;
    use acm\Objects\Schema;

    /**
     * ACM AutoConfig file for COA Sniffle
     */

    if(class_exists('acm\acm') == false)
    {
        include_once(__DIR__ . DIRECTORY_SEPARATOR . 'acm' . DIRECTORY_SEPARATOR . 'acm.php');
    }

    $acm = new acm(__DIR__, 'COASniffle');

    // Database Schema Configuration
    $EndpointSchema = new Schema();
    $EndpointSchema->setDefinition('LocalDevelopment', 'False');
    $EndpointSchema->setDefinition('EnableSSL', 'True');
    $EndpointSchema->setDefinition('LocalEndpoint', 'http://localhost');
    $EndpointSchema->setDefinition('ProductionEndpoint', 'https://accounts.intellivoid.info');
    $acm->defineSchema('Endpoint', $EndpointSchema);

    // If auto-loaded via CLI, Process any arguments passed to the main execution point
    $acm->processCommandLine();