@extends('layouts.app')
@section('css')
@endsection
@section('content')
    <!-- ========== table components start ========== -->
    <section class="table-components">
        <div class="container-fluid">

            <!-- ========== tables-wrapper start ========== -->
            <div class="tables-wrapper mt-50">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="card-style mb-30">
                            <h6 class="mb-10">Data Table</h6>
                            <p class="text-sm mb-20">
                                For basic styling—light padding and only horizontal
                                dividers—use the class table.
                            </p>
                            <div class="table-wrapper table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th class="lead-info">
                                                <h6>Lead</h6>
                                            </th>
                                            <th class="lead-email">
                                                <h6>Email</h6>
                                            </th>
                                            <th class="lead-phone">
                                                <h6>Phone No</h6>
                                            </th>
                                            <th class="lead-company">
                                                <h6>Company</h6>
                                            </th>
                                            <th>
                                                <h6>Action</h6>
                                            </th>
                                        </tr>
                                        <!-- end table row-->
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td class="min-width">
                                                <div class="lead">
                                                    <div class="lead-image">
                                                        <img src="assets/images/lead/lead-1.png" alt="" />
                                                    </div>
                                                    <div class="lead-text">
                                                        <p>Courtney Henry</p>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="min-width">
                                                <p><a href="#0">yourmail@gmail.com</a></p>
                                            </td>
                                            <td class="min-width">
                                                <p>(303)555 3343523</p>
                                            </td>
                                            <td class="min-width">
                                                <p>UIdeck digital agency</p>
                                            </td>
                                            <td>
                                                <div class="action">
                                                    <button class="text-danger">
                                                        <i class="lni lni-trash-can"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                        <!-- end table row -->
                                        <tr>
                                            <td class="min-width">
                                                <div class="lead">
                                                    <div class="lead-image">
                                                        <img src="assets/images/lead/lead-2.png" alt="" />
                                                    </div>
                                                    <div class="lead-text">
                                                        <p>John Doe</p>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="min-width">
                                                <p><a href="#0">yourmail@gmail.com</a></p>
                                            </td>
                                            <td class="min-width">
                                                <p>(303)555 3343523</p>
                                            </td>
                                            <td class="min-width">
                                                <p>Graygrids digital agency</p>
                                            </td>
                                            <td>
                                                <div class="action">
                                                    <button class="text-danger">
                                                        <i class="lni lni-trash-can"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                        <!-- end table row -->
                                        <tr>
                                            <td class="min-width">
                                                <div class="lead">
                                                    <div class="lead-image">
                                                        <img src="assets/images/lead/lead-3.png" alt="" />
                                                    </div>
                                                    <div class="lead-text">
                                                        <p>David Smith</p>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="min-width">
                                                <p><a href="#0">yourmail@gmail.com</a></p>
                                            </td>
                                            <td class="min-width">
                                                <p>(303)555 3343523</p>
                                            </td>
                                            <td class="min-width">
                                                <p>Abc agency</p>
                                            </td>
                                            <td>
                                                <div class="action">
                                                    <button class="text-danger">
                                                        <i class="lni lni-trash-can"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                        <!-- end table row -->
                                        <tr>
                                            <td class="min-width">
                                                <div class="lead">
                                                    <div class="lead-image">
                                                        <img src="assets/images/lead/lead-4.png" alt="" />
                                                    </div>
                                                    <div class="lead-text">
                                                        <p>Jonathon</p>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="min-width">
                                                <p><a href="#0">yourmail@gmail.com</a></p>
                                            </td>
                                            <td class="min-width">
                                                <p>(303)555 3343523</p>
                                            </td>
                                            <td class="min-width">
                                                <p>Creative IT Agency</p>
                                            </td>
                                            <td>
                                                <div class="action">
                                                    <button class="text-danger">
                                                        <i class="lni lni-trash-can"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                        <!-- end table row -->
                                        <tr>
                                            <td>
                                                <div class="lead">
                                                    <div class="lead-image">
                                                        <img src="assets/images/lead/lead-5.png" alt="" />
                                                    </div>
                                                    <div class="lead-text">
                                                        <p>Anna Lee</p>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <p><a href="#0">yourmail@gmail.com</a></p>
                                            </td>
                                            <td>
                                                <p>(303)555 3343523</p>
                                            </td>
                                            <td>
                                                <p>Halal It agency</p>
                                            </td>
                                            <td>
                                                <div class="action">
                                                    <button class="text-danger">
                                                        <i class="lni lni-trash-can"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                        <!-- end table row -->
                                        <tr>
                                            <td>
                                                <div class="lead">
                                                    <div class="lead-image">
                                                        <img src="assets/images/lead/lead-6.png" alt="" />
                                                    </div>
                                                    <div class="lead-text">
                                                        <p>Gray Simon</p>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <p><a href="#0">yourmail@gmail.com</a></p>
                                            </td>
                                            <td>
                                                <p>(303)555 3343523</p>
                                            </td>
                                            <td>
                                                <p>DesignCourse</p>
                                            </td>
                                            <td>
                                                <div class="action">
                                                    <button class="text-danger">
                                                        <i class="lni lni-trash-can"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                        <!-- end table row -->
                                    </tbody>
                                </table>
                                <!-- end table -->
                            </div>
                        </div>
                        <!-- end card -->
                    </div>
                    <!-- end col -->
                </div>
                <!-- end row -->
            </div>
            <!-- ========== tables-wrapper end ========== -->
        </div>
        <!-- end container -->
    </section>
    <!-- ========== table components end ========== -->
@endsection
@section('js')
@endsection
