@extends('layouts.master')

@section('css')
<link href="{{ URL::asset('assets/libs/chartist/chartist.min.css')}}" rel="stylesheet" type="text/css" />
@endsection

@section('content')
<div class="row align-items-center">
    <div class="col-sm-6">
        <div class="page-title-box">
            <h4 class="font-size-18">Perbelanjaan</h4>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item active">Perbelanjaan >> Tambah Perbelanjaan</li>
            </ol>
        </div>
    </div>
</div>
<div class="row">
    <div class="card col-md-12">

        @if(count($errors) > 0)
        <div class="alert alert-danger">
            <ul>
                @foreach($errors->all() as $error)
                <li>{{$error}}</li>
                @endforeach
            </ul>
        </div>
        @endif
        <form method="post" action="{{ route('recurring_fees.store') }}" enctype="multipart/form-data">
            {{csrf_field()}}
            <div class="card-body">

                <div class="form-group">
                    <label>Nama Organisasi</label>
                    <select name="organization" id="organization" class="form-control">
                        <option value="" selected disabled>Pilih Organisasi</option>
                        @foreach($organization as $row)
                        @if ($loop->first)
                        <option value="{{ $row->id }}" selected>{{ $row->nama }}</option>
                        @else
                        <option value="{{ $row->id }}">{{ $row->nama }}</option>
                        @endif
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label>Nama Perbelanjaan</label>
                    <input type="text" name="name" class="form-control" placeholder="Nama Perbelanjaan">
                </div>

                <div class="form-group">
                    <label>Diskripsi</label>
                    <input type="text" name="description" class="form-control" placeholder="Diskripsi Perbelanjaan">
                </div>

                <div class="form-group">
                    <div class="form-check-inline">
                        <input type="radio" name="recurring" class="form-check-input" id="is_recurring"  value="is_recurring">
                        <label class="form-check-label">Berulangan</label>

                        <div class="form-group" id="recurring_section">
                            <div class="form-check">
                                <input type="radio" name="recurring_type" class="form-check-input" value="annually">
                                <label class="form-check-label">Setiap Tahun</label>
                            </div>

                            <div class="form-check">
                                <input type="radio" name="recurring_type" class="form-check-input"  value="semester">
                                <label class="form-check-label">Setiap Semester</label>
                            </div>

                            <div class="form-check">
                                <input type="radio" name="recurring_type" class="form-check-input"  value="monthly">
                                <label class="form-check-label">Setiap Bulan</label>
                            </div>

                        </div>
                    </div>
                    <div class="form-check-inline">
                        <input type="radio" name="recurring" class="form-check-input"  id="is_not_recurring" value="is_not_recurring">
                        <label class="form-check-label">Tidak Berulangan</label>

                    </div>
                </div>

                <div class="form-group">
                    <label>Tempoh Bermula</label>
                    <input type="date" name="start_date" class="form-control" min="{{ $start }}">
                </div>

                <div class="form-group">
                    <label>Tempoh Berakhir</label>
                    <input type="date" name="end_date" class="form-control" min="{{ $start }}">
                </div>

                <div class="form-group">
                    <label>Amaun Perbelanjaan</label>
                    <input type="number" name="amount" class="form-control" step="any" min="0">
                </div>

                <div class="form-group mb-0">
                    <div>
                        <button type="submit" class="btn btn-primary waves-effect waves-light mr-1">
                            Simpan
                        </button>
                    </div>
                </div>
            </div>
            <!-- /.card-body -->

        </form>
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


        // ************************** radio button recurring ********************************
        $('#recurring_section').hide();

        $(document).on('change', '#is_recurring', function() {
            if (this.checked) {
                $('#recurring_section').show();
                $(document).on('change', '#recurring_section', function() {
                    if($('#recurring_section').val()!=null)
                    {
                        console.log( $('#recurring_section').val());
                        console.log("entered");
                    }

                })


            } 
        });

        $(document).on('change', '#is_not_recurring', function() {
            if (this.checked) {
                console.log( "now is "+$('#recurring_section').val());

                $('#recurring_section').hide();
                $('#recurring_section').val()="";

               console.log("then is "+ $('#recurring_section').val());
            } 
        });


    });
</script>

@endsection