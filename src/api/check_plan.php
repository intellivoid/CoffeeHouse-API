<?php

    use CoffeeHouse\Abstracts\PlanSearchMethod;
    use CoffeeHouse\CoffeeHouse;
    use CoffeeHouse\Exceptions\ApiPlanNotFoundException;
    use CoffeeHouse\Exceptions\DatabaseException;
    use CoffeeHouse\Exceptions\InvalidSearchMethodException;
    use IntellivoidAccounts\Abstracts\TransactionType;
    use IntellivoidAccounts\Exceptions\ConfigurationNotFoundException;
    use IntellivoidAccounts\Exceptions\InsufficientFundsException;
    use IntellivoidAccounts\IntellivoidAccounts;
    use ModularAPI\Abstracts\HTTP\ContentType;
    use ModularAPI\Abstracts\HTTP\FileType;
    use ModularAPI\Abstracts\HTTP\ResponseCode\ClientError;
    use ModularAPI\Abstracts\HTTP\ResponseCode\ServerError;
    use ModularAPI\Exceptions\AccessKeyNotFoundException;
    use ModularAPI\Exceptions\NoResultsFoundException;
    use ModularAPI\Exceptions\UnsupportedSearchMethodException;
    use ModularAPI\Objects\AccessKey;
    use ModularAPI\Objects\Response;

    /**
     * Written for the OpenBlu API, this script checks if the plan
     * is still active, if not then it will charge the account.
     */


    /**
     * Checks if the plan is requiring payment, if it does processes the transaction
     * and continues.
     * @param AccessKey $accessKey
     * @return Response|null
     * @throws AccessKeyNotFoundException
     * @throws NoResultsFoundException
     * @throws UnsupportedSearchMethodException
     * @throws ApiPlanNotFoundException
     * @throws DatabaseException
     * @throws InvalidSearchMethodException
     * @throws ConfigurationNotFoundException
     */
    function checkPlan(AccessKey $accessKey)
    {
        $CoffeeHouse = new CoffeeHouse();
        $Plan = $CoffeeHouse->getApiPlanManager()->getPlan(PlanSearchMethod::byId, $accessKey->ID);

        if($Plan->PlanStarted == false)
        {
            $Response = new Response();
            $Response->ResponseCode = ClientError::_400;
            $Response->ResponseType = ContentType::application . '/' . FileType::json;
            $Response->Content = array(
                'status' => false,
                'code' => ClientError::_400,
                'message' => 'You have no active plan subscription'
            );

            return $Response;
        }

        if($Plan->Active == false)
        {
            if($Plan->PaymentRequired == false)
            {
                $Response = new Response();
                $Response->ResponseCode = ClientError::_400;
                $Response->ResponseType = ContentType::application . '/' . FileType::json;
                $Response->Content = array(
                    'status' => false,
                    'code' => ClientError::_400,
                    'message' => 'API Plan is not active, see dashboard for more information'
                );

                return $Response;
            }
        }

        if(time() > $Plan->NextBillingCycle)
        {
            $IntellivoidAccounts = new IntellivoidAccounts();
            $Response = new Response();

            try
            {
                $IntellivoidAccounts->getTransactionRecordManager()->createTransaction(
                    $Plan->AccountId, $Plan->PricePerCycle, 'Intellivoid', TransactionType::SubscriptionPayment
                );
            }
            catch(InsufficientFundsException $insufficientFundsException)
            {
                $Plan->Active = false;
                $Plan->PaymentRequired = true;
                $CoffeeHouse->getApiPlanManager()->updatePlan($CoffeeHouse);

                $Response->ResponseCode = ClientError::_400;
                $Response->ResponseType = ContentType::application . '/' . FileType::json;
                $Response->Content = array(
                    'status' => false,
                    'code' => ClientError::_400,
                    'message' => 'Insufficient funds for API Billing Cycle'
                );

                return $Response;
            }
            catch(Exception $exception)
            {
                $Response->ResponseCode = ServerError::_500;
                $Response->ResponseType = ContentType::application . '/' . FileType::json;
                $Response->Content = array(
                    'status' => false,
                    'code' => ServerError::_500,
                    'message' => 'There was an unknown issue while trying to process your API Subscription'
                );

                return $Response;
            }

            $Plan->NextBillingCycle = time() + $Plan->BillingCycle;
            $Plan->Active = true;
            $Plan->PaymentRequired = false;

            $CoffeeHouse->getApiPlanManager()->updatePlan($Plan);
        }

        return null;
    }