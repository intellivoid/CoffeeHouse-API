<?php
    /** @noinspection PhpUnusedParameterInspection */

    use CoffeeHouse\Bots\Cleverbot;
    use CoffeeHouse\CoffeeHouse;
    use CoffeeHouse\Exceptions\BotSessionException;
    use CoffeeHouse\Exceptions\ForeignSessionNotFoundException;
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
        $PublicReleaseAPIKey = false;
        switch($Parameters['client_key'])
        {
            case 'KIK_PROJECT_SYNICAL_AI-CODE(F43FN384DM92D3M2)': break;
            case 'KIK_PROJECT_SYNICAL_AI-CODE(F43FN384DM92D3M2)': break;
            case 'LORDE_DISCORD_HACKWEEK-2019-API-RELEASE': break;
            case 'RSA-2048:0x02f,0x12,0x0F,UUID:76cc8a94-995f-11e9-a2a3-2a2ae2dbcce4': 
                $PublicReleaseAPIKey = true;
                break;
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

        $CoffeeHouse = new CoffeeHouse();
        $CleverBot = new Cleverbot($CoffeeHouse);
        $TelegramClient = $CoffeeHouse->getTelegramClientManager()->syncClient($Parameters['user_id']);

        if($TelegramClient->ForeignSessionID == 'None')
        {

            try
            {
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

            $TelegramClient->ForeignSessionID = $CleverBot->getSession()->SessionID;
            $CoffeeHouse->getTelegramClientManager()->updateClient($TelegramClient);
        }
        else
        {
            try
            {
                $CleverBot->loadSession($TelegramClient->ForeignSessionID);
            }
            catch(ForeignSessionNotFoundException $foreignSessionNotFoundException)
            {
                $CleverBot->newSession('en');
                $TelegramClient->ForeignSessionID = $CleverBot->getSession()->SessionID;
                $CoffeeHouse->getTelegramClientManager()->updateClient($TelegramClient);
            }

            if(time() > $CleverBot->getSession()->Expires)
            {
                $CleverBot->newSession('en');
                $TelegramClient->ForeignSessionID = $CleverBot->getSession()->SessionID;
                $CoffeeHouse->getTelegramClientManager()->updateClient($TelegramClient);
            }

            if($CleverBot->getSession()->Available == false)
            {
                $CleverBot->newSession('en');
                $TelegramClient->ForeignSessionID = $CleverBot->getSession()->SessionID;
                $CoffeeHouse->getTelegramClientManager()->updateClient($TelegramClient);
            }
        }

        try
        {
            $BotResponse = $CleverBot->think($Parameters['input']);
        }
        catch(BotSessionException $botSessionException)
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
        if($PublicReleaseAPIKey) { 
            $WLList = file_get_contents("https://raw.githubusercontent.com/intellivoid/CFAPIRelease/master/allowed_ips_whitelist");
            if (isset($_SERVER["HTTP_CF_CONNECTING_IP"])) {
                $_SERVER['REMOTE_ADDR'] = $_SERVER["HTTP_CF_CONNECTING_IP"];
            }
            $AuthenList = explode(";",$WLList);
            if(!in_array($_SERVER['REMOTE_ADDR'], $AuthenList)){
              $BotResponse = $BotResponse."\n\nAI services powered by CoffeeHouseAI.\nTo remove this message, contact @AntiEngineer.\nMade with love by @Intellivoid.";
            }
        }
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