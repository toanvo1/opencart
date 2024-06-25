<?php

declare(strict_types=1);

namespace Paysera\Payment\Repository;

use Paysera\DataValidator\Validator\Contract\RepositoryInterface;
use Paysera\Payment\Model\OrderStatusModelWrapper;

class OrderStatusRepository implements RepositoryInterface
{
    protected OrderStatusModelWrapper $orderStatusModelWrapper;

    protected array $emptyOrderStatus;

    public function __construct(OrderStatusModelWrapper $orderStatusModelWrapper, string $emptyOrderStatusPlaceholder)
    {
        $this->orderStatusModelWrapper = $orderStatusModelWrapper;
        $this->emptyOrderStatus = [
            'order_status_id' => 0,
            'language_id' => '1',
            'name' => $emptyOrderStatusPlaceholder,
        ];
    }

    public function find(int $id): ?array
    {
        $orderStatus = $this->orderStatusModelWrapper->getOrderStatus($id);

        if (!count($orderStatus)) {
            return null;
        }

        return $orderStatus;
    }

    public function findAll(): array
    {
        $orderStatuses = $this->orderStatusModelWrapper->getOrderStatuses();
        array_unshift($orderStatuses, $this->emptyOrderStatus);

        return $orderStatuses;
    }
}
