@extends('layouts.master')

@section('css')
<link href="{{ URL::asset('assets/libs/chartist/chartist.min.css')}}" rel="stylesheet" type="text/css" />
@include('layouts.datatable')
@endsection

@section('content')
<div class="row align-items-center">
    <div class="col-sm-6">
        <div class="page-title-box">
            <h4 class="font-size-18">Status Penjaga Pembayaran Perbelanjaan Sekolah</h4>
        </div>
    </div>
</div>
<div class="row">
    {{-- <div class="col-md-12">
        <div class="card card-primary">

            {{csrf_field()}}
            <div class="card-body">

                <div class="form-group">
                    <label>Nama Organisasi</label>
                    <select name="organization" id="organization" class="form-control">
                        <option value="" selected disabled>Pilih Organisasi</option>
                        @foreach($organization as $row)
                        <option value="{{ $row->id }}">{{ $row->nama }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label> Tarikh bermula</label>
                    <input type="date" id="fromTime" name="fromTime" min="{{$minDate}}" max="{{$maxDate}}" class="form-control">
                </div>
                <div class="form-group">
                    <label> Tarikh berakhir</label>
                    <input type="date" id="untilTime" name="untilTime" min="{{$minDate}}" max="{{$maxDate}}" class="form-control">
                </div>
                <div class="form-group">
                    <label> Jenis Perbelanjaan Berulangan</label>
                    <select name="recurring_type" id="recurring_type" class="form-control">
                        <option value="" selected disabled>Pilih Jenis</option>
                        @foreach($recurring_type as $row)
                        <option >{{ $row }}</option>
                        @endforeach
                    </select>                
                </div>
            </div>

        </div>
    </div> --}}

    <div class="col-md-12">
        <div class="card">
            <div class="card-header">Senarai Penjaga Pembayaran {{ $expenses->name }}</div>
            <div>
                <!-- need to add print pdf function -->
                <a style="margin: 19px;" href="#" class="btn btn-primary allBtn"> <i class="far fa-id-badge"></i> Semua</a>
                <a style="margin: 19px;" href="#"  class="btn btn-primary paidBtn"> <i class="far fa-id-badge"></i> Bayar</a>
                <a style="margin: 19px;" href="#"  class="btn btn-primary unpaidBtn"> <i class="far fa-id-badge"></i> Belum Bayar</a>
                <a style="margin: 19px; float: right;" href="#" class="btn btn-success" data-toggle="modal" data-target="#modelId1"> <i class="fas fa-plus"></i> Export</a>
            </div>

            <div class="card-body">

                @if(count($errors) > 0)
                <div class="alert alert-danger">
                    <ul>
                        @foreach($errors->all() as $error)
                        <li>{{$error}}</li>
                        @endforeach
                    </ul>
                </div>
                @endif
                @if(\Session::has('success'))
                <div class="alert alert-success">
                    <p>{{ \Session::get('success') }}</p>
                </div>
                @endif
                @if(\Session::has('fail'))
                <div class="alert alert-danger">
                    <p>{{ \Session::get('fail') }}</p>
                </div>
                @endif

                <div class="flash-message"></div>

                <div class="table-responsive">
                    <table id="parentsTable" class="table table-bordered table-striped dt-responsive nowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                        <thead>
                            <tr style="text-align:center">
                                <th> No. </th>
                                <th>Nama Penjaga</th>
                                <th>Nombor Telefon penjaga</th>
                                <th>Nama Pelajar</th>
                                <th>Nama Kelas</th>
                                <th>Status Pembayaran</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>

        <!-- export dorm modal-->
        <div class="modal fade" id="modelId1" tabindex="-1" role="dialog" aria-labelledby="modelTitleId" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Export Senarai Penjaga Pembayaran Perbelanjaan {{ $expenses->name }} Sekolah</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <!--exportfees-->
                    <form action="" method="post">
                        <div class="modal-body">
                            {{ csrf_field() }}
                            <div class="form-group">
                                <label>Organisasi</label>
                                <select name="organ" id="organ" class="form-control">
                                    @foreach($organization as $row)
                                    <option value="{{ $row->id }}" selected>{{ $row->nama }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="modal-footer">
                                <button id="buttonExport" type="submit" class="btn btn-primary">Export</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

      
      
    </div>
</div>


@endsection


@section('script')
<!-- Peity chart-->
<script src="{{ URL::asset('assets/libs/peity/peity.min.js')}}"></script>

<!-- Plugin Js-->
<script src="{{ URL::asset('assets/libs/chartist/chartist.min.js')}}"></script>

<script src="{{ URL::asset('assets/js/pages/dashboard.init.js')}}"></script>

<script>
    $(document).ready(function() {

        var parentsTable;
        var payStatus="all";
        expensesId = $(this).attr('id');

        $(document).on('click', '.allBtn', function() {
            $('#parentsTable').DataTable().destroy();
            payStatus = "all";
            
            fetch_data(expensesId);
        });

        $(document).on('click', '.paidBtn', function() {
            $('#parentsTable').DataTable().destroy();
            payStatus = "paid";
            
            fetch_data(expensesId);
        });

        $(document).on('click', '.unpaidBtn', function() {
            $('#parentsTable').DataTable().destroy();
            payStatus = "unpaid";
            
            fetch_data(expensesId);
        });

        function fetch_data(expensesId = '') {
            parentsTable = $('#parentsTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ route('recurring_fees.getpayStatusDatatable') }}",

                    data: {
                        expensesId: expensesId,
                        payStatus: payStatus
                    },
                    type: 'GET',
                   
                },
                
                'columnDefs': [{
                    "targets": [0], // your case first column
                    "className": "text-center",
                    "width": "2%"
                }, {
                    "targets": [1,3], 
                    "className": "text-center",
                    "width": "15%"
                }, {
                    "targets": [ 2,4,5],
                    "className": "text-center",
                }, ],
                order: [
                    [1, 'asc']
                ],
                columns: [{
                    "data": null,
                    searchable: false,
                    "sortable": false,
                    render: function(data, type, row, meta) {
                        return meta.row + meta.settings._iDisplayStart + 1;
                    }
                }, {
                    data: "parentName",
                    name: 'parentName'
                }, {
                    data: "parentTel",
                    name: 'parentTel',
                    orderable: false,

                }, {
                    data: "studentName",
                    name: 'studentName',
                }, {
                    data: "className",
                    name: 'className'
                }, {
                    data: "payStatus",
                    name: 'payStatus',
                    searchable: false,
                    orderable: false
                }]
            });

        }

       
        // csrf token for ajax
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        $('.alert').delay(3000).fadeOut();

    });
</script>
@endsection