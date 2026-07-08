<?php

namespace App\Services\Concrete\Admin;

use App\Enums\Filter;
use App\Enums\RoleNames;
use App\Enums\Status;
use App\Models\Timezone;
use App\Repository\Repository;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\DataTables;

class CommonService
{
    protected $model_timezone;

    public function __construct()
    {
        $this->model_timezone = new Repository(new Timezone());
    }

    public function getAllTimezone()
    {
        return $this->model_timezone->getModel()::orderBy('name', 'asc')->get();
    }
}
