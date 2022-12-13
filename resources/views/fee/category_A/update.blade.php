@extends('layouts.master')

@section('css')
<link href="{{ URL::asset('assets/css/required-asterick.css')}}" rel="stylesheet">
@endsection

@section('content')
{{-- <p>Welcome to this beautiful admin panel.</p> --}}
<div class="row align-items-center">
    <div class="col-sm-6">
        <div class="page-title-box">
            <h4 class="font-size-18">Kemaskini Butiran Kategori A</h4>
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
        <form class="form-validation" method="post" action="{{ route('fees.updateA') }}" enctype="multipart/form-data">
            {{csrf_field()}}
            <div class="card-body">
                <input type="text" name="id" value="{{$selectedFee->id}}" hidden>
                <div class="form-group">
                    <label class="control-label required">Nama Organisasi</label>
                    <select name="organization" id="organization" class="form-control"
                        data-parsley-required-message="Sila masukkan nama organisasi" required>
                        <option value="{{ $selectedFee->organization_id }}" selected>{{ $selectedFee->orgName }}</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="control-label required">Nama Butiran</label>
                    <input type="text" name="name" class="form-control"
                        data-parsley-required-message="Sila masukkan nama butiran" required placeholder="Nama Butiran"
                        value="{{ $selectedFee->name }}">
                </div>



                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label class="control-label required">Harga (RM)</label>
                        <input class="form-control input-mask text-left"
                            data-inputmask="'alias': 'numeric', 'groupSeparator': ',', 'digits': 2, 'digitsOptional': false, 'placeholder': '0'"
                            im-insert="true" name="price" value="{{ $selectedFee->price }}" readonly>
                        <i>*Harga per kuantiti</i>
                    </div>
                    <div class="form-group col-md-6">
                        <label class="control-label required">Kuantiti</label>
                        <input type="text" name="quantity" class="form-control quantity text-left"  
                        data-inputmask="'alias': 'numeric'" placeholder="Kuantiti" value="{{ $selectedFee->quantity }}" readonly>
                    </div>

                </div>

                <div class="form-row">
                    <div class="form-group col-md-12">
                        <label class="control-label required">Tempoh Aktif</label>

                        <div class="input-daterange input-group" id="date">
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
<script src="{{ URL::asset('assets/libs/chartist/chartist.min.js')}}"></script>
<script src="{{ URL::asset('assets/libs/parsleyjs/parsleyjs.min.js')}}"></script>
<script src="{{ URL::asset('assets/js/pages/dashboard.init.js')}}"></script>
<script src="{{ URL::asset('assets/libs/inputmask/inputmask.min.js')}}"></script>
<script src="{{ URL::asset('assets/libs/bootstrap-datepicker/bootstrap-datepicker.min.js') }}" defer></script>

<script>
    $(document).ready(function(){

        $('.form-validation').parsley();
        $(".input-mask").inputmask();
        $(".quantity").inputmask();

        var today = new Date();

        $('#date').datepicker({
            toggleActive: true,
            startDate: today,
            todayHighlight:true,
            format: 'dd/mm/yyyy',
            orientation: 'bottom'
          });
        
    });
</script>
@endsection