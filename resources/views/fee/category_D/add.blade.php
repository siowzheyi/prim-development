@extends('layouts.master')

@section('css')
<link href="{{ URL::asset('assets/css/required-asterick.css')}}" rel="stylesheet">
@endsection

@section('content')
{{-- <p>Welcome to this beautiful admin panel.</p> --}}
<div class="row align-items-center">
    <div class="col-sm-6">
        <div class="page-title-box">
            <h4 class="font-size-18">Tambah Butiran Kategori D</h4>
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
        <form class="form-validation" method="post" action="{{ route('fees.storeD') }}" enctype="multipart/form-data">
            {{csrf_field()}}
            <div class="card-body">

                <div class="form-group">
                    <label class="control-label required">Nama Organisasi</label>
                    <select name="organization" id="organization" class="form-control"
                        data-parsley-required-message="Sila masukkan nama organisasi" required>
                        <option value="" disabled selected>Pilih Organisasi</option>
                        @foreach($organization as $row)
                        <option value="{{ $row->id }}">{{ $row->nama }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label class="control-label required">Nama Butiran</label>
                    <input type="text" name="name" class="form-control"
                        data-parsley-required-message="Sila masukkan nama butiran" required placeholder="Nama Butiran">
                </div>

                <div class="form-row">
                    <div class="form-group col-md-12">
                        <label class="control-label required">Harga (RM)</label>
                        <input class="form-control input-mask text-left"
                        data-inputmask="'alias': 'numeric', 'groupSeparator': ',', 'digits': 2, 'digitsOptional': false, 'placeholder': '0'"
                            im-insert="true" name="price" onchange="this.value = Math.abs(this.value);">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col-md-12">
                        <label class="control-label required">Tempoh Aktif</label>

                        <div class="input-daterange input-group" id="date">
                            <input type="text" class="form-control" name="date_started" placeholder="Tarikh Awal"
                                autocomplete="off" data-parsley-required-message="Sila masukkan tarikh awal"
                                data-parsley-errors-container=".errorMessage" required />
                            <input type="text" class="form-control" name="date_end" placeholder="Tarikh Akhir"
                                autocomplete="off" data-parsley-required-message="Sila masukkan tarikh akhir"
                                data-parsley-errors-container=".errorMessage" required />
                        </div>
                        <div class="errorMessage"></div>
                        <div class="errorMessage"></div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="renew" class="control-label required"> Tempoh Ulang (bulan) </label> 
                    <input type="number" name="renew" id="renew" class="form-control"
                        data-parsley-required-message="Sila masukkan tempoh ulangan butiran" required placeholder="0" min="0" onchange="this.value = Math.abs(this.value);">
                    <i>* 0 jika tidak berulang</i>
                </div>

                <div class="form-group">
                    <label class="control-label required">Jenis Asrama</label>
                    <select name="grade" id="grade" class="form-control">
                        <option value="" selected>Pilih Jenis Asrama</option>
                    </select>
                </div>

                <div class="cbhide form-check-inline pb-3 pt-3">

                </div>

                <div class="form-group">
                    <label>Penerangan</label>
                    <textarea name="description" class="form-control" placeholder="Penerangan" cols="30"
                        rows="5"></textarea>
                </div>

                <div class="form-group mb-0">
                    <div class="text-right">
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
<script src="{{ URL::asset('assets/libs/parsleyjs/parsleyjs.min.js')}}"></script>
<script src="{{ URL::asset('assets/js/pages/dashboard.init.js')}}"></script>
<script src="{{ URL::asset('assets/libs/inputmask/inputmask.min.js')}}"></script>
<script src="{{ URL::asset('assets/libs/bootstrap-datepicker/bootstrap-datepicker.min.js') }}" defer></script>

<script>
    $(document).ready(function(){

        $('.form-validation').parsley();
        $(".input-mask").inputmask();
        $(".quantity").inputmask();
        $('.cbhide').hide();

        var today = new Date();

        $('#date').datepicker({
            toggleActive: true,
            startDate: today,
            todayHighlight:true,
            format: 'dd/mm/yyyy',
            orientation: 'bottom'
        });

        // ************************** grade on change ********************************

        $('#grade').change(function() {
            if($(this).val() != '')
            {
                var organizationid    = $("#organization option:selected").val();
                var _token            = $('input[name="_token"]').val();
                var grade             = $("#grade option:selected").val();
                $.ajax({
                    url:"{{ route('fees.fetchDorm') }}",
                    method:"POST",
                    data:{ oid:organizationid,
                            grade:grade,
                            _token:_token },
                    success:function(result)
                    {
                        console.log(result.success);
                        if(grade == "ALL_TYPE")
                        {
                            $('.cbhide').hide();
                            $("#cb_dorm").remove();
                            $(".cbhide label").remove();
                        }
                        else
                        {
                            $('.cbhide').show();
                            $("#cb_dorm").remove();
                            $(".cbhide label").remove();
                            $(".cbhide").append(
                                "<label for='checkAll' style='margin-right: 22px;' class='form-check-label'> <input class='form-check-input' type='checkbox' id='checkedAll' name='all_dorm' value=''/> Semua Asrama </label>"
                            );
                            // console.log(result.success.oid);
                            jQuery.each(result.success, function(key, value) {
                                $(".cbhide").append(
                                    "<label for='cb_dorm' style='margin-right: 22px;' class='form-check-label'> <input class='checkSingle form-check-input' data-parsley-required-message='Sila Pilih Asrama' data-parsley-errors-container='.errorMessageCB' type='checkbox' id='cb_dorm' name='cb_dorm[]' value='" +
                                    value.id + "'/> " + value.name + " </label><br> <div class='errorMessageCB'></div>");

                                    console.log(result.success.length);
                            });
                            $("#cb_dorm").attr('required', '');  
                        }
                    }

                });
            }
        });
            
        if($("#grade").val() != ""){
            $("#grade").prop("selectedIndex", 1).trigger('change');
        }

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

        // ************************** organization on change ********************************
        
        $("#organization").prop("selectedIndex", 1).trigger('change');
        $("#name").prop("selectedIndex", 0);
        fetchDorm($("#organization").val());

        $('#organization').change(function() {
            
            if($(this).val() != '')
            {
                var organizationid    = $("#organization option:selected").val();
                var _token            = $('input[name="_token"]').val();
                var private           = 0;
                var share             = 0;
                $('.cbhide').hide();
                $("#cb_dorm").remove();
                $(".cbhide label").remove();
                $.ajax({
                    url:"{{ route('fees.fetchDorm') }}",
                    method:"POST",
                    data:{ oid:organizationid,
                            _token:_token },
                    success:function(result)
                    {
                        if(result.success.length > 0)
                        {
                            $('#grade').empty();
                            
                            jQuery.each(result.success, function(key, value) {
                                if(value.grade == 1){
                                    private = 1;
                                }
                                else if(value.grade == 2){
                                    share = 1;
                                }
                            });

                            "<option value='' disabled selected>Pilih Jenis Asrama</option>"
                            if(private == 1 && share != 1){
                                $('#grade').append(
                                    '<option value="" disabled selected> Pilih Jenis Asrama</option>' +
                                    '<option value="ALL_TYPE">Semua Jenis</option>' +
                                    '<option value="1">Bilik Peribadi</option>'
                                )
                            }
                            else if(share == 1 && private != 1){
                                $('#grade').append(
                                    '<option value="" disabled selected> Pilih Jenis Asrama</option>' +
                                    '<option value="ALL_TYPE">Semua Jenis</option>' +
                                    '<option value="2">Bilik Kongsi</option>'
                                )
                            }
                            else{
                                $('#grade').append(
                                    '<option value="" disabled selected> Pilih Jenis Asrama</option>' +
                                    '<option value="ALL_TYPE">Semua Jenis</option>' +
                                    '<option value="1">Bilik Peribadi</option>' +
                                    '<option value="2">Bilik Kongsi</option>'
                                )
                            }
                        }
                        else
                        {
                            $('#grade').empty();
                            "<option value='' disabled selected>Pilih Jenis Asrama</option>"
                            $('#grade').append(
                                '<option value="" disabled selected> Pilih Jenis Asrama</option>'
                            );
                        }
                    }

                });
            }
        });

        function fetchDorm(organizationid = ''){
            var _token = $('input[name="_token"]').val();
            $.ajax({
                url:"{{ route('fees.fetchDorm') }}",
                method:"POST",
                data:{ oid:organizationid,
                        // grade:grade,
                        _token:_token },
                success:function(result)
                {
                    console.log(result.success);
                    if(grade == "ALL_TYPE")
                    {
                        $('.cbhide').hide();
                        $("#cb_dorm").remove();
                        $(".cbhide label").remove();
                    }
                    else
                    {
                        $('.cbhide').show();
                        $("#cb_dorm").remove();
                        $(".cbhide label").remove();
                        $(".cbhide").append(
                            "<label for='checkAll' style='margin-right: 22px;' class='form-check-label'> <input class='form-check-input' type='checkbox' id='checkedAll' name='all_dorm' value=''/> Semua Asrama </label>"
                        );
                        // console.log(result.success.oid);
                        jQuery.each(result.success, function(key, value) {
                            $(".cbhide").append(
                                "<label for='cb_dorm' style='margin-right: 22px;' class='form-check-label'> <input class='checkSingle form-check-input' data-parsley-required-message='Sila Pilih Asrama' data-parsley-errors-container='.errorMessageCB' type='checkbox' id='cb_dorm' name='cb_dorm[]' value='" +
                                value.id + "'/> " + value.name + " </label><br> <div class='errorMessageCB'></div>");

                                console.log(result.success.length);
                        });
                        $("#cb_dorm").attr('required', '');  
                    }
                }
            })
        }
    });
</script>
@endsection