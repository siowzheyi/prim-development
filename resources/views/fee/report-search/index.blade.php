@extends('layouts.master')
@include('layouts.datatable')
@section('css')
<link href="{{ URL::asset('assets/libs/chartist/chartist.min.css')}}" rel="stylesheet" type="text/css" />
@endsection

@section('content')

<div class="row align-items-center">
    <div class="col-sm-6">
        <div class="page-title-box">
            <h4 class="font-size-18">Carian Laporan</h4>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card card-primary">
            <div class="card-body">

                <div class="form-group">
                    <label>Organisasi</label>
                    <select name="organization" id="organization" class="form-control organ">
                        <option value="" selected>Pilih Organisasi</option>
                        @foreach($organization as $row)
                        <option value="{{ $row->id }}">{{ $row->nama }}</option>
                        @endforeach
                    </select>
                </div>

                <div id="dkelas" class="form-group">
                    <label> Kelas </label>
                    <select name="classes" id="classes" class="form-control classes">
                        <option value="0" disabled selected>Pilih Kelas</option>
                    </select>
                </div>

                <div id="yuran" class="form-group">
                    <label> Yuran </label>
                    <select name="fees" id="fees" class="form-control">
                        <option value="0" disabled selected>Pilih Yuran</option>
                    </select>
                </div>

            </div>
        </div>
        <div class="card">
            <div class="card-header">Senarai Pelajar</div>
            
            <div class="col-md-12">
                <a style="margin: 19px;" class="btn btn-success"  data-toggle="modal" data-target="#modalByYuran"><i class="fas fa-plus"></i> Export</a>
                <a style="margin: 1px;" href="#" class="btn btn-success " data-toggle="modal" data-target="#modalByYuran2"> <i class="fa fa-download"></i> Cetak</a>
            </div>

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
            
            <div class="flash-message"></div>

            <!-- <div class="col-md-12"> -->
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="yuranTable" class="table table-bordered table-striped dt-responsive wrap"
                            style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                            <thead>
                                <tr style="text-align:center">
                                    <th>No</th>
                                    <th>Nama Murid</th>
                                    <th>Jantina</th>
                                    <th>Status Pembayaran</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            <!-- </div> -->
        </div>
    </div>
</div>

{{-- modal export yuran --}}
<div class="modal fade" id="modalByYuran" tabindex="-1" role="dialog" aria-labelledby="modelTitleId" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Export Yuran</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <form action="{{ route('exportClassYuranStatus') }}" method="post">
                <div class="modal-body">
                    {{ csrf_field() }}
                    <div class="form-group">
                        <label>Organisasi</label>
                        <select name="organExport" id="organExport" class="form-control organ">
                            <option value="" disabled selected>Pilih Organisasi</option>
                            @foreach($organization as $row)
                                <option value="{{ $row->id }}">{{ $row->nama }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div id="dkelas1" class="form-group">
                        <label> Kelas </label>
                        <select name="classesExport" id="classesExport" class="form-control classes">
                            <option value="0" disabled selected>Pilih Kelas</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Name Yuran</label>
                        <select name="yuranExport" id="yuranExport" class="form-control">
                            <option value="0" disabled selected>Pilih Yuran</option>
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

{{-- modal print yuran --}}
<div class="modal fade" id="modalByYuran2" tabindex="-1" role="dialog" aria-labelledby="modelTitleId" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Print Yuran</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <form action="{{ route('printClassYuranStatus') }}" method="post">
                <div class="modal-body">
                    {{ csrf_field() }}
                    <div class="form-group">
                        <label>Organisasi</label>
                        <select name="organPDF" id="organPDF" class="form-control organ">
                            <option value="" disabled selected>Pilih Organisasi</option>
                            @foreach($organization as $row)
                                <option value="{{ $row->id }}">{{ $row->nama }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div id="dkelas2" class="form-group">
                        <label> Kelas </label>
                        <select name="classesPDF" id="classesPDF" class="form-control classes">
                            <option value="0" disabled selected>Pilih Kelas</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Name Yuran</label>
                        <select name="yuranPDF" id="yuranPDF" class="form-control">
                            <option value="0" disabled selected>Pilih Yuran</option>
                        </select>
                    </div>

                    <div class="modal-footer">
                        <button id="buttonPrint" type="submit" class="btn btn-primary">Print</button>
                    </div>
                </div>
            </form>
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
    $(document).ready(function(){
        $("#organPDF").prop("selectedIndex", 1).trigger('change');
        $("#organExport").prop("selectedIndex", 1).trigger('change');

        function fetchClass(classid = '', yuranId = ''){
            var _token = $('input[name="_token"]').val();
            $.ajax({
                url:"{{ route('fees.fetchYuranByOrganId') }}",
                method:"POST",
                data:{ oid: $(".organ").val(),
                        cid:classid,
                        _token:_token },
                success:function(result)
                {
                    $(yuranId).empty();
                    $(yuranId).append("<option value='' disabled selected> Pilih Kelas</option>");
                    jQuery.each(result.success, function(key, value){
                        $(yuranId).append("<option value='"+ value.id +"'>" + value.name + "</option>");
                    });
                }
            })
        }

        $('#classesExport').change(function(){
            if($(this).val() != '')
            {
                var classid   = $("#classesExport option:selected").val();
                var _token    = $('input[name="_token"]').val();
            
                console.log(classid);
                $.ajax({
                    url:"{{ route('fees.fetchYuran') }}",
                    method:"POST",
                    data:{ 
                        classid: classid,
                        oid : $("#organExport").val(),
                        _token: _token 
                    },
                    success:function(result)
                    {
                        $('#yuranExport').empty();
                        $('#yuranExport').append("<option value='' disabled selected> Pilih Yuran</option>");
                        jQuery.each(result.success, function(key, value){
                            $('#yuranExport').append("<option value='"+ value.id +"'>" + value.name + "</option>");
                        });
                    }
                })
            }
        });

        $('#classesPDF').change(function(){
            if($(this).val() != '')
            {
                var classid   = $("#classesPDF option:selected").val();
                var _token    = $('input[name="_token"]').val();
            
                console.log(classid);
                $.ajax({
                    url:"{{ route('fees.fetchYuran') }}",
                    method:"POST",
                    data:{ 
                        classid: classid,
                        oid : $("#organPDF").val(),
                        _token: _token 
                    },
                    success:function(result)
                    {
                        $('#yuranPDF').empty();
                        $('#yuranPDF').append("<option value='' disabled selected> Pilih Yuran</option>");
                        jQuery.each(result.success, function(key, value){
                            $('#yuranPDF').append("<option value='"+ value.id +"'>" + value.name + "</option>");
                        });
                    }
                })
            }
        });

        $('#organExport').change(function() {
            
            var organizationid    = $("#organExport").val();
            var _token            = $('input[name="_token"]').val();
            fetch_data(organizationid);
        });

        $('#organPDF').change(function() {
            var organizationid    = $("#organPDF").val();
            var _token            = $('input[name="_token"]').val();
            fetch_data(organizationid);
        });
        
        if($("#organization").val() == ""){
            $("#organization").prop("selectedIndex", 1).trigger('change');
            fetch_data($("#organization").val());
        }

        function fetch_data(oid = ''){ 
            var _token = $('input[name="_token"]').val();
                $.ajax({
                    url:"{{ route('fees.fetchClassForCateYuran') }}",
                    method:"POST",
                    data:{ oid:oid,
                            _token:_token },
                    success:function(result)
                    {
                        $('.classes').empty();
                        $(".classes").append("<option value='0'> Pilih Kelas</option>");    
                        jQuery.each(result.success, function(key, value){
                            $(".classes").append("<option value='"+ value.cid +"'>" + value.cname + "</option>");
                        });
                    }   
                })    
        }

        $('#classes').change(function(){
            if($(this).val() != '')
            {
                var classid   = $("#classes option:selected").val();
                var _token    = $('input[name="_token"]').val();
            
                console.log(classid);
                $.ajax({
                    url:"{{ route('fees.fetchYuran') }}",
                    method:"POST",
                    data:{ 
                        classid: classid,
                        oid : $("#organization").val(),
                        _token: _token 
                    },
                    success:function(result)
                    {
                        $('#fees').empty();
                        $("#fees").append("<option value='0'> Pilih Yuran</option>");
                        
                        jQuery.each(result.success, function(key, value){
                            $("#fees").append("<option value='"+ value.id +"'>" + value.name + "</option>");
                        });
                    }
                })
            }
        });

        $('#organization').change(function(){
            if($(this).val() != '')
            {
                fetch_data($("#organization").val());
            }
        });
        
        $('#fees').change(function(){
            if($(this).val() != 0){
                $('#yuranTable').DataTable().destroy();

                var yuranTable = $('#yuranTable').DataTable({
                    ordering: true,
                    processing: true,
                    serverSide: true,
                        ajax: {
                            url: "{{ route('fees.debtDatatable') }}",
                            type: 'GET',
                            data: {
                                feeid: $("#fees").val(),
                                classid: $(".classes").val()
                            }
                        },
                        'columnDefs': [{
                              "targets": [0, 1, 2, 3], // your case first column
                              "className": "text-center",
                              "width": "2%"
                          }],
                        order: [
                            [1, 'asc']
                        ],
                        columns: [{
                            "data": null,
                            searchable: false,
                            "sortable": false,
                            render: function (data, type, row, meta) {
                                return meta.row + meta.settings._iDisplayStart + 1;
                            }
                        }, {
                            data: "nama",
                            name: "nama",
                            "width": "20%"
                        },
                        {
                            data: "gender",
                            name: "gender",
                            "width": "10%"
                        },{
                            data: "status",
                            name: "status",
                            "width": "10%"
                        }]
                  });
            }
        });

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