<div class="row">
    <div class="col-lg-6 mb-4">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Customer Summary</h5>
                <a href="{{ route('users.index') }}" class="btn btn-sm btn-outline-primary">Manage Customers</a>
            </div>
            <div class="card-body">
                <div class="row text-center g-3">
                    <div class="col-4">
                        <div class="fs-4 fw-semibold">{{ number_format($parties['customers']['total']) }}</div>
                        <small class="text-muted">Total</small>
                    </div>
                    <div class="col-4">
                        <div class="fs-4 fw-semibold text-success">{{ number_format($parties['customers']['active']) }}</div>
                        <small class="text-muted">Active</small>
                    </div>
                    <div class="col-4">
                        <div class="fs-4 fw-semibold text-info">{{ number_format($parties['customers']['new_this_period']) }}</div>
                        <small class="text-muted">New This Month</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-6 mb-4">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Supplier Summary</h5>
                <a href="{{ route('supplier.index') }}" class="btn btn-sm btn-outline-primary">Manage Suppliers</a>
            </div>
            <div class="card-body">
                <div class="row text-center g-3">
                    <div class="col-4">
                        <div class="fs-4 fw-semibold">{{ number_format($parties['suppliers']['total']) }}</div>
                        <small class="text-muted">Total</small>
                    </div>
                    <div class="col-4">
                        <div class="fs-4 fw-semibold text-success">{{ number_format($parties['suppliers']['active']) }}</div>
                        <small class="text-muted">Active</small>
                    </div>
                    <div class="col-4">
                        <div class="fs-4 fw-semibold text-info">{{ number_format($parties['suppliers']['new_this_period']) }}</div>
                        <small class="text-muted">New This Month</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
