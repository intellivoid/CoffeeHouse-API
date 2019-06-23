<?php
    /** @noinspection PhpUnusedParameterInspection */

    use CoffeeHouse\Bots\Cleverbot;
    use CoffeeHouse\CoffeeHouse;
    use ModularAPI\Abstracts\HTTP\ContentType;
    use ModularAPI\Abstracts\HTTP\FileType;
    use ModularAPI\Abstracts\HTTP\ResponseCode\ClientError;
    use ModularAPI\Abstracts\HTTP\ResponseCode\Successful;
    use ModularAPI\Objects\AccessKey;
    use ModularAPI\Objects\Response;

    /**
     * @param AccessKey $accessKey
     * @param array $Parameters
     * @return Response
     * @throws Exception
     */
    function Module(?AccessKey $accessKey, array $Parameters): Response
    {
        switch($Parameters['client_key'])
        {
            case 'SuperSecret123': break;
            default:
                $Response = new Response();
                $Response->ResponseCode = ClientError::_401;
                $Response->ResponseType = ContentType::application . '/' . FileType::json;
                $Response->Content = array(
                    'status' => false,
                    'code' => ClientError::_401,
                    'message' => 'Invalid Client Key'
                );
                return $Response;
        }

        try
        {
            $CoffeeHouse = new CoffeeHouse();
            $CleverBot = new Cleverbot($CoffeeHouse);
            $CleverBot->newSession('en');
        }
        catch(\CoffeeHouse\Exceptions\BotSessionException $botSessionException)
        {
            $Response = new Response();
            $Response->ResponseCode = ClientError::_404;
            $Response->ResponseType = ContentType::application . '/' . FileType::json;
            $Response->Content = array(
                'status' => false,
                'code' => ClientError::_404,
                'message' => 'Session cannot be created, service unavailable'
            );
            return $Response;
        }

        $Response = new Response();
        $Response->ResponseCode = Successful::_200;
        $Response->ResponseType = ContentType::application . '/' . FileType::json;
        $Response->Content = array(
            'status' => true,
            'code' => Successful::_200,
            'payload' => array(
                'session_id' => $CleverBot->getSession()->SessionID,
                'expires' => $CleverBot->getSession()->Expires
            )
        );
        return $Response;
    }