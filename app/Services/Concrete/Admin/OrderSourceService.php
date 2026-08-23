<?php

namespace App\Services\Concrete\Admin;

use App\Models\OrderSource;
use App\Services\Concrete\Admin\Support\AbstractLookupTypeService;

class OrderSourceService extends AbstractLookupTypeService
{
    protected function newModelInstance()
    {
        return new OrderSource();
    }

    protected function pkField(): string
    {
        return 'order_source_id';
    }

    protected function domIdSuffix(): string
    {
        return 'OrderSource';
    }

    protected function defaultRows(): array
    {
        return [
            ['name' => 'POS', 'code' => 'POS'],
            ['name' => 'Website', 'code' => 'WEBSITE'],
            ['name' => 'Mobile App', 'code' => 'MOBILE_APP'],
        ];
    }
}
