<!-- ========== Left Sidebar Start ========== -->
<div class="vertical-menu">

    <div data-simplebar class="h-100">

        <!--- Sidemenu -->
        <div id="sidebar-menu">
            <!-- Left Menu Start -->
            <ul class="metismenu list-unstyled" id="side-menu">
                
            <!-- micole recurring expenses system -->
            @role('Superadmin|Pentadbir|Guru|Penjaga')

            <li>
                <a href="javascript: void(0);" class="has-arrow waves-effect">
                    <i class="fas fa-file-invoice-dollar"></i>
                    <span>Yuran Berulangan</span>
                </a>
                <ul class="sub-menu mm-collapse" aria-expanded="false">
                    @role('Superadmin|Penjaga')
                    <li>
                        <a href="{{ route('recurring_fees.related_fees') }}" class=" waves-effect">
                            <i class="far fa-credit-card"></i>
                            <span>Bayar</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('recurring_fees.indexTransaction') }}" class=" waves-effect">
                            <i class="ti-clipboard"></i>
                            <span>Sejarah Bayaran</span>
                        </a>
                    </li>
                    @endrole
                    @role('Superadmin|Pentadbir|Admin')
                     <li>
                        <a href="{{ route('recurring_fees.index') }}" class=" waves-effect">
                        <i class="fas fa-child"></i>
                        <span>Butiran Perbelanjaan</span>
                        </a>
                    </li> 
                    <li>
                        <a href="{{ route('recurring_fees.reportExpenses') }}" class=" waves-effect">
                        <i class="fas fa-child"></i>
                        <span>Laporan Perbelanjaan</span>
                        </a>
                    </li> 
                    @endrole
                </ul>
            </li>
            @endrole
            

            {{-- @role('Regular Merchant Admin')
            <li>
                <a href="javascript: void(0);" class="has-arrow waves-effect">
                    <i class="mdi mdi-account-edit"></i>
                    <span>Urus Peniaga</span>
                </a>
                <ul class="sub-menu mm-collapse" aria-expanded="false">
                    <li>
                        <a href="#" class=" waves-effect">
                            <i class="ti-home"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('admin-reg.operation-hour') }}" class=" waves-effect">
                            <i class="ti-timer"></i>
                            <span>Waktu Operasi</span>
                        </a>
                    </li>
                    <li>
                        <a href="#" class=" waves-effect">
                            <i class="ti-package"></i>
                            <span>Urus Produk</span>
                        </a>
                    </li>
                    <li>
                        <a href="#" class=" waves-effect">
                            <i class="ti-email"></i>
                            <span>Pesanan</span>
                        </a>
                    </li>
                </ul>  
            </li>
            @endrole --}}

            {{-- @role('Superadmin|Koop Admin')
            <li>
            <a href="javascript: void(0);" class="has-arrow waves-effect">
                        <i class="fas fa-file-invoice-dollar"></i>
                        <span>Koop Admin</span>
                    </a>

            <ul class="sub-menu mm-collapse" aria-expanded="false">
                <li>
                    <a href="{{ route('koperasi.indexAdmin') }}" class=" waves-effect">
                    <i class="typcn typcn-pencil"></i>
                    <span>Produk</span>
                    </a>
                </li>
           
                <li>
                    <a href="{{route('koperasi.indexOpening')}}" class=" waves-effect">
                    <i class="fas fa-archway"></i>
                    <span>Hari Dibuka</span>
                    </a>
                </li>

                <li>
                    <a href="{{route('koperasi.indexConfirm')}}" class=" waves-effect">
                    <i class="fas fa-check-square"></i>
                    <span>Pengesahan</span>
                    </a>
                </li>
            </ul>
            </li>
            @endrole --}}
            
            {{-- <li>
                <a href="javascript: void(0);" class="has-arrow waves-effect">
                    <i class="mdi mdi-store"></i>
                    <span>Peniaga</span>
                </a>
                <ul class="sub-menu mm-collapse" aria-expanded="false">
                    <li>
                        <a href="{{ route('merchant.regular.index') }}" class=" waves-effect">
                            <i class="ti-bookmark-alt"></i>
                            <span>Semua Peniaga</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('merchant.all-orders') }}" class=" waves-effect">
                            <i class="ti-email"></i>
                            <span>Pesanan</span>
                        </a>
                    </li>
                </ul>  
            </li> --}}

            {{-- <!-- @role('Superadmin|Penjaga') -syah punye
            <li>
                <a href="javascript: void(0);" class="has-arrow waves-effect">
                    <i class="mdi mdi-border-color"></i>
                    <span>Kooperasi</span>
                </a>
                <ul class="sub-menu mm-collapse" aria-expanded="false">
                    <li>
                        <a href="{{ route('koperasi.index') }}" class=" waves-effect">
                            <i class="mdi mdi-book"></i>
                            <span>Koperasi Sekolah</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('koperasi.order') }}" class=" waves-effect">
                            <i class="ti-email"></i>
                            <span>Pesanan Koperasi</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('koperasi.history') }}" class=" waves-effect">
                            <i class="ti-clipboard"></i>
                            <span>Sejarah Koperasi</span>
                        </a>
                    </li>
                </ul>
            </li>
            @endrole --> --}}

            {{-- @role('Superadmin|Penjaga') <!--haziq nye-->
            <li>
                <a href="javascript: void(0);" class="has-arrow waves-effect">
                    <i class="mdi mdi-border-color"></i>
                    <span>Koperasi</span>
                </a>
                <ul class="sub-menu mm-collapse" aria-expanded="false">
                    <li>
                        <a href="{{ route('koperasi.index') }}" class=" waves-effect">
                            <i class="mdi mdi-book"></i>
                            <span>Koperasi Sekolah</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('koperasi.order') }}" class=" waves-effect">
                            <i class="ti-email"></i>
                            <span>Pesanan Koperasi</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('koperasi.history') }}" class=" waves-effect">
                            <i class="ti-clipboard"></i>
                            <span>Sejarah Koperasi</span>
                        </a>
                    </li>
                </ul>
            </li>
            @endrole --}}

            {{-- @role('Superadmin|Pentadbir|Guru')
                <li>
                    <a href="{{ route('chat-user') }}" class=" waves-effect">
            <i class="mdi mdi-chat-outline"></i>
            <span>Chat</span>
            </a>
            </li>
            @endrole --}}

            {{-- @role('Superadmin|Ibu|Bapa|Penjaga')
                <li>
                    <a href="{{ route('billIndex') }}" class=" waves-effect">
            <i class="mdi"></i>
            <span>Bill Design</span>
            </a>
            </li>
            @endrole --}}


            <!-- <li>
                    <a href="" class=" waves-effect">
                        <i class="ti-clipboard"></i>
                        <span>Derma</span>
                    </a>
                </li> -->

            </ul>
        </div>
    </div>
</div>