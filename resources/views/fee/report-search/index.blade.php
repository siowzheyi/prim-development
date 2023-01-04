@extends('layouts.master')

@section('css')
<link href="{{ URL::asset('assets/libs/chartist/chartist.min.css')}}" rel="stylesheet" type="text/css" />
@include('layouts.datatable')

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
        <div class="card">
            {{-- <div class="card-header">List Of Applications</div> --}}
            <div class="card-body">
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

                    <div id="dkelas" class="form-group">
                        <label> Kelas</label>
                        <select name="classes" id="classes" class="form-control">
                            <option value="" disabled selected>Pilih Kelas</option>

                        </select>
                    </div>
                </div>

                <div>
                    <a style="margin: 19px;" class="btn btn-success"  data-toggle="modal" data-target="#modalByKelas"><i class="fas fa-plus"></i> Export</a>
                    <a style="margin: 1px;" id="btn-download" class="btn btn-success" data-toggle="modal" data-target="#modalByKelas2"><i class="fas fa-download"></i> Muat Turun PDF</a>
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

                <div class="table-responsive">
                    <table id="studentTable" class="table table-bordered table-striped dt-responsive nowrap"
                        style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                        <thead>
                            <tr style="text-align:center">
                                <th> No. </th>
                                <th>Nama Penuh</th>
                                <th>Jantina</th>
                                <th>Status Yuran</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>

    </div>
</div>

{{-- modal export yuran --}}
<div class="modal fade" id="modalByKelas" tabindex="-1" role="dialog" aria-labelledby="modelTitleId" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Export Yuran</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <form action="{{ route('exportStudentStatus') }}" method="post">
                <div class="modal-body">
                    {{ csrf_field() }}
                    <div class="form-group">
                        <label>Organisasi</label>
                        <select name="organExport" id="organExport" class="form-control">
                            <option value="" disabled selected>Pilih Organisasi</option>
                            @foreach($organization as $row)
                                <option value="{{ $row->id }}">{{ $row->nama }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Name Yuran</label>
                        <select name="yuranExport" id="yuranExport" class="form-control">

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

            <form action="{{ route('printAllYuranStatus') }}" method="post">
                <div class="modal-body">
                    {{ csrf_field() }}
                    <div class="form-group">
                        <label>Organisasi</label>
                        <select name="organPDF" id="organPDF" class="form-control">
                            <option value="" disabled selected>Pilih Organisasi</option>
                            @foreach($organization as $row)
                                <option value="{{ $row->id }}">{{ $row->nama }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- <div class="form-group">
                        <label>Kelas</label>
                        <select name="kelasPDF" id="kelasPDF" class="form-control">

                        </select>
                    </div> -->

                    <div class="form-group">
                        <label>Name Yuran</label>
                        <select name="yuranPDF" id="yuranPDF" class="form-control">

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
        
        var studentTable;

        if($("#organization").val() != ""){
            $("#organization").prop("selectedIndex", 1).trigger('change');
            fetchClass($("#organization").val());
        }

        
        // fetch_data();
        // alert($("#organization").val());

            function fetch_data(cid = '') {
                studentTable = $('#studentTable').DataTable({
                        processing: true,
                        serverSide: true,
                        ajax: {
                            url: "{{ route('fees.getStudentDatatableFees') }}",
                            data: {
                                classid: cid,
                                hasOrganization: true
                            },
                            type: 'GET',

                        },
                        'columnDefs': [{
                            "targets": [0], // your case first column
                            "className": "text-center",
                            "width": "2%"
                        },{
                            "targets": [2,3], // your case first column
                            "className": "text-center",
                        },],
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
                            name: 'nama'
                        }, {
                            data: "gender",
                            name: 'gender'
                        }, {
                            data: 'status',
                            name: 'status',
                            orderable: false,
                            searchable: false
                        },]
                });
            }

            $('#organization').change(function() {
               
                var organizationid    = $("#organization").val();
                var _token            = $('input[name="_token"]').val();

                fetchClass(organizationid);
                
            });

            function fetchClass(organizationid = ''){
                var _token            = $('input[name="_token"]').val();
                $.ajax({
                    url:"{{ route('student.fetchClass') }}",
                    method:"POST",
                    data:{ oid:organizationid,
                            _token:_token },
                    success:function(result)
                    {
                        $('#classes').empty();
                        $("#classes").append("<option value='' disabled selected> Pilih Kelas</option>");
                        jQuery.each(result.success, function(key, value){
                            // $('select[name="kelas"]').append('<option value="'+ key +'">'+value+'</option>');
                            $("#classes").append("<option value='"+ value.cid +"'>" + value.cname + "</option>");
                        });
                    }
                })
            }

            function downloadPDF(cid = ''){
                $.ajax({
                    url:"{{ route('fees.generatePDFByClass') }}",
                    method:"GET",
                    data:{ 
                        class_id:cid,
                    },
                    success:function(result)
                    {
                        
                    }
                })
            }

            $('#classes').change(function() {
                var organizationid    = $("#organization option:selected").val();

                var classid    = $("#classes option:selected").val();
                if(classid){
                    $('#studentTable').DataTable().destroy();
                    fetch_data( classid);
                    downloadPDF(classid);
                }
                // console.log(organizationid);
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