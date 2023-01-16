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
                    <label>Nama Perbelanjaan</label><span style="color: red">  *</span>
                    <input type="text" name="name" class="form-control" placeholder="Nama Perbelanjaan">
                </div>

                <div class="form-group">
                    <label>Diskripsi</label>
                    <input type="text" name="description" class="form-control" placeholder="Diskripsi Perbelanjaan">
                </div>

                <div class="form-group">
                        <label>Berulangan</label><span style="color: red">  *</span>

                        <div class="form-check">
                            <div>
                                <input type="radio"  id="is_recurring_annual" name="recurring_type" class="form-check-input" value="annually" >
                                <label for="is_recurring_annual" class="form-check-label">Setiap Tahun</label>
                            </div>

                            <div>
                                <input type="radio"  id="is_recurring_semester" name="recurring_type" class="form-check-input"  value="semester">
                                <label for="is_recurring_semester" class="form-check-label">Setiap Semester</label>
                            </div>

                            <div>
                                <input type="radio" id="is_recurring_month" name="recurring_type" class="form-check-input"  value="monthly">
                                <label for="is_recurring_month" class="form-check-label">Setiap Bulan</label>
                            </div>
                        </div>
                </div>
                <div class="form-group">
                    <label>Tempoh Bermula Berulang</label><span style="color: red">  *</span>
                    <input type="date" name="start_date_recurring" class="form-control">
                </div>

                <div class="form-group">
                    <label>Tempoh Berakhir Berulang</label><span style="color: red">  *</span>
                    <input type="date" name="end_date_recurring" class="form-control" min="{{ $start }}">
                </div>
                <div class="form-group">
                    <label>Amaun Perbelanjaan</label><span style="color: red">  *</span>
                    <input type="number" name="amount" class="form-control" step="any" min="0">
                </div>

                <div class="form-group">
                    <label>Tempoh Bermula Berkesan</label><span style="color: red">  *</span>
                    <input type="date" name="start_date" class="form-control" id="start_date" min="{{ $start }}">
                </div>

                <div class="form-group">
                    <label >Tempoh Berakhir Berkesan</label><span style="color: red">  *</span>
                    <input type="date" name="end_date" class="form-control" id="end_date" min="{{ $start }}">
                </div>

                <div class="form-group mb-0">
                    <div>
                        <button type="submit" class="btn btn-primary waves-effect waves-light mr-1">
                            Simpan
                        </button>
                    </div>
                </div>
            </div>
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
   
</script>

@endsection