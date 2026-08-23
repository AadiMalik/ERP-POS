<?php

namespace App\Services\Concrete\Admin;

use App\Models\OrderType;
use App\Services\Concrete\Admin\Support\AbstractLookupTypeService;

class OrderTypeService extends AbstractLookupTypeService
{
    protected function newModelInstance()
    {
        return new OrderType();
    }

    protected function pkField(): string
    {
        return 'order_type_id';
    }

    protected function domIdSuffix(): string
    {
        return 'OrderType';
    }

    protected function defaultRows(): array
    {
        return [
            ['name' => 'Mart', 'code' => 'MART'],
            ['name' => 'Takeaway', 'code' => 'TAKEAWAY'],
            ['name' => 'Delivery', 'code' => 'DELIVERY'],
        ];
    }
}
