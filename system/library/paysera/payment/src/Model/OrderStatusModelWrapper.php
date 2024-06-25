<?php

declare(strict_types=1);

namespace Paysera\Payment\Model;

class OrderStatusModelWrapper
{
    /**
     * @var \ModelLocalisationOrderStatus
     */
    protected $orderStatusModel;

    /**
     * @param \ModelLocalisationOrderStatus $modelLocalisationOrderStatus
     */
    public function __construct($modelLocalisationOrderStatus)
    {
        $this->orderStatusModel = $modelLocalisationOrderStatus;
    }

    public function getOrderStatus(int $orderStatusId): array
    {
        return $this->orderStatusModel->getOrderStatus($orderStatusId);
    }

    public function getOrderStatuses(array $data = []): array
    {
        return $this->orderStatusModel->getOrderStatuses($data);
    }
}
