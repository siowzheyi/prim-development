@extends('layouts.master')

@section('css')
<link href="{{ URL::asset('assets/libs/chartist/chartist.min.css')}}" rel="stylesheet" type="text/css" />
@endsection

@section('content')
{{-- <p>Welcome to this beautiful admin panel.</p> --}}
<div class="row align-items-center">
    <div class="col-sm-6">
        <div class="page-title-box">
            <h4 class="font-size-18">Kemaskini Butiran Kategori D</h4>
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
        <form class="form-validation" method="POST" action="{{ route('fees.updateD') }}" enctype="multipart/form-data">

            @csrf
            <div class="card-body">
                <input type="text" name="id" value="{{$selectedFee->id}}" hidden>
                <div class="form-group">
                    <label class="control-label">Nama Organisasi</label>
                    <select name="organization" id="organization" class="form-control"
                        data-parsley-required-message="Sila pilih organisasi" required>
                        <option value="{{ $selectedFee->organization_id }}" selected>{{ $selectedFee->orgName }}</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Nama Butiran</label>
                    <input type="text" name="name" class="form-control"
                        data-parsley-required-message="Sila masukkan nama butiran" required placeholder="Nama Butiran"
                        value="{{ $selectedFee->name }}">
                </div>

                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label>Harga (RM)</label>
                        <input class="form-control input-mask text-left"
                            data-inputmask="'alias': 'numeric', 'groupSeparator': ',', 'digits': 2, 'digitsOptional': false, 'placeholder': '0'"
                            im-insert="true" name="price" data-parsley-required-message="Sila masukkan harga"
                            data-parsley-errors-container=".errorMessagePrice" value="{{ $selectedFee->price }}" required readonly>
                        <i>*Harga per kuantiti</i>
                        <div class="errorMessagePrice"></div>
                    </div>
                    <!-- <div class="form-group col-md-6">
                        <label>Kuantiti</label>
                        <input type="text" name="quantity" class="form-control" placeholder="Kuantiti"
                            data-parsley-required-message="Sila masukkan kuantiti" value="{{ $selectedFee->quantity }}" required readonly>


                    </div> -->

                </div>


                <div class="form-row">
                    <div class="form-group col-md-12 required">
                        <label class="control-label">Tempoh Aktif</label>

                        <div class="input-daterange input-group" id="date">
                            <input type="text" id="define_sdate" value="{{ $selectedFee->start_date }}" hidden>
                            <input type="text" class="form-control" name="date_started" placeholder="Tarikh Awal"
                                autocomplete="off" data-parsley-required-message="Sila masukkan tarikh awal"
                                data-parsley-errors-container=".errorMessage" value="{{ $selectedFee->start_date }}" required />
                            <input type="text" class="form-control" name="date_end" placeholder="Tarikh Akhir"
                                autocomplete="off" data-parsley-required-message="Sila masukkan tarikh akhir"
                                data-parsley-errors-container=".errorMessage" value="{{ $selectedFee->end_date }}" required />
                        </div>
                        <div class="errorMessage"></div>
                        <div class="errorMessage"></div>
                    </div>
                </div>

                <div class="form-group">
                    <label>Nama Asrama</label>
                    <select name="dorm" id="dorm" class="form-control">
                        <option value="{{ $data }}" disabled selected>{{ $dorm }}</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Penerangan</label>
                    <textarea name="description" class="form-control" placeholder="Penerangan" cols="30"
                        rows="5">{{ $selectedFee->desc }}</textarea>
                </div>

                <div class="form-group mb-0">
                    <div class="text-right">
                        <button type="submit" class="btn btn-primary waves-effect waves-light mr-1">
                            Kemaskini
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
<script src="{{ URL::asset('assets/libs/dropzone/dropzone.min.js')}}"></script>
<script src="{{ URL::asset('assets/libs/chartist/chartist.min.js')}}"></script>
<script src="{{ URL::asset('assets/libs/parsleyjs/parsleyjs.min.js')}}"></script>
<script src="{{ URL::asset('assets/js/pages/dashboard.init.js')}}"></script>
<script src="{{ URL::asset('assets/libs/inputmask/inputmask.min.js')}}"></script>
<script src="{{ URL::asset('assets/libs/bootstrap-datepicker/bootstrap-datepicker.min.js') }}" defer></script>

<script>
    $(document).ready(function(){

        $('.form-validation').parsley();
        $(".input-mask").inputmask();

        var today = new Date();

        $('#date').datepicker({
            toggleActive: true,
            startDate: $("#define_sdate").val(),
            format: 'yyyy-mm-dd',
            orientation: 'bottom'
        });
        
    });
</script>
@endsection