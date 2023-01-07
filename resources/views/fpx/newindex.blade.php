<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">

    <link href="{{ URL::asset('assets/css/bootstrap.min.css') }}" id="bootstrap-light" rel="stylesheet"
        type="text/css" />
    <link href="{{ URL::asset('assets/css/icons.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ URL::asset('assets/css/app.min.css') }}" id="app-light" rel="stylesheet" type="text/css" />
    <style>
        span {
            font-size: 1.09375rem;
            font-weight: bolder;
        }
    </style>
    <title>PRiM | Pembayaran</title>
    <link rel="shortcut icon" href="{{ URL::asset('assets/images/logo/fav-logo-prim.png')}}">

</head>

<body style="background-color: white">
    <div class="container detail" id="detail">
        <div class="card-body shadow rounded mb-1" style="background-color:#323447">
            <center>
                <img src="{{ URL::asset('assets/images/logo/prim.svg') }}" alt="" height="50">
            </center>
        </div>

        <form method="POST" action="{{ route('paymentSuccess') }}" enctype="multipart/form-data">
            {{ csrf_field() }}
            <div class="card rounded-xl mt-4" style="padding: 10px">
                <input type="hidden" id="transac_id" name="transac_id" value="{{$transaction->id}}">
                <input type="hidden" id="organ_id" name="organ_id" value="{{$organization->id}}">
                <div class="form-group">
                    <h2>Transaksi</h2>  
                    <br>
                    <table style="width: 100%; margin: auto;">
                        <tr>
                            <td>No Transaksi</td>
                            <td> : </td>
                            <td id="transac_no" name="transac_no">{{$fpx_sellerOrderNo}}</td>
                        </tr>

                        <tr>
                            <td>Tarikh</td>
                            <td> : </td>
                            <td id="dt1"></td>
                        </tr>
                        
                        <tr>
                            <td>Nama Pembayar</td>
                            <td> : </td>
                            <td>{{$fpx_buyerName}}</td>
                        </tr>

                        <tr>
                            <td>Nombor Telefon Pembayar</td>
                            <td> : </td>
                            <td>{{$telno}}</td>
                        </tr>

                        <tr><td colspan="3"><hr></td></tr>

                        <tr>
                            <td>Nama Penerima</td>
                            <td> : </td>
                            <td>{{$organization->nama}}</td>
                        </tr>

                        <tr>
                            <td>Nombor Telefon Penerima</td>
                            <td> : </td>
                            <td>{{$organization->telno}}</td>
                        </tr>

                        <tr><td colspan="3"><hr></td></tr>

                        <tr>
                            <td>Deskripsi</td>
                            <td> : </td>
                            <td>{{$fpx_productDesc}}</td>
                        </tr>

                        <tr>
                            <td>Jumlah Bayaran</td>
                            <td> : </td>
                            <td>{{$fpx_txnAmount}} {{$fpx_txnCurrency}}</td>
                        </tr>
                    </table>
                </div>
                <br>
                <div class="text-center">
                    <button class="btn btn-danger mdi mdi-chevron-left-circle">Bayar</button>
                </div>
            </div>
        </form>
    </div>
</body>

</html>

<script>
    var today = new Date();
    document.getElementById("dt1").innerHTML = today.toLocaleString();

    $(document).ready(function(){
        $.ajaxSetup({
                headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });
    });
</script>