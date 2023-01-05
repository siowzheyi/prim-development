@extends('layouts.master')

@section('css')
<link href="{{ URL::asset('assets/libs/chartist/chartist.min.css')}}" rel="stylesheet" type="text/css" />
<link href="{{ URL::asset('assets/libs/sweetalert2/sweetalert2.min.css')}}" rel="stylesheet" type="text/css" />
<link href="{{ URL::asset('assets/css/checkbox.css') }}" rel="stylesheet" type="text/css" />
<link href="{{ URL::asset('assets/css/accordion.css') }}" rel="stylesheet" type="text/css" />

<style>
    body {
        /* overflow: hidden; */
    }

    .container-wrapper-scroll {
        width: 100%;
        height: 50vh;
        overflow-y: auto;
    }

    /* width */
    .container-wrapper-scroll::-webkit-scrollbar {
        width: 10px;
    }

    /* Track */
    .container-wrapper-scroll::-webkit-scrollbar-track {
        background: #f1f1f1;
    }

    /* Handle */
    .container-wrapper-scroll::-webkit-scrollbar-thumb {
        background: #888;
    }

    /* Handle on hover */
    .container-wrapper-scroll::-webkit-scrollbar-thumb:hover {
        background: #555;
    }
</style>

@endsection

@section('content')
<div class="row align-items-center">
    <div class="col-sm-6">
        <div class="page-title-box">
            <h4 class="font-size-18">Bayar Perbelanjaan</h4>
            <!-- <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item active">Welcome to Veltrix Dashboard</li>
            </ol> -->
        </div>
    </div>
</div>

<div class="card p-4">
    <div class="row">
        <div class="col-md-12 pb-3">
            <h3>Sila Pilih Sekolah Berkaitan Untuk Bayaran Perbelanjaan</h3>
        </div>

<div class="col-md-12">
    <div class="row">
        <div class="container-wrapper-scroll p-2 mb-3">

            @foreach ($organization as $organizations)
            <div class="col-md-12">
                <div id="accordionExample{{ $organizations->id }}" class="accordion shadow">
                    <!-- Accordion item 1 -->
                    <div class="card">
                        <div class="inputGroup">
                            <input id="option-{{ $organizations->id }}" name="nameSchool"
                                value="{{ $organizations->id }}" type="checkbox" data-toggle="collapse"
                                data-target="#collapse{{ $organizations->id }}" aria-expanded="false"
                                aria-controls="collapse{{ $organizations->id }}"
                                class="d-block position-relative text-dark collapsible-link py-2"
                                onchange="checkOrganization(this)" />

                            <label for="option-{{ $organizations->id }}">
                                <span style="font-size: 18px">{{ $organizations->nama }}</span>
                                <br>
                            </label>
                        </div>

                        <!-- Below is to let parent to choose wanna pay for which and how many expenses item -->

                        <div id="collapse{{ $organizations->id }}" aria-labelledby="heading{{ $organizations->id }}"
                            data-parent="#accordionExample{{ $organizations->id }}" class="collapse">
                            <div class="card-body pl-0 pr-0">

                                @foreach($recurring_type as $data)
                                
                                <div class="col-md-12">
                                    <div id="accordionExample{{ $organizations->id }}-{{ $organizations->user_id }}"
                                        class="accordion shadow">
                                        <!-- Accordion item 1 -->
                                        <div class="card">
                                            <div id="heading{{ $organizations->id }}-{{ $organizations->user_id }}"
                                                class="card-header bg-white shadow-sm border-0">
                                                <h6 class="mb-0 font-weight-bold"><a href="#" data-toggle="collapse"
                                                        data-target="#collapse{{ $organizations->id }}-{{ $organizations->user_id }}"
                                                        aria-expanded="true"
                                                        aria-controls="collapse{{ $organizations->id }}-{{ $organizations->user_id }}"
                                                        class="d-block position-relative text-dark text-uppercase collapsible-link py-2">
                                                        {{ $data }}</a></h6>

                                                <div id="collapse{{ $organizations->id }}-{{ $organizations->user_id }}"
                                                    aria-labelledby="heading{{ $organizations->id }}-{{ $organizations->user_id }}"
                                                    data-parent="#accordionExample{{ $organizations->id }}-{{ $organizations->user_id }}"
                                                    class="collapse show">

                                                    <div class="card-body pl-0 pr-0">
                                                        @foreach($getfees_by_parent->where('organization_id', $organizations->id)->where('recurring_name',$data) as $item)
                                                        <div class="inputGroup">
                                                            <input
                                                                id="option-{{ $item->id }}-{{ $organizations->user_id }}"
                                                                name="billcheck" value="{{ $item->amount }}"
                                                                onchange="checkD(this)" type="checkbox" />

                                                            <label
                                                                for="option-{{ $item->id }}-{{ $organizations->user_id }}">
                                                                <span style="font-size: 18px">{{ $item->name }}</span>
                                                                <br>
                                                                <span
                                                                    style="font-size: 14px;font-weight:100;">RM{{  number_format((float)$item->amount, 2, '.', '') }}
                                                                    </span>
                                                            </label>

                                                            {{-- hidden input checkbox second --}}
                                                            <input
                                                                id="option-{{ $item->id }}-{{ $organizations->user_id }}-2"
                                                                style="opacity: 0.0; position: absolute; left: -9999px"
                                                                checked="checked" name="billcheck2"
                                                                value="{{ $organizations->user_id }}-{{ $item->id }}"
                                                                type="checkbox" />
                                                        </div>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                @endforeach

                                <!-- Below is to let parent to choose wanna pay for which and how many children -->
                                @foreach($list->where('oid', $organizations->id) as $row)
                                <div class="col-md-12">
                                    <div id="accordionExample{{ $row->oid }}-{{ $row->studentid }}"
                                        class="accordion shadow">
                                        <!-- Accordion children -->
                                        <div class="card">

                                            <div class="inputGroup">
                                                <input id="option-{{ $row->oid }}-{{ $row->studentid }}"
                                                    name="nameSchool" value="{{ $row->oid }}" type="checkbox"
                                                    onchange="checkStudent(this)"
                                                    data-toggle="collapse"
                                                    data-target="#collapse{{ $row->oid }}-{{ $row->studentid }}"
                                                    aria-expanded="false"
                                                    aria-controls="collapse{{ $row->oid }}-{{ $row->studentid }}"
                                                    class="d-block position-relative text-dark collapsible-link py-2" />

                                                <label for="option-{{ $row->oid }}-{{ $row->studentid }}">
                                                    <span style="font-size: 18px">{{ $loop->iteration }}.
                                                        {{ $row->studentname  }}</span>
                                                    <span> ( {{ $row->classname }} )</span>
                                                    <br>
                                                    <span> {{ $row->nschool }} </span>
                                                </label>

                                                 {{-- hidden input checkbox second --}}
                                                 <input
                                                    id="option-{{ $row->oid }}-{{ $row->studentid }}-2"
                                                    style="opacity: 0.0; position: absolute; left: -9999px"
                                                    checked="checked" name="billcheck2"
                                                    value="{{ $row->oid }}-{{ $row->studentid }}"
                                                    type="checkbox" />

                                            </div>
                                            <div id="collapse{{ $row->oid }}-{{ $row->studentid }}"
                                                aria-labelledby="heading{{ $row->oid }}-{{ $row->studentid }}"
                                                data-parent="#accordionExample{{ $row->oid }}-{{ $row->studentid }}"
                                                class="collapse">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        {{-- confirmation print modal --}}
        <div id="printConfirmationModal" class="modal fade" role="dialog">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">Terima Kasih</h4>
                    </div>
                    <div class="modal-body">
                        Sila tekan butang 'Cetak' untuk mencetak resit anda.
                    </div>
                    <div class="modal-footer">
                        <form action="route('recurring_fees.printReceipt')" method="post">
                            <button type="button" data-dismiss="modal" class="btn btn-primary" id="print" name="print">Cetak</button>
                            <button type="button" data-dismiss="modal" class="btn">Batal</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        {{-- end confirmation print modal --}}

        <div class="col-md-8 p-3">
            <h4>Jumlah Yang Perlu Dibayar : RM<span id="pay"></span> </h4>

            {{-- <form method="POST" action="{{ route('fpxIndex') }}" enctype="multipart/form-data"> --}}

        </div>
        <div class="col-md-4 p-2">

            <button id="btn-byr" disabled class="btn btn-success float-right" type="submit">Proses
                Pembayaran</button>
            {{-- </form> --}}
        </div>

        <input type="hidden" name="ttlpay" id="ttlpay" value="0.00">
        <input type="hidden" value="{{ route('payment') }}" id="routepay">

    </div>
</div>
</div>
<hr>

{{-- <hr>
    <h4>FPX Terms & Conditions: <a href="https://www.mepsfpx.com.my/FPXMain/termsAndConditions.jsp" target="_blank">https://www.mepsfpx.com.my/FPXMain/termsAndConditions.jsp</a></h4> --}}
</div>
@endsection


@section('script')
<!-- Peity chart-->
<script src="{{ URL::asset('assets/libs/peity/peity.min.js')}}"></script>

<!-- Plugin Js-->
<script src="{{ URL::asset('assets/libs/chartist/chartist.min.js')}}"></script>

<script src="{{ URL::asset('assets/js/pages/dashboard.init.js')}}"></script>

<script src="{{ URL::asset('assets/libs/sweetalert2/sweetalert2.min.js')}}"></script>

<script>
    // return array like this 1-2-3
    // 1 = student id
    // 2 = fee id
    // 3 = details id

    var amt = 0;
    var total = 0;
    $("#pay").html("0.00");
    var myCheckboxes = new Array();
    var myCheckboxes_checkStudent = new Array();
    var organization_cb = new Array();
    var oid;

    $('#btn-byr').click(function () {
      Swal.fire({
        title: "Adakah anda pasti?",
        text: "Jumlah yang perlu dibayar RM"+total,
        type: "warning",
        showCancelButton: true,
        confirmButtonColor: "#34c38f",
        cancelButtonColor: "#f46a6a",
      }).then(function (result) {
        if (result.value) {
           
            // console.log(myCheckboxes.length());
            // window.location.href = "{{ route('billIndex')}}";

            //id = {{ $organizations->user_id }}-{{ $item->id }}
            // item = $getFeesbyparents
            //studentId = {{ $row->oid }}-{{ $row->studentid }}
            $.ajax({
                url: "{{ route('recurring_fees.paymentFake') }}",
                data: { 
                    user_id_and_expenses_id: myCheckboxes,
                    oid_and_student_id: myCheckboxes_checkStudent
                },
                
            })
            .done(function(response){
                // document.write(response);
                console.log("success");
                console.log(response);
                console.log("any");
                $('#printConfirmationModal').modal('show');


            });
            
        }
        else{
            console.log("denied");
        }
      });
    }); //Parameter
   
    ///*************** function for if different organization *****************
    function checkOrganization(element) {
        var id = document.getElementById(element.id);
        var name = document.getElementsByName(element.name);
        // console.log(name[0].value);
        oid = $('#'+element.id).val();
        // console.log(oid);

        // console.log(oid);
        if (id.checked) {
            for(var i=0; i < name.length; i++){
                
                if(name[i].checked ){

                    name[i].disabled = false;
                }else{
                    
                    if(oid == name[i].value){
                        name[i].disabled = false;
                    }
                    else{
                        name[i].disabled = true;
                    }
                
                }

            } 
        }else {
            for(var i=0; i < name.length; i++){
                name[i].disabled = false;
            } 

            total = 0;
            amt = 0;

            $("input[name='billcheck']:checkbox").prop('checked', false);
            $("input[name='billcheck2']:checkbox").prop('checked', false);
            $("#pay").html("0.00");
            myCheckboxes = [];
            myCheckboxes_checkStudent = [];
            if(total == 0){
            document.getElementById('btn-byr').disabled = true;
            }else{
                document.getElementById('btn-byr').disabled = false;
            }
        } 
        
    }
    

    function checkD(element) {
        var id = element.id;
        var id2 = element.id+"-2";
        if (element.checked) {
            amt += parseFloat($("#" + id).val());
            // $("#" + id2).prop('checked', true)

            $("#" + id2).prop('checked', true).each(function() {
                myCheckboxes.push($(this).val());
            });
           

        } else {
            if(amt != 0)
            {
                amt -= parseFloat($("#" + id).val());
                $("#" + id2).prop('checked', false).each(function() {
                    myCheckboxes = myCheckboxes.filter(item => item !== $(this).val())
                    // myCheckboxes.push($(this).val());
            });
            }
        }   
        total = parseFloat(amt).toFixed(2);
        $("#pay").html(total);
        $("input[name='amount']").val(total);

        if(total == 0){
            document.getElementById('btn-byr').disabled = true;
        }else{
            document.getElementById('btn-byr').disabled = false;
        }
    }

    function checkStudent(element) {
        var id = element.id;
        var id2 = element.id+"-2";
        if (element.checked) {
        
            // $("#" + id2).prop('checked', true)

            $("#" + id2).prop('checked', true).each(function() {
                myCheckboxes_checkStudent.push($(this).val());
               
            });
            
        } else {
            }
    }   
        total = parseFloat(amt).toFixed(2);
        $("#pay").html(total);
        $("input[name='amount']").val(total);

        if(total == 0){
            document.getElementById('btn-byr').disabled = true;
        }else{
            document.getElementById('btn-byr').disabled = false;
        }
        

    // function checkD(element) {
    //     var id = element.id;
    //     if (element.checked) {
    //         amt += parseFloat($("#" + id).val());
    //     } else {
    //         if(amt != 0)
    //         {
    //             amt -= parseFloat($("#" + id).val());
    //         }
    //     }   
    //     total = parseFloat(amt).toFixed(2);
    //     $("#pay").html(total);
    //     $("input[name='amount']").val(total);

    //     if(total == 0){
    //         document.getElementById('btn-byr').disabled = true;
    //     }else{
    //         document.getElementById('btn-byr').disabled = false;
    //     }
    // }

    

        // $('.getid').children().prop('disabled', true);

        // $('.getid').on("click",function(){
        //     var id =  $(this).attr("id");
        //     console.log(id);
        // var id = "#"+$(".getid").attr("id");

        //     $(".getid").children().prop('disabled', true);
        //     //post code
        // });

    // var elem = $('a[href="'+id+'"]');
    // elem.attr('disabled','disabled')

</script>

@endsection