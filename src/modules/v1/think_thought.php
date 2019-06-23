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

        $CoffeeHouse = new CoffeeHouse();
        $CleverBot = new Cleverbot($CoffeeHouse);

        try
        {
            $CleverBot->loadSession($Parameters['session_id']);
        }
        catch(\CoffeeHouse\Exceptions\ForeignSessionNotFoundException $foreignSessionNotFoundException)
        {
            $Response = new Response();
            $Response->ResponseCode = ClientError::_404;
            $Response->ResponseType = ContentType::application . '/' . FileType::json;
            $Response->Content = array(
                'status' => false,
                'code' => ClientError::_404,
                'message' => 'Session not found'
            );
            return $Response;
        }


        if(time() > $CleverBot->getSession()->Expires)
        {
            $Response = new Response();
            $Response->ResponseCode = ClientError::_400;
            $Response->ResponseType = ContentType::application . '/' . FileType::json;
            $Response->Content = array(
                'status' => false,
                'code' => ClientError::_400,
                'message' => 'Session Expired'
            );
            return $Response;
        }

        if($CleverBot->getSession()->Available == false)
        {
            $Response = new Response();
            $Response->ResponseCode = ClientError::_400;
            $Response->ResponseType = ContentType::application . '/' . FileType::json;
            $Response->Content = array(
                'status' => false,
                'code' => ClientError::_400,
                'message' => 'Session Not Available'
            );
            return $Response;
        }

        try
        {
            $BotResponse = $CleverBot->think($Parameters['input']);
        }
        catch(\CoffeeHouse\Exceptions\BotSessionException $botSessionException)
        {
            $Session = $CleverBot->getSession();
            $Session->Available = false;
            $CoffeeHouse->getForeignSessionsManager()->updateSession($Session);

            $Response = new Response();
            $Response->ResponseCode = ClientError::_400;
            $Response->ResponseType = ContentType::application . '/' . FileType::json;
            $Response->Content = array(
                'status' => false,
                'code' => ClientError::_400,
                'message' => 'Session Not Available'
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
                'expires' => $CleverBot->getSession()->Expires,
                'output' => $BotResponse
            )
        );

        return $Response;
    }