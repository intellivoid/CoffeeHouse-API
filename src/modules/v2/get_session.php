<?php
    /** @noinspection PhpUndefinedClassInspection */
    /** @noinspection PhpUnusedParameterInspection */

    use CoffeeHouse\Abstracts\ForeignSessionSearchMethod;
    use CoffeeHouse\CoffeeHouse;
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
        $CoffeeHouse = new CoffeeHouse();

        if(strlen($Parameters['session_id']) > 500)
        {
            $Parameters['session_id'] = substr($Parameters['session_id'], 0, 500);
        }

        try
        {
            $ForeignSession = $CoffeeHouse->getForeignSessionsManager()->getSession(
                ForeignSessionSearchMethod::bySessionId, $Parameters['session_id']
            );
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

        $Response = new Response();
        $Response->ResponseCode = Successful::_200;
        $Response->ResponseType = ContentType::application . '/' . FileType::json;
        $Response->Content = array(
            'status' => true,
            'code' => Successful::_200,
            'payload' => array(
                'session_id'    => $ForeignSession->SessionID,
                'language'      => $ForeignSession->Language,
                'available'     => $ForeignSession->Available,
                'expires'       => $ForeignSession->Expires
            )
        );
        return $Response;
    }