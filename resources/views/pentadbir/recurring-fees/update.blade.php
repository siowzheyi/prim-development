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
                <li class="breadcrumb-item active">Perbelanjaan >> Ubah Perbelanjaan</li>
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

        <form method="post" action="{{ route('recurring_fees.update', $expenses->id) }}" enctype="multipart/form-data">
            @method('PATCH')

            {{csrf_field()}}
            <div class="card-body">

                <div class="form-group">
                    <label>Nama Organisasi</label>
                    <select name="organization" id="organization" class="form-control">

                        <option value="">Pilih Organisasi</option>
                        @foreach($organization as $organizationRow)

                        @if($organizationRow->id == $expenses->organization_id)
                        <option value="{{ $organizationRow->id }}" selected> {{ $organizationRow->nama }} </option>
                        @else
                        <option value="{{ $organizationRow->id }}">{{ $organizationRow->nama }}</option>

                        @endif

                        @endforeach

                    </select>
                </div>

                <div class="form-group">
                    <label>Nama Perbelanjaan</label>
                    <input type="text" name="name" class="form-control" value="{{ $expenses->name }}">
                </div>

                <div class="form-group">
                    <label>Diskripsi</label>
                    <input type="text" name="description" class="form-control" value="{{ $expenses->description }}">
                </div>

                <div class="form-group">
                    <div class="form-check-inline">
                        <label class="form-check-label">Berulangan</label>

                        <div class="form-group">
                            <div class="form-check">
                                <input type="text" id="getRecurringType" class="form-control" value={{ $expenses->recurring_type }} hidden>

                                <input type="radio" name="recurring_type" class="form-check-input" value="annually" id="recurring_annualy">
                                <label class="form-check-label">Setiap Tahun</label>
                            </div>

                            <div class="form-check">
                                <input type="radio" name="recurring_type" class="form-check-input"  value="semester" id="recurring_semester">
                                <label class="form-check-label">Setiap Semester</label>
                            </div>

                            <div class="form-check">
                                <input type="radio" name="recurring_type" class="form-check-input"  value="monthly" id="recurring_monthly">
                                <label class="form-check-label">Setiap Bulan</label>
                            </div>

                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label>Tempoh Bermula Berulang</label>
                    <input type="date" name="start_date_recurring" class="form-control" value="{{ $start_date_recurring }}">
                </div>

                <div class="form-group">
                    <label>Tempoh Berakhir Berulang</label>
                    <input type="date" name="end_date_recurring" class="form-control" value="{{ $end_date_recurring }}">
                </div>

                <div class="form-group">
                    <label>Amaun Perbelanjaan</label>
                    <input type="number" name="amount" class="form-control" step="any" min="0" value="{{ $expenses->amount }}" readonly>
                </div>

                <div class="form-group">
                    <label>Tempoh Bermula Berkesan</label>
                    <input type="date" name="start_date" class="form-control"  value="{{ $start_date }}">
                </div>

                <div class="form-group">
                    <label>Tempoh Berakhir Berkesan</label>
                    <input type="date" name="end_date" class="form-control"  value="{{ $end_date }}">
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
     $(document).ready(function() {
        
        if($("#getRecurringType").val()=="annually"){
            $("#recurring_annualy").prop("checked", true);
        }
        else if($("#getRecurringType").val()=="semester"){
            $("#recurring_semester").prop("checked", true);
        }
        else if($("#getRecurringType").val()=="monthly"){
            $("#recurring_monthly").prop("checked", true);
        }

    });
</script>

@endsection