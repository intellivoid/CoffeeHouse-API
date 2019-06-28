<?php
    /** @noinspection PhpUnusedParameterInspection */

    use CoffeeHouse\Bots\Cleverbot;
    use CoffeeHouse\CoffeeHouse;
    use CoffeeHouse\Exceptions\BotSessionException;
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
            case 'KIK_PROJECT_SYNICAL_AI-CODE(F43FN384DM92D3M2)': break;
            case 'LORDE_DISCORD_HACKWEEK-2019-API-RELEASE': break;
            case 'RSA-2048:0x02f,0x12,0x0F,UUID:76cc8a94-995f-11e9-a2a3-2a2ae2dbcce4': break;
	          case 'RSA-2048:0x02f,0x12,0x0F,UUID:eda3e6fc-d23d-496b-b8b3-9b3425b963c0': break;
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
        catch(BotSessionException $botSessionException)
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
