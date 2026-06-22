<div class="d-flex flex-wrap gap-1 align-items-center">

    @forelse ($images as $image)
        <div class="position-relative img-thumb-wrap"
             style="width:44px; height:44px; flex-shrink:0;">

            <img src="{{ $image->image_url }}"
                 class="rounded {{ $image->is_default ? 'border border-warning border-2' : 'border set-default-btn' }}"
                 data-id="{{ $image->product_image_id }}"
                 style="width:44px; height:44px; object-fit:cover;"
                 title="{{ $image->is_default ? 'Default' : 'Click to set default' }}">

            {{-- Default star badge --}}
            @if($image->is_default)
                <span class="position-absolute d-flex align-items-center justify-content-center"
                      style="bottom:-4px; left:-4px; background:#f59e0b;
                             border-radius:50%; width:16px; height:16px; z-index:2;">
                    <i class="fa fa-star" style="font-size:7px; color:#fff;"></i>
                </span>
            @endif

            {{-- Delete X — hover pe dikhe --}}
            <button class="delete-image position-absolute border-0 d-flex align-items-center
                           justify-content-center bg-danger img-delete-btn"
                    data-id="{{ $image->product_image_id }}"
                    style="top:-5px; right:-5px; width:16px; height:16px;
                           border-radius:50%; cursor:pointer; padding:0; line-height:1;
                           opacity:0; transition:opacity 0.2s ease; z-index:3;">
                <i class="fa fa-times" style="font-size:8px; color:#fff; pointer-events:none;"></i>
            </button>
        </div>
    @empty
        <span class="text-muted" style="font-size:11px;">No images</span>
    @endforelse

    {{-- + Manage images --}}
    <button class="btn btn-sm btn-outline-primary add-image d-flex align-items-center justify-content-center"
            data-id="{{ $images[0]->product_id ?? '' }}"
            style="width:40px; height:40px; font-size:18px; flex-shrink:0; border-radius:6px;">
        <i class="fa fa-plus" style="pointer-events:none;"></i>
    </button>
</div>

<style>
    .img-thumb-wrap:hover .img-delete-btn {
        opacity: 1 !important;
    }
</style>