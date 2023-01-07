@extends('layouts.master')

@section('css')
<link href="{{ URL::asset('assets/css/required-asterick.css')}}" rel="stylesheet">
<link href="{{ URL::asset('assets/libs/chartist/chartist.min.css')}}" rel="stylesheet" type="text/css" />
@endsection

@section('content')
{{-- <p>Welcome to this beautiful admin panel.</p> --}}
<div class="row align-items-center">
    <div class="col-sm-6">
        <div class="page-title-box">
            <h4 class="font-size-18">Kemaskini Butiran Kategori B</h4>
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
        <form class="form-validation" method="POST" action="{{ route('fees.updateB') }}" enctype="multipart/form-data">

            @csrf
            <div class="card-body">
                <input type="text" name="id" value="{{$selectedFee->id}}" hidden>
                <div class="form-group">
                    <label class="control-label required">Nama Organisasi</label>
                    <select name="organization" id="organization" class="form-control"
                        data-parsley-required-message="Sila pilih organisasi" required>
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
                            im-insert="true" name="price" data-parsley-required-message="Sila masukkan harga"
                            data-parsley-errors-container=".errorMessagePrice" value="{{ $selectedFee->price }}" required readonly>
                        <i>*Harga per kuantiti</i>
                        <div class="errorMessagePrice"></div>
                    </div>
                    <div class="form-group col-md-6">
                        <label class="control-label required">Kuantiti</label>
                        <input type="text" name="quantity" class="form-control" placeholder="Kuantiti"
                            data-parsley-required-message="Sila masukkan kuantiti" value="{{ $selectedFee->quantity }}" required readonly>


                    </div>

                </div>


                <div class="form-row">
                    <div class="form-group col-md-12">
                        <label class="control-label required">Tempoh Aktif</label>

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
                    <label class="control-label required">Tahap</label>
                    <!-- <input type="text" value="{{$data}}"> -->
                    <select name="level" id="level" class="form-control"
                        data-parsley-required-message="Sila pilih tahap"
                        data-parsley-required-message="Sila pilih tahap" required>
                        @if($data == "All_Level"){
                            <option value="All_Level" selected>Semua Tahap</option>
                        }
                        @elseif($data == 1){
                            <option value="1" selected>Tahap 1</option>
                        }
                        @elseif($data == 2){
                            <option value="2" selected>Tahap 2</option>
                        }
                        @else{
                            <option value="All_Level">Semua Tahap</option>
                            <option value="1">Tahap 1</option>
                            <option value="2">Tahap 2</option>
                        }
                        @endif
                    </select>
                </div>

                <div class="yearhide form-group">
                    <label class="control-label required">Tahun</label>
                    <select name="year" id="year" class="form-control">
                        <option value="" disabled selected>Pilih Tahun</option>
                    </select>
                </div>

                <div class="cbhide form-check-inline pb-3 pt-3">

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
        $('.yearhide').hide();
        $('.cbhide').hide();

        $('#date').datepicker({
            toggleActive: true,
            startDate: $("#define_sdate").val(),
            format: 'yyyy-mm-dd',
            orientation: 'bottom',
          });

        // if($("#tahap").val() == "All_Level"){
        //     $("#level").prop("selectedIndex", 0).trigger('change');
        // }
        // else if($("#tahap").val() == 1){
        //     $('.yearhide').show();
        //     $('.cbhide').show();
        //     $("#level").prop("selectedIndex", 1).trigger('change');
            
        // }
        // else if($("#tahap").val() == 2){
        //     $("#level").prop("selectedIndex", 2).trigger('change');
        //     $('.yearhide').show();
        //     $('.cbhide').show();
        // }

          // ************************** organization on change ********************************

        $('#organization').change(function() {
            var organizationid = $("#organization option:selected").val();
            $('.yearhide').hide();
            $("#level").prop("selectedIndex", 1).trigger('change');
            
        });
            
        // if($("#organization").val() != ""){
        //     $("#organization").prop("selectedIndex", 1).trigger('change');
        // }

          // ************************** checkbox class ********************************

        $(document).on('change', '#checkedAll', function() {
            if (this.checked) {
                $(".checkSingle").each(function() {
                    this.checked = true;
                })
            } else {
                $(".checkSingle").each(function() {
                    this.checked = false;
                })
            }
        });

        // ************************** checkbox class ********************************

        $(document).on('change', '.checkSingle', function() {
            // $('#cb_class').not(this).prop('checked', this.checked);
            if ($(this).is(":checked")) {
                var isAllChecked = 0;
                $(".checkSingle").each(function() {
                    if (!this.checked)
                        isAllChecked = 1;
                })
                if (isAllChecked == 0) {
                    $("#checkedAll").prop("checked", true);
                }
            } else {
                $("#checkedAll").prop("checked", false);
            }
        });
        
           // ************************** retrieve class year ********************************
        $('#level').change(function() {
            if ($(this).val() != '') {
                var level   = $("#level option:selected").val();
                var oid     = $("#organization option:selected").val();
                var _token  = $('input[name="_token"]').val();

                if(level=="All_Level"){
                    $('.yearhide').hide();
                    $('.cbhide').hide();
                    $('#cb_class').remove();
                    $(".cbhide label").remove();
                    $('#year').empty();

                }else{
                    $.ajax({
                        url: "{{ route('fees.fetchClassYear') }}",
                        method: "GET",
                        data: {
                            level: level,
                            oid: oid,
                            _token: _token
                        },
                        success: function(result) {

                            $('.yearhide').show();
                            $('#year').empty();
                            $("#year").append("<option value='All_Year' selected> Semua Tahun</option>");
                            jQuery.each(result.datayear, function(key, value) {
                                $("#year").append("<option value='"+ value.year +"'> Tahun " + value.year + "</option>");
                            });
                        }
                    })
                }
            }
        });

        // ************************** retrieve class ********************************
        $('#year').change(function() {
            console.log($(this).val());
            if ($(this).val() != '') {
                var year   = $("#year option:selected").val();
                var oid     = $("#organization option:selected").val();
                var _token  = $('input[name="_token"]').val();

                $.ajax({
                    url: "{{ route('fees.fetchClass') }}",
                    method: "POST",
                    data: {
                        year: year,
                        oid: oid,
                        _token: _token
                    },
                    success: function(result) {

                        if(year == "All_Year"){
                            $('.cbhide').hide();
                            $('#cb_class').remove();
                            $(".cbhide label").remove();
                        }else{
                            $('.cbhide').show();
                            $('#cb_class').remove();
                            $(".cbhide label").remove();
                            $(".cbhide").append(
                                "<label for='checkAll' style='margin-right: 22px;' class='form-check-label'> <input class='form-check-input' type='checkbox' id='checkedAll' name='all_classes' value=''/> Semua Kelas </label>"
                            );
                            // console.log(result.success.oid);
                            jQuery.each(result.success, function(key, value) {
                                $(".cbhide").append(
                                    "<label for='cb_class' style='margin-right: 22px;' class='form-check-label'> <input class='checkSingle form-check-input' data-parsley-required-message='Sila pilih kelas' data-parsley-errors-container='.errorMessageCB' type='checkbox' id='cb_class' name='cb_class[]' value='" +
                                    value.cid + "'/> " + value.cname + " </label><br> <div class='errorMessageCB'></div>");
                            });
                            $("#cb_class").attr('required', '');  
                        }
                        
                    }
                })
            }
        });
        
    });
</script>
@endsection