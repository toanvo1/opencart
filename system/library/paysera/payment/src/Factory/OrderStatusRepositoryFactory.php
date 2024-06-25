<?php

declare(strict_types=1);

namespace Paysera\Payment\Factory;

use Paysera\Payment\Model\OrderStatusModelWrapper;
use Paysera\Payment\Repository\OrderStatusRepository;

class OrderStatusRepositoryFactory
{
    /**
     * @var \Registry
     */
    protected $registry;

    /**
     * @param \Registry $registry
     */
    public function __construct($registry)
    {
        $this->registry = $registry;
    }

    public function createInstance(): OrderStatusRepository
    {
        $this->registry->get('load')->model('localisation/order_status');
        $orderStatusModelWrapper = new OrderStatusModelWrapper($this->registry->get('model_localisation_order_status'));

        return new OrderStatusRepository(
            $orderStatusModelWrapper,
            $this->registry->get('language')->get('empty_order_status_placeholder')
        );
    }
}
