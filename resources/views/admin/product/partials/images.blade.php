<div class="d-flex gap-2 flex-wrap">
      @foreach($item->productImages as $img)
      <div class="position-relative">
            <img src="{{ asset($img->path) }}" width="40" height="40" class="rounded border">

            <button class="btn btn-danger btn-xs delete-image"
                  data-id="{{ $img->id }}">
                  x
            </button>
      </div>
      @endforeach

      <button class="btn btn-sm btn-primary add-image" data-id="{{ $item->product_id }}">
            +
      </button>
</div>