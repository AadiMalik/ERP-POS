<?php

namespace App\Http\Controllers\Admin\Concerns;

use App\Enums\Message;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * Shared getData/store/edit/status/destroy actions for the "lookup type"
 * controller trio (Order Type, Order Source, Sale Type) - see
 * App\Services\Concrete\Admin\Support\AbstractLookupTypeService for the
 * matching service-layer consolidation. Each using controller keeps its own
 * constructor DI, permission middleware, and index()/other unique actions;
 * this only covers the byte-identical CRUD shape. Requires the composing
 * controller to also use App\Traits\ResponseAPI (for success()/error()/
 * validationResponse()).
 */
trait HasLookupTypeCrudActions
{
    abstract protected function lookupTypeService();

    abstract protected function lookupTypeTable(): string;

    abstract protected function lookupTypePkField(): string;

    public function getData(Request $request)
    {
        return $this->lookupTypeService()->getData($request->all());
    }

    public function store(Request $request)
    {
        $pk = $this->lookupTypePkField();

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique($this->lookupTypeTable(), 'code')
                    ->where(function ($query) use ($request) {
                        return $query->where('business_id', $request->business_id ?? Auth::user()->business_id)
                            ->where('is_deleted', 0);
                    })
                    ->ignore($request->{$pk}, $pk),
            ],
        ];

        $validate = Validator::make($request->all(), $rules);
        if ($validate->fails()) {
            return $this->validationResponse($validate->errors()->first());
        }

        $obj = $request->only([$pk, 'name', 'code', 'sort_order']);
        $obj['business_id'] = $request->business_id ?? Auth::user()->business_id;
        $obj['is_default'] = $request->boolean('is_default');
        $obj['status'] = $request->status ?? 'active';

        try {
            $result = $this->lookupTypeService()->save($obj);
            return $this->success(
                empty($request->{$pk}) ? Message::SAVE : Message::UPDATE,
                $result
            );
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function edit($id)
    {
        try {
            $result = $this->lookupTypeService()->getById($id);
            return $this->success(Message::FETCH, $result);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function status($id)
    {
        try {
            $this->lookupTypeService()->status($id);
            return $this->success(Message::STATUS, []);
        } catch (Exception $e) {
            return $this->error(Message::ERROR);
        }
    }

    public function destroy($id)
    {
        try {
            $this->lookupTypeService()->delete($id);
            return $this->success(Message::DELETE, []);
        } catch (Exception $e) {
            return $this->error(Message::ERROR);
        }
    }
}
