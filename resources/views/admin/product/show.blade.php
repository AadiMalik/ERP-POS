@extends('layouts.app')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center py-3 mb-4 flex-wrap gap-2">
        <div>
            <h4 class="fw-bold mb-1">{{ $product->name }}</h4>
            <div class="d-flex flex-wrap gap-1">
                <span class="badge bg-label-secondary">{{ __('products.type_' . $product->type) }}</span>
                @if ($product->usage_type)
                    <span class="badge bg-label-info">{{ __('products.usage_' . $product->usage_type) }}</span>
                @endif
                <span class="badge bg-{{ $product->status == 'active' ? 'success' : 'secondary' }}">
                    {{ $product->status == 'active' ? __('common.active') : __('common.inactive') }}
                </span>
            </div>
        </div>
        <div class="d-flex gap-2">
            @can('product.edit')
                <a href="{{ route('product.edit', $product->product_id) }}" class="btn btn-primary">
                    <i class="fa fa-pencil"></i> {{ __('common.edit') }}
                </a>
            @endcan
            <a href="{{ url('admin/product') }}" class="btn btn-outline-secondary">
                <i class="fa fa-arrow-left"></i> {{ __('common.back') }}
            </a>
        </div>
    </div>

    <div class="row g-4 mb-4">
        @if ($kpis['track_stock'])
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-muted small">{{ __('products.kpi_on_hand') }}</div>
                    <div class="fw-semibold">{{ decimal($kpis['on_hand'] ?? 0) }}</div>
                </div>
            </div>
        </div>
        @endif
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-muted small">{{ __('products.kpi_sold') }}</div>
                    <div class="fw-semibold">{{ decimal($kpis['sold'] ?? 0) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-muted small">{{ __('products.kpi_purchased') }}</div>
                    <div class="fw-semibold">{{ decimal($kpis['purchased'] ?? 0) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-muted small">{{ __('products.kpi_shares') }}</div>
                    <div class="fw-semibold">{{ $kpis['shares'] ?? 0 }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-muted small">{{ __('common.status') }}</div>
                    <div class="fw-semibold text-capitalize">{{ $product->status == 'active' ? __('common.active') : __('common.inactive') }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <ul class="nav nav-tabs card-header-tabs" id="productTabs" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" data-bs-toggle="tab" href="#overview" role="tab">{{ __('products.tab_overview') }}</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="tab" href="#variations" role="tab">{{ __('products.tab_variations') }}</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="tab" href="#sales" role="tab">{{ __('products.tab_sales') }}</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="tab" href="#purchases" role="tab">{{ __('products.tab_purchases') }}</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="tab" href="#consumption" role="tab">{{ __('products.tab_consumption') }}</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="tab" href="#shares" role="tab">{{ __('products.tab_shares') }}</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="tab" href="#stock" role="tab">{{ __('products.tab_stock') }}</a>
                </li>
            </ul>
        </div>
        <div class="card-body">
            <div class="tab-content">
                <div class="tab-pane fade show active" id="overview" role="tabpanel">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <h6>{{ __('products.identity') }}</h6>
                            <table class="table table-sm">
                                <tr><th>{{ __('common.business') }}</th><td>{{ $product->business->name ?? '-' }}</td></tr>
                                <tr><th>{{ __('products.category') }}</th><td>{{ $product->category->name ?? '-' }}</td></tr>
                                <tr><th>{{ __('products.sub_category') }}</th><td>{{ $product->subCategory->name ?? '-' }}</td></tr>
                                <tr><th>{{ __('products.brand') }}</th><td>{{ $product->brand->name ?? '-' }}</td></tr>
                                <tr><th>{{ __('products.slug') }}</th><td>{{ $product->slug ?? '-' }}</td></tr>
                                <tr><th>{{ __('products.tags') }}</th><td>{{ $product->tags->pluck('name')->join(', ') ?: '-' }}</td></tr>
                                <tr><th>{{ __('products.short_description') }}</th><td>{{ $product->short_description ?: '-' }}</td></tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h6>{{ __('products.flags') }}</h6>
                            <table class="table table-sm">
                                <tr><th>{{ __('products.track_stock') }}</th><td>{{ $product->is_track_stock ? __('common.yes') : __('common.no') }}</td></tr>
                                <tr><th>{{ __('products.flag_purchasable') }}</th><td>{{ $product->is_purchasable ? __('common.yes') : __('common.no') }}</td></tr>
                                <tr><th>{{ __('products.flag_sellable') }}</th><td>{{ $product->is_sellable ? __('common.yes') : __('common.no') }}</td></tr>
                                <tr><th>{{ __('products.flag_raw_material') }}</th><td>{{ $product->is_raw_material ? __('common.yes') : __('common.no') }}</td></tr>
                                <tr><th>{{ __('products.flag_manufactured') }}</th><td>{{ $product->is_manufactured ? __('common.yes') : __('common.no') }}</td></tr>
                                <tr><th>{{ __('products.pos_visible') }}</th><td>{{ $product->is_pos_visible ? __('common.yes') : __('common.no') }}</td></tr>
                                <tr><th>{{ __('products.website') }}</th><td>{{ $product->is_website_visible ? __('common.yes') : __('common.no') }}</td></tr>
                                <tr><th>{{ __('products.app') }}</th><td>{{ $product->is_app_visible ? __('common.yes') : __('common.no') }}</td></tr>
                                <tr><th>{{ __('products.featured') }}</th><td>{{ $product->is_featured ? __('common.yes') : __('common.no') }}</td></tr>
                                <tr><th>{{ __('products.loyalty_enabled') }}</th><td>{{ $product->is_loyalty_enabled ? __('common.yes') : __('common.no') }}</td></tr>
                            </table>
                        </div>
                        <div class="col-md-12">
                            <h6>{{ __('products.images') }}</h6>
                            @if ($product->productImages->isEmpty())
                                <p class="text-muted mb-0">{{ __('products.empty_images') }}</p>
                            @else
                                <div class="d-flex flex-wrap gap-2">
                                    @foreach ($product->productImages->sortBy('sorting') as $image)
                                        <img src="{{ $image->image_url }}" alt="{{ $product->name }}" class="rounded border" style="width: 80px; height: 80px; object-fit: cover;">
                                    @endforeach
                                </div>
                            @endif
                        </div>
                        @if ($charts['has_qty_trend'])
                        <div class="col-md-12">
                            <h6>{{ __('products.chart_qty_trend') }}</h6>
                            <div id="productQtyTrendChart"></div>
                        </div>
                        @endif
                        @if (!empty($charts['stock_by_type']))
                        <div class="col-md-6">
                            <h6>{{ __('products.chart_stock_by_type') }}</h6>
                            <div id="productStockTypeChart"></div>
                        </div>
                        @endif
                        @if (!empty($charts['shares_by_platform']))
                        <div class="col-md-6">
                            <h6>{{ __('products.chart_shares_by_platform') }}</h6>
                            <div id="productShareChart"></div>
                        </div>
                        @endif
                    </div>
                </div>

                <div class="tab-pane fade" id="variations" role="tabpanel">
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered">
                            <thead>
                                <tr>
                                    <th>{{ __('products.variation_name') }}</th>
                                    <th>{{ __('products.sku') }}</th>
                                    <th>{{ __('products.barcode') }}</th>
                                    <th>{{ __('products.sale_price') }}</th>
                                    <th>{{ __('products.purchase_price') }}</th>
                                    @if ($kpis['track_stock'])
                                        <th>{{ __('common.warehouse') }}</th>
                                        <th>{{ __('products.kpi_on_hand') }}</th>
                                        <th>{{ __('products.col_reserved') }}</th>
                                        <th>{{ __('products.col_avg_price') }}</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($product->productVariations as $variation)
                                    @php $variationStocks = $stocks->where('product_variation_id', $variation->product_variation_id); @endphp
                                    @if ($kpis['track_stock'] && $variationStocks->isNotEmpty())
                                        @foreach ($variationStocks as $stock)
                                        <tr>
                                            <td>{{ $variation->name }}</td>
                                            <td>{{ $variation->sku ?? '-' }}</td>
                                            <td>{{ $variation->barcode ?? '-' }}</td>
                                            <td>{{ currency($variation->sale_price ?? 0) }}</td>
                                            <td>{{ currency($variation->purchase_price ?? 0) }}</td>
                                            <td>{{ $stock->warehouse->name ?? '-' }}</td>
                                            <td>{{ decimal($stock->quantity) }}</td>
                                            <td>{{ decimal($stock->reserved_quantity) }}</td>
                                            <td>{{ currency($stock->avg_price ?? 0) }}</td>
                                        </tr>
                                        @endforeach
                                    @else
                                        <tr>
                                            <td>{{ $variation->name }}</td>
                                            <td>{{ $variation->sku ?? '-' }}</td>
                                            <td>{{ $variation->barcode ?? '-' }}</td>
                                            <td>{{ currency($variation->sale_price ?? 0) }}</td>
                                            <td>{{ currency($variation->purchase_price ?? 0) }}</td>
                                            @if ($kpis['track_stock'])
                                                <td colspan="3" class="text-muted">{{ __('common.na') }}</td>
                                            @endif
                                        </tr>
                                    @endif
                                @empty
                                    <tr><td colspan="{{ $kpis['track_stock'] ? 9 : 5 }}" class="text-center text-muted">{{ __('products.empty_variations') }}</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="tab-pane fade" id="sales" role="tabpanel">
                    <h6>{{ __('products.tab_sales') }}</h6>
                    <div class="table-responsive mb-4">
                        <table class="table table-sm table-bordered">
                            <thead>
                                <tr>
                                    <th>{{ __('common.date') }}</th>
                                    <th>{{ __('products.col_order') }}</th>
                                    <th>{{ __('common.customer') }}</th>
                                    <th>{{ __('products.variations') }}</th>
                                    <th>{{ __('common.quantity') }}</th>
                                    <th>{{ __('common.price') }}</th>
                                    <th>{{ __('common.total') }}</th>
                                    <th>{{ __('products.col_source') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($sales as $row)
                                <tr>
                                    <td>{{ localDate($row->order->sale_date ?? null) }}</td>
                                    <td>
                                        <a href="{{ route('order.show', $row->order_id) }}">{{ $row->order->daily_order_id ?? $row->order_id }}</a>
                                    </td>
                                    <td>{{ $row->order->user->name ?? '-' }}</td>
                                    <td>{{ $row->productVariation->name ?? '-' }}</td>
                                    <td>{{ decimal($row->quantity) }}</td>
                                    <td>{{ currency($row->final_unit_price ?? $row->unit_price ?? 0) }}</td>
                                    <td>{{ currency($row->total ?? 0) }}</td>
                                    <td>{{ !empty($row->order->register_id) ? __('products.source_pos') : __('products.source_order') }}</td>
                                </tr>
                                @empty
                                <tr><td colspan="8" class="text-center text-muted">{{ __('products.empty_sales') }}</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <h6>{{ __('products.sale_returns') }}</h6>
                    <div class="table-responsive mb-4">
                        <table class="table table-sm table-bordered">
                            <thead>
                                <tr>
                                    <th>{{ __('common.date') }}</th>
                                    <th>{{ __('products.col_document') }}</th>
                                    <th>{{ __('products.variations') }}</th>
                                    <th>{{ __('common.quantity') }}</th>
                                    <th>{{ __('common.total') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($sale_returns as $row)
                                <tr>
                                    <td>{{ localDate($row->orderReturn->order_return_date ?? null) }}</td>
                                    <td>{{ $row->orderReturn->order_return_no ?? $row->order_return_id }}</td>
                                    <td>{{ $row->productVariation->name ?? '-' }}</td>
                                    <td>{{ decimal($row->return_quantity) }}</td>
                                    <td>{{ currency($row->total ?? 0) }}</td>
                                </tr>
                                @empty
                                <tr><td colspan="5" class="text-center text-muted">{{ __('products.empty_sale_returns') }}</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if ($product->type === 'service')
                    <h6>{{ __('products.service_sales') }}</h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered">
                            <thead>
                                <tr>
                                    <th>{{ __('common.date') }}</th>
                                    <th>{{ __('products.col_document') }}</th>
                                    <th>{{ __('common.name') }}</th>
                                    <th>{{ __('common.quantity') }}</th>
                                    <th>{{ __('common.total') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($service_sales as $row)
                                <tr>
                                    <td>{{ localDate($row->service_sale_date ?? null) }}</td>
                                    <td>{{ $row->service_sale_no ?? $row->service_sale_id }}</td>
                                    <td>{{ $row->item_name ?? '-' }}</td>
                                    <td>{{ decimal($row->quantity) }}</td>
                                    <td>{{ currency($row->total ?? 0) }}</td>
                                </tr>
                                @empty
                                <tr><td colspan="5" class="text-center text-muted">{{ __('products.empty_service_sales') }}</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @endif
                </div>

                <div class="tab-pane fade" id="purchases" role="tabpanel">
                    <h6>{{ __('products.tab_purchases') }}</h6>
                    <div class="table-responsive mb-4">
                        <table class="table table-sm table-bordered">
                            <thead>
                                <tr>
                                    <th>{{ __('common.date') }}</th>
                                    <th>{{ __('products.col_document') }}</th>
                                    <th>{{ __('products.col_supplier') }}</th>
                                    <th>{{ __('products.variations') }}</th>
                                    <th>{{ __('products.ordered_qty') }}</th>
                                    <th>{{ __('products.received_qty') }}</th>
                                    <th>{{ __('common.price') }}</th>
                                    <th>{{ __('common.total') }}</th>
                                    <th>{{ __('common.status') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($purchases as $row)
                                <tr>
                                    <td>{{ localDate($row->purchase_date ?? null) }}</td>
                                    <td>
                                        <a href="{{ route('purchase.edit', $row->purchase_header_id ?? $row->purchase_id) }}">{{ $row->purchase_no ?? $row->purchase_id }}</a>
                                    </td>
                                    <td>{{ $row->supplier_name ?? '-' }}</td>
                                    <td>{{ $row->productVariation->name ?? '-' }}</td>
                                    <td>{{ decimal($row->ordered_quantity) }}</td>
                                    <td>{{ decimal($row->received_quantity) }}</td>
                                    <td>{{ currency($row->unit_price ?? 0) }}</td>
                                    <td>{{ currency($row->total ?? 0) }}</td>
                                    <td class="text-capitalize">{{ $row->purchase_status ?? '-' }}</td>
                                </tr>
                                @empty
                                <tr><td colspan="9" class="text-center text-muted">{{ __('products.empty_purchases') }}</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <h6>{{ __('products.grn') }}</h6>
                    <div class="table-responsive mb-4">
                        <table class="table table-sm table-bordered">
                            <thead>
                                <tr>
                                    <th>{{ __('common.date') }}</th>
                                    <th>{{ __('products.col_document') }}</th>
                                    <th>{{ __('products.variations') }}</th>
                                    <th>{{ __('products.received_qty') }}</th>
                                    <th>{{ __('common.total') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($grns as $row)
                                <tr>
                                    <td>{{ localDate($row->goodReceiptNote->good_receipt_note_date ?? null) }}</td>
                                    <td>{{ $row->goodReceiptNote->good_receipt_note_no ?? $row->good_receipt_note_id }}</td>
                                    <td>{{ $row->productVariation->name ?? '-' }}</td>
                                    <td>{{ decimal($row->received_quantity) }}</td>
                                    <td>{{ currency($row->total ?? 0) }}</td>
                                </tr>
                                @empty
                                <tr><td colspan="5" class="text-center text-muted">{{ __('products.empty_grn') }}</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <h6>{{ __('products.purchase_returns') }}</h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered">
                            <thead>
                                <tr>
                                    <th>{{ __('common.date') }}</th>
                                    <th>{{ __('products.col_document') }}</th>
                                    <th>{{ __('products.variations') }}</th>
                                    <th>{{ __('common.quantity') }}</th>
                                    <th>{{ __('common.total') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($purchase_returns as $row)
                                <tr>
                                    <td>{{ localDate($row->purchaseReturn->purchase_return_date ?? null) }}</td>
                                    <td>{{ $row->purchaseReturn->purchase_return_no ?? $row->purchase_return_id }}</td>
                                    <td>{{ $row->productVariation->name ?? '-' }}</td>
                                    <td>{{ decimal($row->return_quantity) }}</td>
                                    <td>{{ currency($row->total ?? 0) }}</td>
                                </tr>
                                @empty
                                <tr><td colspan="5" class="text-center text-muted">{{ __('products.empty_purchase_returns') }}</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="tab-pane fade" id="consumption" role="tabpanel">
                    <h6>{{ __('products.recipes_as_finished') }}</h6>
                    <div class="table-responsive mb-4">
                        <table class="table table-sm table-bordered">
                            <thead>
                                <tr>
                                    <th>{{ __('products.variations') }}</th>
                                    <th>{{ __('products.sku') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($recipes_as_finished as $row)
                                <tr>
                                    <td>{{ $row->productVariation->name ?? '-' }}</td>
                                    <td>{{ $row->productVariation->sku ?? '-' }}</td>
                                </tr>
                                @empty
                                <tr><td colspan="2" class="text-center text-muted">{{ __('common.no_data') }}</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <h6>{{ __('products.recipes_as_material') }}</h6>
                    <div class="table-responsive mb-4">
                        <table class="table table-sm table-bordered">
                            <thead>
                                <tr>
                                    <th>{{ __('products.col_document') }}</th>
                                    <th>{{ __('common.quantity') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($recipes_as_material as $row)
                                <tr>
                                    <td>{{ $row->recipe->product->name ?? '-' }}</td>
                                    <td>{{ decimal($row->quantity) }}</td>
                                </tr>
                                @empty
                                <tr><td colspan="2" class="text-center text-muted">{{ __('common.no_data') }}</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <h6>{{ __('products.manufacturing_plans') }}</h6>
                    <div class="table-responsive mb-4">
                        <table class="table table-sm table-bordered">
                            <thead>
                                <tr>
                                    <th>{{ __('common.date') }}</th>
                                    <th>{{ __('products.col_document') }}</th>
                                    <th>{{ __('products.variations') }}</th>
                                    <th>{{ __('common.quantity') }}</th>
                                    <th>{{ __('common.status') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($manufacturing_plans as $row)
                                <tr>
                                    <td>{{ localDate($row->plan_date ?? null) }}</td>
                                    <td>{{ $row->plan_no ?? $row->manufacturing_plan_id }}</td>
                                    <td>{{ $row->productVariation->name ?? '-' }}</td>
                                    <td>{{ decimal($row->planned_quantity) }} / {{ decimal($row->produced_quantity) }}</td>
                                    <td class="text-capitalize">{{ $row->status ?? '-' }}</td>
                                </tr>
                                @empty
                                <tr><td colspan="5" class="text-center text-muted">{{ __('common.no_data') }}</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <h6>{{ __('products.plan_materials') }}</h6>
                    <div class="table-responsive mb-4">
                        <table class="table table-sm table-bordered">
                            <thead>
                                <tr>
                                    <th>{{ __('products.col_document') }}</th>
                                    <th>{{ __('products.variations') }}</th>
                                    <th>{{ __('common.quantity') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($plan_materials as $row)
                                <tr>
                                    <td>{{ $row->plan->plan_no ?? '-' }}</td>
                                    <td>{{ $row->productVariation->name ?? '-' }}</td>
                                    <td>{{ decimal($row->required_base_quantity) }}</td>
                                </tr>
                                @empty
                                <tr><td colspan="3" class="text-center text-muted">{{ __('common.no_data') }}</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <h6>{{ __('products.tab_consumption') }}</h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered">
                            <thead>
                                <tr>
                                    <th>{{ __('common.date') }}</th>
                                    <th>{{ __('products.col_document') }}</th>
                                    <th>{{ __('products.variations') }}</th>
                                    <th>{{ __('common.warehouse') }}</th>
                                    <th>{{ __('common.quantity') }}</th>
                                    <th>{{ __('common.total') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($consumptions as $row)
                                <tr>
                                    <td>{{ localDate($row->date_created ?? null) }}</td>
                                    <td>
                                        @if ($row->production_id)
                                            <a href="{{ url('admin/production/show/' . $row->production_id) }}">{{ $row->production->production_no ?? $row->production_id }}</a>
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td>{{ $row->productVariation->name ?? '-' }}</td>
                                    <td>{{ $row->warehouse->name ?? '-' }}</td>
                                    <td>{{ decimal($row->base_quantity) }}</td>
                                    <td>{{ currency($row->total_cost ?? 0) }}</td>
                                </tr>
                                @empty
                                <tr><td colspan="6" class="text-center text-muted">{{ __('products.empty_consumption') }}</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="tab-pane fade" id="shares" role="tabpanel">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                        <h6 class="mb-0">{{ __('products.share_activity_heading') }}
                            <span class="badge bg-primary ms-2">{{ __('products.share_total', ['count' => $share_summary['total']]) }}</span>
                        </h6>
                        @if (!empty($share_summary['by_platform']))
                        <div class="d-flex flex-wrap gap-2">
                            @foreach ($share_summary['by_platform'] as $platform => $count)
                                <span class="badge bg-label-secondary">{{ ucwords(str_replace('_', ' ', $platform)) }}: {{ $count }}</span>
                            @endforeach
                        </div>
                        @endif
                    </div>
                    @if ($share_summary['log']->isEmpty())
                        <p class="text-muted mb-0">{{ __('products.share_activity_empty') }}</p>
                    @else
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm">
                                <thead class="table-light">
                                    <tr>
                                        <th>{{ __('products.share_col_user') }}</th>
                                        <th>{{ __('products.share_col_platform') }}</th>
                                        <th>{{ __('products.share_col_time') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($share_summary['log'] as $row)
                                    <tr>
                                        <td>{{ $row->customer->name ?? __('products.share_guest') }}</td>
                                        <td>{{ ucwords(str_replace('_', ' ', $row->platform)) }}</td>
                                        <td>{{ $row->date_created ? localDateTime($row->date_created) : '-' }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        {{ $share_summary['log']->fragment('shares')->links() }}
                    @endif
                </div>

                <div class="tab-pane fade" id="stock" role="tabpanel">
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered">
                            <thead>
                                <tr>
                                    <th>{{ __('common.date') }}</th>
                                    <th>{{ __('common.type') }}</th>
                                    <th>{{ __('products.variations') }}</th>
                                    <th>{{ __('common.warehouse') }}</th>
                                    <th>{{ __('common.quantity') }}</th>
                                    <th>{{ __('products.col_reference') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($movements as $row)
                                <tr>
                                    <td>{{ localDate($row->transaction_date ?? null) }}</td>
                                    <td>{{ ucwords(str_replace('_', ' ', $row->transaction_type ?? '')) }}</td>
                                    <td>{{ $row->productVariation->name ?? '-' }}</td>
                                    <td>{{ $row->warehouse->name ?? '-' }}</td>
                                    <td>{{ decimal($row->base_quantity ?? $row->quantity) }}</td>
                                    <td>{{ ucwords(str_replace('_', ' ', $row->reference_type ?? '')) }}{{ $row->reference_id ? ' · ' . $row->reference_id : '' }}</td>
                                </tr>
                                @empty
                                <tr><td colspan="6" class="text-center text-muted">{{ __('products.empty_stock') }}</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('js')
<script>
    (function () {
        if (typeof ApexCharts === 'undefined') {
            return;
        }

        var chartColors = [config.colors.primary, config.colors.success, config.colors.info, config.colors.warning, config.colors.danger, config.colors.secondary];

        @if ($charts['has_qty_trend'])
        try {
            new ApexCharts(document.querySelector('#productQtyTrendChart'), {
                series: [
                    { name: @json(__('products.chart_sold')), data: @json($charts['sold']) },
                    { name: @json(__('products.chart_purchased')), data: @json($charts['purchased']) }
                ],
                chart: { type: 'area', height: 300, toolbar: { show: false } },
                colors: [config.colors.primary, config.colors.success],
                dataLabels: { enabled: false },
                stroke: { curve: 'smooth', width: 2 },
                fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.4, opacityTo: 0.05, stops: [0, 90, 100] } },
                xaxis: { categories: @json($charts['months']), labels: { style: { colors: config.colors.axisColor } } },
                yaxis: { labels: { style: { colors: config.colors.axisColor } } },
                grid: { borderColor: config.colors.borderColor }
            }).render();
        } catch (e) {
            console.error('Product quantity trend chart failed to render:', e);
        }
        @endif

        function donutChart(elementId, data, height) {
            var el = document.querySelector(elementId);
            if (!el) { return; }
            try {
                new ApexCharts(el, {
                    series: Object.values(data),
                    labels: Object.keys(data),
                    chart: { type: 'donut', height: height || 260 },
                    colors: chartColors,
                    legend: { position: 'bottom', labels: { colors: config.colors.headingColor } },
                    dataLabels: { enabled: true, formatter: function (val) { return val.toFixed(1) + '%'; } }
                }).render();
            } catch (e) {
                console.error('Chart ' + elementId + ' failed to render:', e);
            }
        }

        @if (!empty($charts['stock_by_type']))
            donutChart('#productStockTypeChart', @json($charts['stock_by_type']), 240);
        @endif
        @if (!empty($charts['shares_by_platform']))
            donutChart('#productShareChart', @json($charts['shares_by_platform']), 240);
        @endif

        if (window.location.hash === '#shares') {
            var shareTab = document.querySelector('a[href="#shares"]');
            if (shareTab && typeof bootstrap !== 'undefined') {
                new bootstrap.Tab(shareTab).show();
            }
        }
    })();
</script>
@endpush
