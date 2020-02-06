<?php


    namespace IntellivoidSubscriptionManager\Managers;


    use IntellivoidSubscriptionManager\Abstracts\SearchMethods\SubscriptionSearchMethod;
    use IntellivoidSubscriptionManager\Exceptions\DatabaseException;
    use IntellivoidSubscriptionManager\Exceptions\InvalidSearchMethodException;
    use IntellivoidSubscriptionManager\Exceptions\SubscriptionNotFoundException;
    use IntellivoidSubscriptionManager\IntellivoidSubscriptionManager;
    use IntellivoidSubscriptionManager\Objects\Subscription;
    use msqg\QueryBuilder;
    use ZiProto\ZiProto;

    /**
     * Class SubscriptionManager
     * @package IntellivoidSubscriptionManager\Managers
     */
    class SubscriptionManager
    {
        /**
         * @var IntellivoidSubscriptionManager
         */
        private $intellivoidSubscriptionManager;

        /**
         * SubscriptionManager constructor.
         * @param IntellivoidSubscriptionManager $intellivoidSubscriptionManager
         */
        public function __construct(IntellivoidSubscriptionManager $intellivoidSubscriptionManager)
        {
            $this->intellivoidSubscriptionManager = $intellivoidSubscriptionManager;
        }

        /**
         * Gets an existing subscription from the database
         *
         * @param string $search_method
         * @param string $value
         * @return Subscription
         * @throws DatabaseException
         * @throws InvalidSearchMethodException
         * @throws SubscriptionNotFoundException
         */
        public function getSubscription(string $search_method, string $value): Subscription
        {
            switch($search_method)
            {
                case SubscriptionSearchMethod::byId:
                    $search_method = $this->intellivoidSubscriptionManager->getDatabase()->real_escape_string($search_method);
                    $value = (int)$value;
                    break;

                case SubscriptionSearchMethod::byPublicId:
                    $search_method = $this->intellivoidSubscriptionManager->getDatabase()->real_escape_string($search_method);
                    $value = $this->intellivoidSubscriptionManager->getDatabase()->real_escape_string($value);
                    break;

                default:
                    throw new InvalidSearchMethodException();
            }

            $Query = QueryBuilder::select('subscriptions', [
                'id',
                'public_id',
                'account_id',
                'subscription_plan_id',
                'active',
                'billing_cycle',
                'next_billing_cycle',
                'properties',
                'created_timestamp',
                'flags'
            ], $search_method, $value);
            $QueryResults = $this->intellivoidSubscriptionManager->getDatabase()->query($Query);

            if($QueryResults == false)
            {
                throw new DatabaseException($Query, $this->intellivoidSubscriptionManager->getDatabase()->error);
            }
            else
            {
                if($QueryResults->num_rows !== 1)
                {
                    throw new SubscriptionNotFoundException();
                }

                $Row = $QueryResults->fetch_array(MYSQLI_ASSOC);
                $Row['flags'] = ZiProto::decode($Row['flags']);
                $Row['properties'] = ZiProto::decode($Row['properties']);
                return Subscription::fromArray($Row);
            }
        }

        /**
         * Updates an existing subscription to the database
         *
         * @param Subscription $subscription
         * @return bool
         * @throws DatabaseException
         */
        public function updateSubscription(Subscription $subscription): bool
        {
            $id = (int)$subscription->ID;
            $active = (int)$subscription->Active;
            $billing_cycle = (int)$subscription->BillingCycle;
            $next_billing_cycle = (int)$subscription->NextBillingCycle;
            $properties = ZiProto::encode($subscription->Properties->toArray());
            $properties = $this->intellivoidSubscriptionManager->getDatabase()->real_escape_string($properties);
            $flags = ZiProto::encode($subscription->Flags);
            $flags = $this->intellivoidSubscriptionManager->getDatabase()->real_escape_string($flags);

            $Query = QueryBuilder::update('subscriptions',array(
                'active' => $active,
                'billing_cycle' => $billing_cycle,
                'next_billing_cycle' => $next_billing_cycle,
                'properties' => $properties,
                'flags' => $flags
            ), 'id', $id);
            $QueryResults = $this->intellivoidSubscriptionManager->getDatabase()->query($Query);

            if($QueryResults == true)
            {
                return true;
            }
            else
            {
                throw new DatabaseException($Query, $this->intellivoidSubscriptionManager->getDatabase()->error);
            }
        }

        /**
         * Determines if the subscription requires billing
         *
         * @param Subscription $subscription
         * @return bool
         */
        public function billingRequired(Subscription $subscription): bool
        {
            if($subscription->NextBillingCycle > (int)time())
            {
                return True;
            }

            return False;
        }

        /**
         * Cancels an existing subscription
         *
         * @param Subscription $subscription
         * @return bool
         * @throws DatabaseException
         */
        public function cancelSubscription(Subscription $subscription)
        {
            $id = (int)$subscription->ID;

            $Query = "DELETE FROM `subscriptions` WHERE id=$id";
            $QueryResults = $this->intellivoidSubscriptionManager->getDatabase()->query($Query);

            if($QueryResults == true)
            {
                return true;
            }
            else
            {
                throw new DatabaseException($Query, $this->intellivoidSubscriptionManager->getDatabase()->error);
            }
        }

        /**
         * Gets subscriptions associated with an account ID
         *
         * @param int $account_id
         * @return array
         * @throws DatabaseException
         */
        public function getSubscriptionsByAccountID(int $account_id): array
        {
            $account_id = (int)$account_id;

            $Query = QueryBuilder::select('subscriptions', [
                'id',
                'public_id',
                'account_id',
                'subscription_plan_id',
                'active',
                'billing_cycle',
                'next_billing_cycle',
                'properties',
                'created_timestamp',
                'flags'
            ], 'account_id', $account_id);
            $QueryResults = $this->intellivoidSubscriptionManager->getDatabase()->query($Query);

            if($QueryResults == false)
            {
                throw new DatabaseException($Query, $this->intellivoidSubscriptionManager->getDatabase()->error);
            }
            else
            {
                $ResultsArray = [];

                while($Row = $QueryResults->fetch_assoc())
                {
                    $Row['flags'] = ZiProto::decode($Row['flags']);
                    $Row['properties'] = ZiProto::decode($Row['properties']);
                    $ResultsArray[] = $Row;
                }

                return $ResultsArray;
            }
        }

        /**
         * Determines if the Subscription Plan is associated with an account
         *
         * @param int $account_id
         * @param int $subscription_plan_id
         * @return bool
         * @throws DatabaseException
         */
        public function subscriptionPlanAssociatedWithAccount(int $account_id, int $subscription_plan_id): bool
        {
            $account_id = (int)$account_id;
            $subscription_plan_id = (int)$subscription_plan_id;

            $Query = QueryBuilder::select('subscriptions', ['id'],
                'account_id', $account_id . "' AND subscription_plan_id='$subscription_plan_id"
            );
            $QueryResults = $this->intellivoidSubscriptionManager->getDatabase()->query($Query);

            if($QueryResults == false)
            {
                throw new DatabaseException($Query, $this->intellivoidSubscriptionManager->getDatabase()->error);
            }
            else
            {
                if($QueryResults->num_rows !== 1)
                {
                    return false;
                }

                return true;
            }
        }

        /**
         * Gets a subscription plan associated with an account
         *
         * @param int $account_id
         * @param int $subscription_plan_id
         * @return Subscription
         * @throws DatabaseException
         * @throws SubscriptionNotFoundException
         */
        public function getSubscriptionPlanAssociatedWithAccount(int $account_id, int $subscription_plan_id): Subscription
        {
            $account_id = (int)$account_id;

            $Query = QueryBuilder::select('subscriptions', [
                'id',
                'public_id',
                'account_id',
                'subscription_plan_id',
                'active',
                'billing_cycle',
                'next_billing_cycle',
                'properties',
                'created_timestamp',
                'flags'
            ], 'account_id', $account_id . "' AND subscription_plan_id='$subscription_plan_id");
            $QueryResults = $this->intellivoidSubscriptionManager->getDatabase()->query($Query);

            if($QueryResults == false)
            {
                throw new DatabaseException($Query, $this->intellivoidSubscriptionManager->getDatabase()->error);
            }
            else
            {
                if($QueryResults->num_rows !== 1)
                {
                    throw new SubscriptionNotFoundException();
                }

                $Row = $QueryResults->fetch_array(MYSQLI_ASSOC);
                $Row['flags'] = ZiProto::decode($Row['flags']);
                $Row['properties'] = ZiProto::decode($Row['properties']);
                return Subscription::fromArray($Row);
            }
        }
    }