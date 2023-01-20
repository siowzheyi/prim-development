@extends('layouts.master')
@include('layouts.datatable')
@section('css')
<link href="{{ URL::asset('assets/css/required-asterick.css')}}" rel="stylesheet">
<link href="{{ URL::asset('assets/libs/chartist/chartist.min.css')}}" rel="stylesheet" type="text/css" />
@endsection

@section('content')

<div class="row align-items-center">
    <div class="col-sm-6">
        <div class="page-title-box">
            <h4 class="font-size-18">Laporan Kutipan Yuran</h4>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card card-primary">
            <input type="hidden" id="role" value="{{$role}}">
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
                    <label> Kategori </label>
                    <select name="fees" id="fees" class="form-control">
                        <option value="0" disabled selected>Pilih Kategori</option>
                    </select>
                </div>

                <div class="form-row">
                    <div class="form-group col-md-12">
                        <label>Tempoh</label>

                        <div class="input-daterange input-group" id="date">
                            <input type="text" class="form-control" name="date_started" id="date_started" placeholder="Tarikh Awal"
                                autocomplete="off" data-parsley-required-message="Sila masukkan tarikh awal"
                                data-parsley-errors-container=".errorMessage" required />
                            <input type="text" class="form-control" name="date_end" id="date_end" placeholder="Tarikh Akhir"
                                autocomplete="off" data-parsley-required-message="Sila masukkan tarikh akhir"
                                data-parsley-errors-container=".errorMessage" required />
                        </div>
                        <div class="errorMessage"></div>
                        <div class="errorMessage"></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="card">
            <div class="card-header">Senarai Yuran</div>

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
                                    <th>Yuran</th>
                                    <th>Kelas</th>
                                    <th>Harga ($)</th>
                                    <th>Bilangan Telah Bayar</th>
                                    <th>Jumlah Kutipan ($)</th>
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

            <form action="{{ route('exportCollectedYuran') }}" method="post">
                <div class="modal-body">
                    {{ csrf_field() }}
                    <div class="form-group">
                        <label class="control-label required">Organisasi</label>
                        <select name="organExport" id="organExport" class="form-control organ">
                            <option value="" disabled selected>Pilih Organisasi</option>
                            @foreach($organization as $row)
                                <option value="{{ $row->id }}">{{ $row->nama }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div id="dkelas1" class="form-group">
                        <label class="control-label required"> Kelas </label>
                        <select name="classesExport" id="classesExport" class="form-control classes">
                            <option value="0" disabled selected>Pilih Kelas</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="control-label required">Kategori</label>
                        <select name="yuranExport" id="yuranExport" class="form-control">
                            <option value="0" disabled selected>Pilih Kategori</option>
                        </select>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-12">
                            <label class="control-label required">Tempoh</label>

                            <div class="input-daterange input-group" id="dateExport">
                                <input type="date" class="form-control" name="startExport" id="startExport" placeholder="Tarikh Awal"
                                    autocomplete="off" data-parsley-required-message="Sila masukkan tarikh awal"
                                    data-parsley-errors-container=".errorMessage" onclick="this.showPicker()" required />
                                <input type="date" class="form-control" name="endExport" id="endExport" placeholder="Tarikh Akhir"
                                    autocomplete="off" data-parsley-required-message="Sila masukkan tarikh akhir"
                                    data-parsley-errors-container=".errorMessage" onclick="this.showPicker()" required />
                            </div>
                            <div class="errorMessage"></div>
                            <div class="errorMessage"></div>
                        </div>
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

            <form action="{{ route('printCollectedYuran') }}" method="post">
                <div class="modal-body">
                    {{ csrf_field() }}
                    <div class="form-group">
                        <label class="control-label required">Organisasi</label>
                        <select name="organPDF" id="organPDF" class="form-control organ">
                            <option value="" disabled selected>Pilih Organisasi</option>
                            @foreach($organization as $row)
                                <option value="{{ $row->id }}">{{ $row->nama }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div id="dkelas2" class="form-group">
                        <label class="control-label required"> Kelas </label>
                        <select name="classesPDF" id="classesPDF" class="form-control classes">
                            <option value="0" disabled selected>Pilih Kelas</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="control-label required">Kategori</label>
                        <select name="yuranPDF" id="yuranPDF" class="form-control">
                            <option value="0" disabled selected>Pilih Kategori</option>
                        </select>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-12">
                            <label class="control-label required">Tempoh</label>

                            <div class="input-daterange input-group" id="datePDF">
                                <input type="date" class="form-control" name="startPDF" id="startPDF" placeholder="Tarikh Awal"
                                    autocomplete="off" data-parsley-required-message="Sila masukkan tarikh awal"
                                    data-parsley-errors-container=".errorMessage" onclick="this.showPicker()" required />
                                <input type="date" class="form-control" name="endPDF" id="endPDF" placeholder="Tarikh Akhir"
                                    autocomplete="off" data-parsley-required-message="Sila masukkan tarikh akhir"
                                    data-parsley-errors-container=".errorMessage" onclick="this.showPicker()" required />
                            </div>
                            <div class="errorMessage"></div>
                            <div class="errorMessage"></div>
                        </div>
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
<script src="{{ URL::asset('assets/libs/dropzone/dropzone.min.js')}}"></script>
<script src="{{ URL::asset('assets/libs/chartist/chartist.min.js')}}"></script>
<script src="{{ URL::asset('assets/libs/parsleyjs/parsleyjs.min.js')}}"></script>
<script src="{{ URL::asset('assets/libs/bootstrap-datepicker/bootstrap-datepicker.min.js') }}" defer></script>

<script>
    $(document).ready(function(){

        $('#date').datepicker({
            toggleActive: true,
            todayHighlight:true,
            format: 'yyyy-mm-dd',
            // endDate: '0d',
            orientation: 'bottom'
        });

        var flag      = "";

        endPDF.value = startPDF.value = new Date().toISOString().split("T")[0];
        endExport.value = startExport.value = new Date().toISOString().split("T")[0];

        $('#startPDF').change(function() {
            if (startPDF.value > endPDF.value) {
                endPDF.value = startPDF.value;
            }
        });

        $('#endPDF').change(function() {
            if (startPDF.value > endPDF.value) {
                startPDF.value = endPDF.value;
            }
        });

        $('#startExport').change(function() {
            if (startExport.value > endExport.value) {
                endExport.value = startExport.value;
            }
        });

        $('#endExport').change(function() {
            if (startExport.value > endExport.value) {
                startExport.value = endExport.value;
            }
        });

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
                    url:"{{ route('fees.fetchCategorybyOrganId') }}",
                    method:"POST",
                    data:{ 
                        classid: classid,
                        oid : $("#organExport").val(),
                        _token: _token 
                    },
                    success:function(result)
                    {
                        $('#yuranExport').empty();
                        $('#yuranExport').append("<option value='' disabled selected> Pilih Kategori</option>");
                        if($("#classesExport option:selected").val() == "ALL")
                            $("#yuranExport").append("<option value='ALL'> Semua Kategori</option>");
                        jQuery.each(result.success, function(key, value){
                            if(value.category == "Kategory A")
                                flag = " - Mengikut keluarga";
                            else if(value.category == "Kategory B")
                                flag = " - Mengikut murid";
                            else if(value.category == "Kategory C")
                                flag = " - Pilihan";
                            else
                                flag = " - Asrama";
                            $('#yuranExport').append("<option value='"+ value.category +"'>" + value.category + flag + "</option>");
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
                    url:"{{ route('fees.fetchCategorybyOrganId') }}",
                    method:"POST",
                    data:{ 
                        classid: classid,
                        oid : $("#organPDF").val(),
                        _token: _token 
                    },
                    success:function(result)
                    {
                        $('#yuranPDF').empty();
                        $('#yuranPDF').append("<option value='' disabled selected> Pilih Kategori</option>");
                        if($("#classesPDF option:selected").val() == "ALL")
                            $("#yuranPDF").append("<option value='ALL'> Semua Kategori</option>");
                        jQuery.each(result.success, function(key, value){
                            if(value.category == "Kategory A")
                                flag = " - Mengikut keluarga";
                            else if(value.category == "Kategory B")
                                flag = " - Mengikut murid";
                            else if(value.category == "Kategory C")
                                flag = " - Pilihan";
                            else
                                flag = " - Asrama";
                            $('#yuranPDF').append("<option value='"+ value.category +"'>" + value.category + flag + "</option>");
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
                        if($('#role').val() == 1)
                            $(".classes").append("<option value='ALL'> Semua Kelas</option>");    
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
            
                $.ajax({
                    url:"{{ route('fees.fetchCategorybyOrganId') }}",
                    method:"POST",
                    data:{ 
                        classid: classid,
                        oid : $("#organization").val(),
                        _token: _token 
                    },
                    success:function(result)
                    {
                        $('#fees').empty();
                        $("#fees").append("<option value='0'> Pilih Kategori</option>");
                        if($("#classes option:selected").val() == "ALL")
                            $("#fees").append("<option value='ALL'> Semua Kategori</option>");
                        jQuery.each(result.success, function(key, value){
                            if(value.category == "Kategory A")
                                flag = " - Mengikut keluarga";
                            else if(value.category == "Kategory B")
                                flag = " - Mengikut murid";
                            else if(value.category == "Kategory C")
                                flag = " - Pilihan";
                            else
                                flag = " - Asrama";
                            $("#fees").append("<option value='"+ value.category +"'>" + value.category + flag + "</option>");
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
            $('#date_started').val("");
            $('#date_end').val(""); 
        });

        $('#yuranExport').change(function(){
            $('#startExport').val("");
            $('#endExport').val(""); 
        });
        
        $('#yuranPDF').change(function(){
            $('#startPDF').val("");
            $('#endPDF').val(""); 
        });

        $('#date_end').change(function(){
            if($('#fees').val() != 0 && $('#date_started').val != "" && $('#date_end').val != ""){
                $('#yuranTable').DataTable().destroy();
                var yuranTable = $('#yuranTable').DataTable({
                ordering: true,
                processing: true,
                serverSide: true,
                    ajax: {
                        url: "{{ route('fees.collectedFeeDatatable') }}",
                        type: 'GET',
                        data: {
                            feeid: $("#fees").val(),
                            classid: $(".classes").val(),
                            oid: $("#organization").val(),
                            date_started: $("#date_started").val(),
                            date_end: $("#date_end").val()
                        }
                    },
                    'columnDefs': [{
                            "targets": [0, 1, 2, 3, 4, 5], // your case first column
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
                        data: "name",
                        name: "name",
                        "width": "20%"
                    },
                    {
                        data: "class_name",
                        name: "class_name",
                        "width": "10%"
                    },{
                        data: "totalAmount",
                        name: "isamount",
                        "width": "10%"
                    },{
                        data: "total",
                        name: "total",
                        "width": "10%"
                    },{
                        data: "sum",
                        name: "sum",
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