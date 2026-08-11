<div class="table-responsive" style="max-height:65vh;">
      <table class="table table-bordered table-sm align-middle mb-0">
            <thead class="table-light" style="position:sticky; top:0; z-index:1;">
                  <tr>
                        <th colspan="3" class="text-center">
                              <b>Information</b>
                        </th>
                        <th colspan="3" class="text-center">
                              <b>Unit Info.</b>
                        </th>
                        <th colspan="2" class="text-center">
                              <b>Price Info.</b>
                        </th>
                        <th colspan="3" class="text-center">
                              <b>Stock Info.</b>
                        </th>
                        <th colspan="3" class="text-center">
                              <b>Other</b>
                        </th>
                  </tr>
                  <tr>
                        <th>Name</th>
                        <th>SKU</th>
                        <th style="min-width:110px;">Barcode / QR</th>
                        <th>Basic</th>
                        <th>Purchase</th>
                        <th>Sale</th>
                        <th>Purchase</th>
                        <th>Sale</th>
                        <th>Min Stock</th>
                        <th>Batch</th>
                        <th>Expiry</th>
                        <th>Attributes</th>
                        <th>Status</th>
                        <th>Action</th>
                  </tr>
            </thead>

            <tbody>
                  @foreach($variations as $var)
                  <tr>
                        <td class="text-nowrap">{{ $var->name }}</td>
                        <td class="text-nowrap">{{ $var->sku }}</td>
                        <td>
                              <div class="d-flex align-items-center gap-1">
                                    @if ($var->barcode)
                                          <img src="{{ url('admin/barcode/render/' . $var->product_variation_id) }}?type=barcode"
                                                alt="barcode" title="{{ $var->barcode }}" style="height:26px;">
                                    @endif
                                    @if ($var->qr_code)
                                          <img src="{{ url('admin/barcode/render/' . $var->product_variation_id) }}?type=qr"
                                                alt="qr code" title="QR Code" style="height:30px; width:30px;">
                                    @endif
                                    @if (!$var->barcode && !$var->qr_code)
                                          <span class="text-muted">&mdash;</span>
                                    @endif
                              </div>
                              @if ($var->barcode)
                                    <div class="small text-muted text-nowrap">{{ $var->barcode }}</div>
                              @endif
                        </td>
                        <td class="text-nowrap">{{ $var->unit->name??'' }}</td>
                        <td class="text-nowrap">{{ $var->purchaseUnit->name??'' }}</td>
                        <td class="text-nowrap">{{ $var->saleUnit->name??'' }}</td>
                        <td class="text-nowrap">{{ $var->purchase_price }}</td>
                        <td class="text-nowrap">{{ $var->sale_price }}</td>
                        <td class="text-nowrap">{{ $var->minimum_stock }}</td>
                        <td>
                              <span class="badge bg-info">
                                    {{ $var->track_batch ? 'Yes' : 'No' }}
                              </span>
                        </td>
                        <td>
                              <span class="badge bg-warning">
                                    {{ $var->track_expiry ? 'Yes' : 'No' }}
                              </span>
                        </td>
                        <td>
                              @foreach($var->attributes as $attr)
                              <span class="badge bg-primary me-1">
                                    {{ $attr->name }} : {{ $attr->value }}
                              </span>
                              @endforeach
                        </td>

                        <td>
                              <div class="form-check form-switch mb-0">
                                    <input
                                          class="form-check-input toggle-variation"
                                          type="checkbox"
                                          data-id="{{ $var->product_variation_id }}" {{ $var->status ? 'checked' : '' }}>
                              </div>
                        </td>
                        <td>
                              <div class="btn-group btn-group-sm" role="group">
                                    <a class='btn btn-outline-secondary regenerate-barcode' title="Regenerate barcode/QR"
                                          data-id='{{ $var->product_variation_id }}'
                                          data-is-manual='{{ $var->barcode_is_manual ? 1 : 0 }}'>
                                          <i class='fa fa-barcode'></i>
                                    </a>
                                    <a class='btn btn-outline-danger delete-variation' title="Delete"
                                          data-id='{{ $var->product_variation_id }}'>
                                          <i class='fa fa-trash'></i>
                                    </a>
                              </div>
                        </td>
                  </tr>
                  @endforeach
            </tbody>
      </table>
</div>
