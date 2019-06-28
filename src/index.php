<?php
    /** @noinspection PhpUnhandledExceptionInspection */

    /**
     * ModularAPI HTTP Handler File
     *
     * This file determines if the User is making a valid request, and if the authentication method used is valid and correct
     */

    use ModularAPI\Abstracts\AccessKeyStatus;
    use ModularAPI\Abstracts\HTTP\ContentType;
    use ModularAPI\Abstracts\HTTP\FileType;
    use ModularAPI\Abstracts\HTTP\RequestMethod;
    use ModularAPI\Abstracts\HTTP\ResponseCode\ServerError;
    use ModularAPI\Exceptions\AccessKeyExpiredException;
    use ModularAPI\Exceptions\MissingParameterException;
    use ModularAPI\Exceptions\UsageExceededException;
    use ModularAPI\HTTP\Request;
    use ModularAPI\ModularAPI;
    use ModularAPI\Objects\AccessKey;
    use ModularAPI\Objects\ExceptionDetails;
    use ModularAPI\Objects\Response;

    include_once(__DIR__ . DIRECTORY_SEPARATOR . 'ModularAPI' . DIRECTORY_SEPARATOR . 'ModularAPI.php');
    include_once(__DIR__ . DIRECTORY_SEPARATOR . 'CoffeeHouse' . DIRECTORY_SEPARATOR . 'CoffeeHouse.php');
    include_once(__DIR__ . DIRECTORY_SEPARATOR . 'IntellivoidAccounts' . DIRECTORY_SEPARATOR . 'IntellivoidAccounts.php');
    include_once(__DIR__ . DIRECTORY_SEPARATOR . 'api' . DIRECTORY_SEPARATOR . 'authentication.php');
    include_once(__DIR__ . DIRECTORY_SEPARATOR . 'api' . DIRECTORY_SEPARATOR . 'check_plan.php');
    include_once(__DIR__ . DIRECTORY_SEPARATOR . 'api' . DIRECTORY_SEPARATOR . 'generic_responses.php');
    include_once(__DIR__ . DIRECTORY_SEPARATOR . 'api' . DIRECTORY_SEPARATOR . 'request.php');

    $Configuration = verifyRequest();

    if($Configuration->API->Available == false)
    {
        unavailableError($Configuration->API->UnavailableMessage);
    }

    $AccessKey = null;
    $ModularAPI = new ModularAPI();

    if($Configuration->Policies->AuthenticatedRequired == true)
    {
        $AccessKey = verifyAuthentication($ModularAPI, $Configuration->Policies->ForceCertificate);
        if($AccessKey->Usage->expired() == true)
        {
            keyExpiredError();
        }
    }

    $Query = Request::parseQuery();
    if($Configuration->moduleExists($Query->Module) == false)
    {
        invalidModuleError();
    }

    $Module = $Configuration->getModule($Query->Module);

    if($Query->RequestMethod == RequestMethod::GET)
    {
        if($Module->GetMethodAllowed == false)
        {
            requestMethodNotAllowed($Query->RequestMethod);
        }
    }
    elseif($Query->RequestMethod == RequestMethod::POST)
    {
        if($Module->PostMethodAllowed == false)
        {
            requestMethodNotAllowed($Query->RequestMethod);
        }
    }
    else
    {
        // Any other request method which isn't supported isn't allowed
        requestMethodNotAllowed($Query->RequestMethod);
    }

    if($Module->RequireAuthentication == true)
    {
        if($AccessKey == null)
        {
            $AccessKey = verifyAuthentication($ModularAPI, false);

            if($AccessKey->Usage->expired() == true)
            {
                keyExpiredError();
            }
        }

        if($AccessKey->Permissions->AllowAll == false)
        {
            if($AccessKey->Permissions->hasPermission($Module->Name) == false)
            {
                invalidPermissionError();
            }
        }

        if($Module->RequireUsage == true)
        {
            if($AccessKey->Usage->usageExceeded() == true)
            {
                usageExceededError();
            }
        }

        if($AccessKey->State == AccessKeyStatus::Suspended)
        {
            accessKeySuspended();
        }
    }

    if($AccessKey == null)
    {
        $AccessKey = new AccessKey();
        $AccessKey->PublicID = 'NONE';
        $AccessKey->ID = 0;
    }
    else
    {
        try
        {
            $ModularAPI->AccessKeys()->trackUsage($AccessKey, $Module->RequireUsage);
        }
        catch(AccessKeyExpiredException $accessKeyExpiredException)
        {
            keyExpiredError();
        }
        catch(UsageExceededException $usageExceededException)
        {
            usageExceededError();
        }
    }

    $SafeVersion = str_ireplace('/', '_', $Query->Version);
    $SafeVersion = str_ireplace('\\', '_', $SafeVersion);
    $ModuleFile = __DIR__ . DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR . $SafeVersion . DIRECTORY_SEPARATOR . $Module->ScriptName . '.php';

    if(file_exists($ModuleFile) == false)
    {
        moduleScriptNotFoundError();
    }

    $ExecutionStart = microtime(true);
    $ExecutionEnd = null;
    $Response = null;
    $FatalError = false;
    $ExceptionDetails = null;
    $Parameters = [];

    try
    {
        $Parameters = Request::getParameters($Module->Parameters);
        $Response = null;
        if($SafeVersion == 'v2')
        {
            $Response = checkPlan($AccessKey);
        }
        if($Response == null)
        {
            /** @noinspection PhpIncludeInspection */
            include($ModuleFile);
            $Response = Module($AccessKey, $Parameters);
            $ExecutionEnd = microtime(true);
        }
        $ExecutionEnd = microtime(true);

    }
    catch(MissingParameterException $missingParameterException)
    {
        $ExecutionEnd = microtime(true);
        missingParameter($missingParameterException->ParameterName);
    }
    catch(Exception $exception)
    {
        $ExecutionEnd = microtime(true);
        $Response = new Response();
        $Response->ResponseCode = ServerError::_500;
        $Response->ResponseType = ContentType::application . '/' . FileType::json;
        $Response->Content = array(
            'status' => false,
            'code' => ServerError::_500,
            'message' => 'Internal Server Error',
            'exception_code' => $exception->getCode()
        );
        $ExceptionDetails = new ExceptionDetails();
        $ExceptionDetails->Line = $exception->getLine();
        $ExceptionDetails->File = $exception->getFile();
        $ExceptionDetails->Message = $exception->getMessage();
        $ExceptionDetails->ExceptionCode = $exception->getCode();
    }

    $ReferenceID = $ModularAPI->RequestsLog()->recordRequest(
        getClientIP(),
        ($ExecutionEnd - $ExecutionStart),
        $Query,
        $Parameters,
        $Response,
        Request::parseAuthentication(),
        $AccessKey->PublicID,
        $FatalError,
        $ExceptionDetails
    );

    $Response->executeResponse($ReferenceID);