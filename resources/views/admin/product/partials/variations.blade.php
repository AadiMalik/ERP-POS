<table class="table table-bordered table-sm">
      <thead>
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
                  <th>Barcode</th>
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
                  <td>{{ $var->name }}</td>
                  <td>{{ $var->sku }}</td>
                  <td>{{ $var->barcode }}</td>
                  <td>{{ $var->unit->name??'' }}</td>
                  <td>{{ $var->purchaseUnit->name??'' }}</td>
                  <td>{{ $var->saleUnit->name??'' }}</td>
                  <td>{{ $var->purchase_price }}</td>
                  <td>{{ $var->sale_price }}</td>
                  <td>{{ $var->minimum_stock }}</td>
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
                        <a class='btn btn-icon btn-outline-danger delete-variation'
                              data-id='{{ $var->product_variation_id }}'>
                              <i class='fa fa-trash'></i>
                        </a>
                  </td>
            </tr>
            @endforeach
      </tbody>
</table>