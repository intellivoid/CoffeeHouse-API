<?php

    use ModularAPI\Exceptions\UnsupportedClientException;
    use ModularAPI\HTTP\Request;
    use ModularAPI\Objects\Configuration;

    /**
     * @return Configuration
     * @throws UnsupportedClientException
     */
    function verifyRequest(): Configuration
    {
        $Query = null;

        try
        {
            $Query = Request::parseQuery();
        }
        catch(Exception $exception)
        {
            invalidModuleError();
        }

        if(file_exists(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'configuration.json') == false)
        {
            invalidConfigurationError();
        }

        try
        {
            $APIConfiguration = json_decode(file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'configuration.json'), true);
        }
        catch(Exception $exception)
        {
            invalidConfigurationError();
        }

        // Determine if the version is available
        if(isset($APIConfiguration[strtolower($Query->Version)]) == false)
        {
            invalidVersionError();
        }

        /** @var array $APIConfiguration */
        return Configuration::fromArray($APIConfiguration[strtolower($Query->Version)], $Query->Version);
    }

    /**
     * @return mixed
     */
    function getClientIP()
    {
        if (!empty($_SERVER['HTTP_CLIENT_IP']))
        {
            $ip=$_SERVER['HTTP_CLIENT_IP'];
        }
        elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR']))
        {
            $ip=$_SERVER['HTTP_X_FORWARDED_FOR'];
        }
        else
        {
            $ip=$_SERVER['REMOTE_ADDR'];
        }
        return $ip;
    }