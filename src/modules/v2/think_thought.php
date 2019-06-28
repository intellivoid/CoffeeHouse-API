<?php
    /** @noinspection PhpUnusedParameterInspection */

    use CoffeeHouse\Bots\Cleverbot;
    use CoffeeHouse\CoffeeHouse;
    use CoffeeHouse\Exceptions\BotSessionException;
    use CoffeeHouse\Exceptions\ForeignSessionNotFoundException;
    use ModularAPI\Abstracts\HTTP\ContentType;
    use ModularAPI\Abstracts\HTTP\FileType;
    use ModularAPI\Abstracts\HTTP\ResponseCode\ClientError;
use ModularAPI\Abstracts\HTTP\ResponseCode\ServerError;
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

        $CoffeeHouse = new CoffeeHouse();
        $CleverBot = new Cleverbot($CoffeeHouse);

        try
        {
            $CleverBot->loadSession($Parameters['session_id']);
        }
        catch(ForeignSessionNotFoundException $foreignSessionNotFoundException)
        {
            $Response = new Response();
            $Response->ResponseCode = ClientError::_404;
            $Response->ResponseType = ContentType::application . '/' . FileType::json;
            $Response->Content = array(
                'status' => false,
                'code' => ClientError::_404,
                'message' => 'The session ID does not exist'
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
                'message' => 'Session expired'
            );
            return $Response;
        }

        if($CleverBot->getSession()->Available == false)
        {
            $Response = new Response();
            $Response->ResponseCode = ServerError::_503;
            $Response->ResponseType = ContentType::application . '/' . FileType::json;
            $Response->Content = array(
                'status' => false,
                'code' => ServerError::_503,
                'message' => 'Session no longer available'
            );
            return $Response;
        }

        if(strlen($Parameters['input']) < 1)
        {
            $Response = new Response();
            $Response->ResponseCode = ClientError::_400;
            $Response->ResponseType = ContentType::application . '/' . FileType::json;
            $Response->Content = array(
                'status' => false,
                'code' => ClientError::_400,
                'message' => 'Input cannot be empty'
            );
            return $Response;
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
            $Response->ResponseCode = ServerError::_503;
            $Response->ResponseType = ContentType::application . '/' . FileType::json;
            $Response->Content = array(
                'status' => false,
                'code' => ServerError::_503,
                'message' => 'Session no longer available'
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
                'output' => $BotResponse
            )
        );

        return $Response;
    }