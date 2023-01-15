@extends('layouts.master')

@section('css')
<link href="{{ URL::asset('assets/libs/chartist/chartist.min.css')}}" rel="stylesheet" type="text/css" />
<link rel="stylesheet" href="{{ URL::asset('assets/css/datatable.css')}}">
@include('layouts.datatable')
@endsection

@section('content')
<div class="row align-items-center">
  <div class="col-sm-6">
    <div class="page-title-box">
      <h4 class="font-size-18">Sejarah Bayaran</h4>
      <!-- <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item active">Welcome to Veltrix Dashboard</li>
            </ol> -->
    </div>
  </div>
</div>
<div class="row">

  <div class="col-md-12">
    <div class="card card-primary">

      @if(count($errors) > 0)
      <div class="alert alert-danger">
        <ul>
          @foreach($errors->all() as $error)
          <li>{{$error}}</li>
          @endforeach
        </ul>
      </div>
      @endif

      {{csrf_field()}}
      <div class="card-body">
        <div class="form-group">
          <label>Nama Organisasi</label>
          <select name="organization" id="organization" class="form-control">
            <option value="" selected>Pilih Organisasi</option>
            @foreach($organization as $row)
            <option value="{{ $row->id }}">{{ $row->nama }}</option>
            @endforeach
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

      {{-- <div class="">
        <button onclick="filter()" style="float: right" type="submit" class="btn btn-primary"><i
            class="fa fa-search"></i>
          Tapis</button>
      </div> --}}

    </div>
  </div>

  <div class="col-md-12">
    <div class="card">
      <div class="card-header">Senarai Resit</div>

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
      <div class="card-body">
        <div class="table-responsive">
          <table id="feesReceiptDataTable" class="table table-bordered table-striped dt-responsive"
            style="border-collapse: collapse; border-spacing: 0; width: 100%;">
            <thead>
              <tr style="text-align:center">
                <th> No. </th>
                <th> No Resit </th>
                <th> Tarikh Pembayaran </th>
                <th> Jumlah </th>
                <th> Tindakan </th>
              </tr>
            </thead>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

@endsection


@section('script')

<!-- Plugin Js-->
<script src="{{ URL::asset('assets/libs/dropzone/dropzone.min.js')}}"></script>
<script src="{{ URL::asset('assets/libs/chartist/chartist.min.js')}}"></script>
<script src="{{ URL::asset('assets/libs/parsleyjs/parsleyjs.min.js')}}"></script>
<script src="{{ URL::asset('assets/js/pages/dashboard.init.js')}}"></script>
<script src="{{ URL::asset('assets/libs/inputmask/inputmask.min.js')}}"></script>
<script src="{{ URL::asset('assets/libs/bootstrap-datepicker/bootstrap-datepicker.min.js') }}" defer></script>

<script>
    $( document ).ready(function() {

        $('.form-validation').parsley();
        $(".input-mask").inputmask();

        var today = new Date();
        $('.yearhide').hide();
        $('.cbhide').hide();

        $('#date').datepicker({
            toggleActive: true,
            todayHighlight:true,
            format: 'yyyy-mm-dd',
            orientation: 'bottom'
          });

        fetch_data();

        var receiptTable;

        function fetch_data(oid = '') {   
            receiptTable = $('#feesReceiptDataTable').DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: {
                        url: "{{ route('fees.getFeesReceiptDataTable') }}",
                        data: {
                            oid: oid,
                            date_started: $("#date_started").val(),
                            date_end: $("#date_end").val()
                        },
                        type: 'GET',

                    },
                    'columnDefs': [{
                        "targets": [0], // your case first column
                        "className": "text-center",
                        "width": "2%"
                    },{
                        "targets": [2, 3, 4], // your case first column
                        "className": "text-center",
                    },],
                    columns: [{
                        "data": null,
                        searchable: false,
                        "sortable": false,
                        render: function (data, type, row, meta) {
                            return meta.row + meta.settings._iDisplayStart + 1;
                        }
                    }, {
                        data: "desc",
                        name: 'desc',
                        "width": "20%"
                    },{
                        data: "date",
                        name: 'date',
                        "width": "30%"
                    },{
                        data: "amount",
                        name: 'amount',
                        "width": "20%"
                    }, {
                        data: 'action',
                        name: 'action',
                        orderable: false,
                        searchable: false,
                        "className": "text-center",
                        "width": "20%"
                    },]
            });
        }

        $('#date_end').change(function() {
            if($("#organization option:selected").val() != "" && $("#date_started").val()){
              var organizationid = $("#organization option:selected").val();
              $('#feesReceiptDataTable').DataTable().destroy();
              console.log(organizationid);
              fetch_data(organizationid);
            }
        });

        // csrf token for ajax
        $.ajaxSetup({
                headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });
    });
</script>
@endsection