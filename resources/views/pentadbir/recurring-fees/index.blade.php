@extends('layouts.master')

@section('css')
<link href="{{ URL::asset('assets/libs/chartist/chartist.min.css')}}" rel="stylesheet" type="text/css" />
@include('layouts.datatable')
@endsection

@section('content')
<div class="row align-items-center">
    <div class="col-sm-6">
        <div class="page-title-box">
            <h4 class="font-size-18">Perbelanjaan Sekolah</h4>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-md-12">
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
    </div>

    <div class="col-md-12">
        <div class="card">
            <div class="card-header">Senarai Perbelanjaan</div>
            <div>
                <!-- need to add print pdf function -->
                {{-- <a style="margin: 19px;" href="#" class="btn btn-primary" data-toggle="modal" data-target="#modelId"> <i class="fas fa-plus"></i> Import</a> --}}
                <a style="margin: 19px; float: right;" href="#" class="btn btn-success" data-toggle="modal" data-target="#modelId1"> <i class="fas fa-plus"></i> Export</a>
                <a style="margin: 19px; float: right;" href="{{ route('recurring_fees.create') }}" class="btn btn-primary"> <i class="fas fa-plus"></i> Tambah Perbelanjaan</a>
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
                    <table id="expensesTable" class="table table-bordered table-striped dt-responsive nowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                        <thead>
                            <tr style="text-align:center">
                                <th> No. </th>
                                <th>Nama Perbelanjaan</th>
                                <th>Diskripsi</th>
                                <th>Amaun</th>
                                <th>Tarikh Bermula</th>
                                <th>Tarikh Berakhir</th>
                                <th>Status Berulangan</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>

        {{-- confirmation delete modal --}}
        <div id="deleteConfirmationModal" class="modal fade" role="dialog">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">Padam Perbelanjaan</h4>
                    </div>
                    <div class="modal-body">
                        Adakah anda pasti?
                    </div>
                    <div class="modal-footer">
                        <button type="button" data-dismiss="modal" class="btn btn-primary" id="delete" name="delete">Padam</button>
                        <button type="button" data-dismiss="modal" class="btn">Batal</button>
                    </div>
                </div>
            </div>
        </div>
        {{-- end confirmation delete modal --}}

        <!-- export dorm modal-->
        <div class="modal fade" id="modelId1" tabindex="-1" role="dialog" aria-labelledby="modelTitleId" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Export Perbelanjaan Sekolah</h5>
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

        var expensesTable;

        if ($("#organization").val() != "") {
            $("#organization").prop("selectedIndex", 1).trigger('change');
            fetch_data($("#organization").val());
        }

        function fetch_data(oid = '') {
            expensesTable = $('#expensesTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ route('recurring_fees.getExpensesDatatable') }}",

                    data: {
                        oid: oid,
                        hasOrganization: true,
                        recurring_type: $("#recurring_type option:selected").val(),
                        fromTime: $('#fromTime').val(),
                        untilTime: $('#untilTime').val(),
                    },
                    type: 'GET',
                   
                },
                
                'columnDefs': [{
                    "targets": [0], // your case first column
                    "className": "text-center",
                    "width": "2%"
                }, {
                    "targets": [1,4], 
                    "className": "text-center",
                    "width": "15%"
                }, {
                    "targets": [ 2,3,5,6,7],
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
                    data: "name",
                    name: 'name',
                }, {
                    data: "description",
                    name: 'description',
                    searchable: false,
                    orderable: false,

                }, {
                    data: "amount",
                    name: 'amount',
                    searchable: false
                }, {
                    data: "start_date",
                    name: 'start_date',
                    searchable: false
                }, {
                    data: "end_date",
                    name: 'end_date',
                    searchable: false
                }, {
                    data: "status_recurring",
                    name: 'status_recurring',
                    searchable: false,
                    orderable: false
                }, {
                    data: 'action',
                    name: 'action',
                    orderable: false,
                    searchable: false
                }, ]
            });

        }

        $('#organization').change(function() {
            var organizationid = $("#organization option:selected").val();
            $('#expensesTable').DataTable().destroy();
            fetch_data(organizationid);
        });

        $('#recurring_type').change(function() {
            var organizationid = $("#organization option:selected").val();

            $('#expensesTable').DataTable().destroy();

            fetch_data(organizationid);
        });

        $('#fromTime').change(function() {
            var organizationid = $("#organization option:selected").val();

            $('#expensesTable').DataTable().destroy();

            fetch_data(organizationid);
        });

        $('#untilTime').change(function() {
            var organizationid = $("#organization option:selected").val();

            $('#expensesTable').DataTable().destroy();

            fetch_data(organizationid);
        });

        // csrf token for ajax
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        var expenses_id;

        $(document).on('click', '.destroyExpenses', function() {
            expenses_id = $(this).attr('id');
            $('#deleteConfirmationModal').modal('show');
        });

       

        $('#delete').click(function() {
            $.ajax({
                type: 'POST',
                dataType: 'html',
                data: {
                    "_token": "{{ csrf_token() }}",
                    _method: 'DELETE'
                },
                url: "/recurring_fees/" + expenses_id,
                success: function(data) {
                    setTimeout(function() {
                        $('#confirmModal').modal('hide');
                    }, 2000);
                    console.log('it works');

                    $('div.flash-message').html(data);

                    expensesTable.ajax.reload();
                },
                error: function(data) {
                    $('div.flash-message').html(data);
                    console.log("it doesn't Works");

                }
            })
        });

        

        $('.alert').delay(3000).fadeOut();

    });
</script>
@endsection