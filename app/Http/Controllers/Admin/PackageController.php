<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Message;
use App\Http\Controllers\Controller;
use App\Services\Concrete\Admin\PackageService;
use App\Traits\ResponseAPI;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class PackageController extends Controller
{
    use ResponseAPI;

    protected $package_service;

    public function __construct(PackageService $package_service)
    {
        $this->middleware('permission:package.view')->only(['index', 'show', 'getData']);
        $this->middleware('permission:package.create')->only(['create']);
        $this->middleware('permission:package.edit')->only(['edit']);
        $this->middleware('permission:package.create|package.edit')->only(['store']);
        $this->middleware('permission:package.delete')->only(['destroy']);

        $this->package_service = $package_service;
    }

    public function index()
    {
        return view('admin.packages.index');
    }

    public function getData(Request $request)
    {
        try {
            return $this->package_service->getData($request->all());
        } catch (Exception $e) {
            return redirect()->back()->with('error',$e->getMessage());
        }
    }
    public function create()
    {
        return view('admin.packages.create');
    }
    public function edit($id)
    {
        $package = $this->package_service->getById($id);
        return view('admin.packages.create', compact('package'));
    }

    public function store(Request $request)
    {
        $rules = [
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('packages')
                    ->where(function ($query) use ($request) {
                        return $query->where(
                            'duration_type',
                            $request->duration_type
                        )->where('is_deleted', 0);
                    })
                    ->ignore($request->package_id, 'package_id')
            ],
            'price' => 'nullable|numeric|min:0',
            'price_yearly' => 'nullable|numeric|min:0',
            'duration_days' => 'required|integer',
            'duration_type' => 'required|string',
            'code' => 'nullable|string|max:50',
            'tagline' => 'nullable|string|max:255',
            'badge' => 'nullable|string|max:100',
            'best_for' => 'nullable|string|max:255',
            'currency' => 'nullable|string|max:10',
            'support' => 'nullable|string|max:255',
            'cta' => 'nullable|string|max:100',
            'is_custom' => 'nullable|boolean',
        ];

        $validate = Validator::make($request->all(), $rules);

        if ($validate->fails()) {
            return redirect()->back()->withErrors($validate)->withInput();
        }

        try {

            $obj = $request->only([
                'package_id',
                'name',
                'code',
                'description',
                'tagline',
                'badge',
                'best_for',
                'price',
                'price_yearly',
                'currency',
                'support',
                'cta',
                'order',
                'duration_type',
                'duration_days',
            ]);

            $obj['status'] = $request->status ?? 1;
            $obj['is_custom'] = $request->boolean('is_custom');
            $obj['currency'] = $obj['currency'] ?: 'PKR';

            foreach (['features', 'limitations'] as $jsonField) {
                $raw = $request->input($jsonField);
                if (is_string($raw)) {
                    $lines = array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $raw))));
                    $obj[$jsonField] = $lines;
                } elseif (is_array($raw)) {
                    $obj[$jsonField] = $raw;
                }
            }

            $compare = [];
            foreach (['accounting', 'hrPayroll', 'recurring', 'stockTransfers', 'b2bPortal', 'api', 'advancedReports'] as $key) {
                if ($request->has('compare_' . $key)) {
                    $val = $request->input('compare_' . $key);
                    if ($val === 'true' || $val === '1' || $val === 1 || $val === true) {
                        $compare[$key] = true;
                    } elseif ($val === 'false' || $val === '0' || $val === 0 || $val === false) {
                        $compare[$key] = false;
                    } else {
                        $compare[$key] = $val;
                    }
                }
            }
            if ($compare) {
                $obj['compare'] = $compare;
            }

            // Module access + limits are stored per-key in package_modules
            // (see SubscriptionModuleRegistry) - the legacy is_*_enabled /
            // max_* columns on `packages` are no longer written to.
            $obj['modules'] = $request->input('modules', []);

            $package = $this->package_service->save($obj);

            return redirect('admin/packages')->with('success', Message::SAVE);
        } catch (Exception $e) {

            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }
    }
    public function show($id)
    {
        try {
            $package = $this->package_service->getById($id);
            return $this->success(
                Message::FETCH,
                $package
            );
        } catch (Exception $e) {
            return $this->error(Message::ERROR);
        }
    }
    public function destroy($id)
    {
        try {

            $this->package_service->delete($id);

            return $this->success(
                Message::DELETE,
                []
            );
        } catch (Exception $e) {

            return $this->error(
                Message::ERROR
            );
        }
    }
}
