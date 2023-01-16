<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta content="Report Yuran" name="description" />
    <meta content="UTeM" name="author" />
    <title>PRiM | Laporan Yuran {{$data[0]->nama_kelas}}</title>

    <link rel="shortcut icon" href="{{ URL::asset('assets/images/logo/fav-logo-prim.png')}}">
    <link href="{{ URL::asset('assets/libs/chartist/chartist.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ URL::asset('assets/libs/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ URL::asset('assets/css/checkbox.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ URL::asset('assets/css/accordion.css') }}" rel="stylesheet" type="text/css" />
    @include('layouts.head')

    <style>
        .table td,
        .table th {
            padding: .3rem !important;
            border: 1px solid black !important;
            border-collapse: collapse !important;
        }
    </style>
</head>

<body style="background-color: white; color: black">
    <div class="container">
        <div class="row mt-3">
            <div class="col-12">
                <div class="card mb-1">
                    <div class="card-body py-5">
                        <div class="row">
                            <div class="col-12 pt-3">
                                <center>
                                    <img src="{{ URL::asset('/organization-picture/'.$organization->organization_picture) }}" height="80" alt="" />
                                </center>
                                <h4 style="text-align: center">{{$organization->nama}}</h4>
                                <h5 style="text-align: center">{{$organization->address}}, {{$organization->postcode}} {{$organization->state}}</h5>
                                <br>
                                <br>
                                <span> Laporan {{$yuran->name}} </span>

                                <br>
                                <br>
                                <table class="table table-bordered table-striped" style=" width:100%; color: black">
                                    <tr style="text-align: center">
                                        <th style="width:3%"> No. </th>
                                        <th>Nama</th>
                                        <th>Kelas</th>
                                        <th>Jantina</th>
                                        <th>Status</th>
                                    </tr>
                                    @foreach ($data as $item)
                                    <tr>
                                        <td style="text-align: center"> {{ $loop->iteration }}.</td>
                                        <td>
                                            <div class="pl-2"> {{ $item->nama }} </div>
                                        </td>
                                        <td>
                                            <div class="pl-2"> {{ $item->nama_kelas }} </div>
                                        </td>
                                        <td>
                                            <div class="pl-2"> {{ $item->gender }} </div>
                                        </td>
                                        <td style="text-align: center">{{ $item->status }}</td>

                                    </tr>
                                    @endforeach


                                </table>

                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 pt-5 text-center">
            <button class="btn btn-primary p-2 w-10 mx-2 btn-fill" style="font-size:18px"
                onclick="window.print();">
                <span class="mdi mdi-file-pdf"> Print </span>
            </button>
            <a href="{{ route('fees.searchreport') }}">
                <button class="btn btn-danger p-2 w-10 mx-2" style="font-size:18px;">
                    <span class="mdi mdi-chevron-left-circle"> Kembali</span>
                </button>
            </a>
        </div>
    </div>
</body>

</html>