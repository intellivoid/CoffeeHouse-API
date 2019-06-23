<?php

    use ModularAPI\Abstracts\HTTP\ResponseCode\ClientError;
    use ModularAPI\Abstracts\HTTP\ResponseCode\ServerError;
    use ModularAPI\Exceptions\UnsupportedClientException;
    use ModularAPI\HTTP\Response;

    /**
     * @throws UnsupportedClientException
     */
    function invalidResourceError()
    {
        $Payload = array(
            'status' => false,
            'code' => ClientError::_404,
            'message' => 'The requested resource is invalid/unavailable'
        );
        Response::json($Payload, ClientError::_404);
        exit();
    }

    /**
     * @throws UnsupportedClientException
     */
    function invalidModuleError()
    {
        $Payload = array(
            'status' => false,
            'code' => ClientError::_404,
            'message' => 'The requested resource/module is invalid or unavailable'
        );
        Response::json($Payload, ClientError::_404);
        exit();
    }

    /**
     * @throws UnsupportedClientException
     */
    function invalidConfigurationError()
    {
        $Payload = array(
            'status' => false,
            'code' => ServerError::_500,
            'message' => 'The sever does not have a valid configuration file, if you are the administrator then please consult the documentation'
        );
        Response::json($Payload, ServerError::_500);
        exit();
    }

    /**
     * @throws UnsupportedClientException
     */
    function invalidVersionError()
    {
        $Payload = array(
            'status' => false,
            'code' => ClientError::_400,
            'message' => 'The API Version is not supported by the server'
        );
        Response::json($Payload, ClientError::_400);
        exit();
    }

    /**
     * @throws UnsupportedClientException
     */
    function authenticationRequiredError()
    {
        $Payload = array(
            'status' => false,
            'code' => ClientError::_401,
            'message' => 'Authentication is required'
        );
        Response::json($Payload, ClientError::_401);
        exit();
    }

    /**
     * @throws UnsupportedClientException
     */
    function certificateRequiredError()
    {
        $Payload = array(
            'status' => false,
            'code' => ClientError::_401,
            'message' => 'A Certificate is required for authentication'
        );
        Response::json($Payload, ClientError::_401);
        exit();
    }

    /**
     * @throws UnsupportedClientException
     */
    function invalidAuthenticationError()
    {
        $Payload = array(
            'status' => false,
            'code' => ClientError::_401,
            'message' => 'Incorrect Authentication'
        );
        Response::json($Payload, ClientError::_401);
        exit();
    }

    /**
     * @throws UnsupportedClientException
     */
    function invalidPermissionError()
    {
        $Payload = array(
            'status' => false,
            'code' => ClientError::_403,
            'message' => 'You don\'t have the required permissions to use this API Module'
        );
        Response::json($Payload, ClientError::_403);
        exit();
    }

    /**
     * @param string $Message
     * @throws UnsupportedClientException
     */
    function unavailableError(string $Message)
    {
        $Payload = array(
            'status' => false,
            'code' => ServerError::_503,
            'message' => $Message
        );
        Response::json($Payload, ServerError::_503);
        exit();
    }

    /**
     * @throws UnsupportedClientException
     */
    function keyExpiredError()
    {
        $Payload = array(
            'status' => false,
            'code' => ClientError::_403,
            'message' => 'The Key/Certificate has expired'
        );
        Response::json($Payload, ClientError::_403);
        exit();
    }

    /**
     * @throws UnsupportedClientException
     */
    function usageExceededError()
    {
        $Payload = array(
            'status' => false,
            'code' => ClientError::_429,
            'message' => 'Usage limit has exceeded'
        );
        Response::json($Payload, ClientError::_429);
        exit();
    }

    /**
     * @throws UnsupportedClientException
     */
    function moduleScriptNotFoundError()
    {
        $Payload = array(
            'status' => false,
            'code' => ServerError::_500,
            'message' => 'The module was not found on the server'
        );
        Response::json($Payload, ServerError::_500);
        exit();
    }

    /**
     * @param string $parameter_name
     * @throws UnsupportedClientException
     */
    function missingParameter(string $parameter_name)
    {
        $Payload = array(
            'status' => false,
            'code' => ClientError::_400,
            'message' => 'The parameter "' . $parameter_name . '" is missing'
        );
        Response::json($Payload, ClientError::_400);
        exit();
    }

    /**
     * @param Exception $exception
     * @throws UnsupportedClientException
     */
    function internalServerError(Exception $exception)
    {
        $Payload = array(
            'status' => false,
            'code' => ServerError::_500,
            'message' => 'Internal Server Error',
            'exception_code' => $exception->getCode()
        );
        Response::json($Payload, ServerError::_500);
        exit();
    }

    /**
     * @param string $requestMethod
     * @throws UnsupportedClientException
     */
    function requestMethodNotAllowed(string $requestMethod)
    {
        $Payload = array(
            'status' => false,
            'code' => ClientError::_405,
            'message' => 'The request method "' . $requestMethod . '" is not allowed'
        );
        Response::json($Payload, ClientError::_405);
        exit();
    }

    /**
     * @throws UnsupportedClientException
     */
    function accessKeySuspended()
    {
        $Payload = array(
            'status' => false,
            'code' => ClientError::_403,
            'message' => 'Your access key has been suspended'
        );
        Response::json($Payload, ClientError::_403);
        exit();
    }