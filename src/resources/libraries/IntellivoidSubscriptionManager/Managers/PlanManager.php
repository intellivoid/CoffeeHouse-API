<?php /** @noinspection PhpUnused */


    namespace IntellivoidSubscriptionManager\Managers;


    use IntellivoidSubscriptionManager\Abstracts\SearchMethods\SubscriptionPlanSearchMethod;
    use IntellivoidSubscriptionManager\Exceptions\DatabaseException;
    use IntellivoidSubscriptionManager\Exceptions\InvalidBillingCycleException;
    use IntellivoidSubscriptionManager\Exceptions\InvalidCyclePriceException;
    use IntellivoidSubscriptionManager\Exceptions\InvalidInitialPriceException;
    use IntellivoidSubscriptionManager\Exceptions\InvalidSearchMethodException;
    use IntellivoidSubscriptionManager\Exceptions\SubscriptionPlanNotFoundException;
    use IntellivoidSubscriptionManager\IntellivoidSubscriptionManager;
    use IntellivoidSubscriptionManager\Objects\SubscriptionPlan;
    use msqg\QueryBuilder;
    use ZiProto\ZiProto;

    /**
     * Class PlanManager
     * @package IntellivoidSubscriptionManager\Managers
     */
    class PlanManager
    {
        /**
         * @var IntellivoidSubscriptionManager
         */
        private $intellivoidSubscriptionManager;

        /**
         * PlanManager constructor.
         * @param IntellivoidSubscriptionManager $intellivoidSubscriptionManager
         */
        public function __construct(IntellivoidSubscriptionManager $intellivoidSubscriptionManager)
        {
            $this->intellivoidSubscriptionManager = $intellivoidSubscriptionManager;
        }

        /**
         * Returns a Subscription Plan from the database
         *
         * @param string $search_method
         * @param string $value
         * @return SubscriptionPlan
         * @throws DatabaseException
         * @throws InvalidSearchMethodException
         * @throws SubscriptionPlanNotFoundException
         * @noinspection DuplicatedCode
         */
        public function getSubscriptionPlan(string $search_method, string $value): SubscriptionPlan
        {
            switch($search_method)
            {
                case SubscriptionPlanSearchMethod::byId:
                    $search_method = $this->intellivoidSubscriptionManager->getDatabase()->real_escape_string($search_method);
                    $value = (int)$value;
                    break;

                case SubscriptionPlanSearchMethod::byPublicId:
                    $search_method = $this->intellivoidSubscriptionManager->getDatabase()->real_escape_string($search_method);
                    $value = $this->intellivoidSubscriptionManager->getDatabase()->real_escape_string($value);
                    break;

                default:
                    throw new InvalidSearchMethodException();
            }

            $Query = QueryBuilder::select('subscription_plans', [
                'id',
                'public_id',
                'application_id',
                'plan_name',
                'features',
                'initial_price',
                'cycle_price',
                'billing_cycle',
                'status',
                'flags',
                'last_updated',
                'created_timestamp'
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
                    throw new SubscriptionPlanNotFoundException();
                }

                $Row = $QueryResults->fetch_array(MYSQLI_ASSOC);
                $Row['features'] = ZiProto::decode($Row['features']);
                $Row['flags'] = ZiProto::decode($Row['flags']);
                return SubscriptionPlan::fromArray($Row);
            }
        }

        /**
         * Fetches a Subscription Plan by a Plan Name
         *
         * @param int $application_id
         * @param string $name
         * @return SubscriptionPlan
         * @throws DatabaseException
         * @throws SubscriptionPlanNotFoundException
         * @noinspection DuplicatedCode
         */
        public function getSubscriptionPlanByName(int $application_id, string $name): SubscriptionPlan
        {
            $application_id = (int)$application_id;
            $name = $this->intellivoidSubscriptionManager->getDatabase()->real_escape_string($name);

            $Query = QueryBuilder::select('subscription_plans', [
                'id',
                'public_id',
                'application_id',
                'plan_name',
                'features',
                'initial_price',
                'cycle_price',
                'billing_cycle',
                'status',
                'flags',
                'last_updated',
                'created_timestamp'
            ], 'application_id', $application_id . "' AND plan_name='$name");
            $QueryResults = $this->intellivoidSubscriptionManager->getDatabase()->query($Query);

            if($QueryResults == false)
            {
                throw new DatabaseException($Query, $this->intellivoidSubscriptionManager->getDatabase()->error);
            }
            else
            {
                if($QueryResults->num_rows !== 1)
                {
                    throw new SubscriptionPlanNotFoundException();
                }

                $Row = $QueryResults->fetch_array(MYSQLI_ASSOC);
                $Row['features'] = ZiProto::decode($Row['features']);
                $Row['flags'] = ZiProto::decode($Row['flags']);
                return SubscriptionPlan::fromArray($Row);
            }
        }

        /**
         * Updates an existing Subscription Plan
         *
         * @param SubscriptionPlan $subscriptionPlan
         * @return bool
         * @throws DatabaseException
         * @throws InvalidBillingCycleException
         * @throws InvalidCyclePriceException
         * @throws InvalidInitialPriceException
         */
        public function updateSubscriptionPlan(SubscriptionPlan $subscriptionPlan): bool
        {
            $subscriptionPlanArray = $subscriptionPlan->toArray();

            if((float)$subscriptionPlanArray['initial_price'] < 0)
            {
                throw new InvalidInitialPriceException();
            }

            if((float)$subscriptionPlanArray['cycle_price'] < 0)
            {
                throw new InvalidCyclePriceException();
            }

            if((int)$subscriptionPlanArray['billing_cycle'] < 0)
            {
                throw new InvalidBillingCycleException();
            }

            $features = ZiProto::encode($subscriptionPlanArray['features']);
            $features = $this->intellivoidSubscriptionManager->getDatabase()->real_escape_string($features);
            $flags = ZiProto::encode($subscriptionPlanArray['flags']);
            $flags = $this->intellivoidSubscriptionManager->getDatabase()->real_escape_string($flags);
            $last_updated = (int)time();
            $billing_cycle = (int)$subscriptionPlanArray['billing_cycle'];
            $initial_price = (float)$subscriptionPlanArray['initial_price'];
            $cycle_price = (float)$subscriptionPlanArray['cycle_price'];
            $status = (int)$subscriptionPlanArray['status'];

            $Query = QueryBuilder::update('subscription_plans', array(
                'features' => $features,
                'flags' => $flags,
                'initial_price' => $initial_price,
                'cycle_price' => $cycle_price,
                'billing_cycle' => $billing_cycle,
                'status' => $status,
                'last_updated' => $last_updated
            ), 'id', (int)$subscriptionPlanArray['id']);
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
         * Returns the subscription plans associated to a Application
         *
         * @param int $application_id
         * @return array
         * @throws DatabaseException
         */
        public function getSubscriptionPlansByApplication(int $application_id): array
        {
            $application_id = (int)$application_id;

            $Query = QueryBuilder::select('subscription_plans', [
                'id',
                'public_id',
                'application_id',
                'plan_name',
                'features',
                'initial_price',
                'cycle_price',
                'billing_cycle',
                'status',
                'flags',
                'last_updated',
                'created_timestamp'
            ], 'application_id', $application_id);
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
                    $Row['features'] = ZiProto::decode($Row['features']);
                    $Row['flags'] = ZiProto::decode($Row['flags']);
                    $ResultsArray[] = SubscriptionPlan::fromArray($Row);
                }

                return $ResultsArray;
            }
        }
    }