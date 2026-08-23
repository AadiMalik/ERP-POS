{{-- Reusable inline "+" trigger for master-data dropdowns.
     Params: $permission (create permission name), $modal (target modal id, no '#'), $label (entity name for the tooltip). --}}
@canAccess($permission)
<button type="button" class="btn btn-sm btn-outline-primary quick-add-btn py-0 px-1"
    data-bs-toggle="modal" data-bs-target="#{{ $modal }}" title="Add New {{ $label }}">
    <i class="fa fa-plus"></i>
</button>
@endcanAccess
