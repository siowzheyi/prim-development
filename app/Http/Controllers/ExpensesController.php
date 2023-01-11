<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Organization;
use App\Models\Recurring;
use App\Models\Transaction;
use Yajra\DataTables\DataTables;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ParentPayStatusExport;
use App\Exports\ExpensesPayStatusExport;
use PDF;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\View;
use App\Models\Expenses;
use Illuminate\Database\Eloquent\Builder;


class ExpensesController extends Controller
{
    //
    public function index(){


        $organization = $this->getOrganizationByUserId();

        $minDate = date('Y-m-d', strtotime(Expenses::orderBy('start_date')
        ->value('start_date')));

        $maxDate = date('Y-m-d', strtotime(Expenses::orderBy('end_date', 'desc')
        ->value('end_date')));

        $recurring_type = ['Semua', 'Setiap Bulan','Setiap Tahun','Setiap Semester'];

        
        return view('pentadbir.recurring-fees.index', compact('recurring_type', 'minDate','maxDate', 'organization'));

    }
    public function create()
    {
        $organization = $this->getOrganizationByUserId();
        

        $start = date('Y-m-d');

      
        return view('pentadbir.recurring-fees.add',compact('start','organization'));
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'name'                      =>  'required',
            'recurring_type'             =>  'required',
            'end_date'                   =>  'required',
            'start_date'                 =>  'required',
            'end_date_recurring'          =>  'required',
            'start_date_recurring'       =>  'required',
            'organization'              =>  'required',
            'amount'                    =>  'required',
        ]);

        

        //check if the recurring existed in the db or not
        $getType = Recurring::where('name','=',$request->get('recurring_type'))
                    ->where('start_date','=',$request->get('start_date_recurring'))
                    ->where('end_date','=',$request->get('end_date_recurring'))
                    ->first();

        //if recurring existed got record
        if ($getType != null) {
            //QUESTION: how to auto update recurring fees date?
                $recurring_id = $getType;
        }  
        else{
            $recurring_id =Recurring::create([
                    'name'          => $request->get('recurring_type'),
                    'start_date'    => $request->get('start_date'),
                    'end_date'      => $request->get('end_date')
                ]);
               
        }
        
        if($request->get('start_date')>date('Y-m-d',strtotime(now())))
        {
            $status = 'inactive';
        }
        else{
            $status = 'active';
        }
        $expenses = Expenses::create([
            'name'          =>  $request->get('name'),
            'description'   =>  $request->get('description'),
            'amount'        =>  $request->get('amount'),
            'start_date'    =>  $request->get('start_date'),
            'end_date'      =>  $request->get('end_date'),
            'recurring_id'  =>  $recurring_id->id,
            'organization_id'=> $request->get('organization'),
            'status'        =>  $status

        ]);
        
        //if the expenses successfully created
        if($expenses)
        {
            $related_student = DB::table('class_student as cs')
                            ->join('class_organization as co','co.id','=','cs.organclass_id')
                            ->where('co.organization_id','=',$request->get('organization'))
                            ->select('cs.id')
                            ->get();
            $i=0;
            $result=0;

            foreach($related_student as $row)
            {

                $result = DB::table('student_expenses')
                ->insert([
                    'status'             => 'unpaid',
                    'expenses_id'        => $expenses->id, 
                    'class_student_id'   => $row->id           
                ]);
                
            }

            //if successfully updated expenses link with students
            if($result!=0)
                return redirect('/recurring_fees')->with('success', 'Perbelanjaan telah ditambahkan');
            else
                return redirect('/recurring_fees')->with('fail','Perbelanjaan tidak ditambahkan kerana tiada pelajar dalam sekolah ini');
        }
        else
             return redirect('/recurring_fees')->with('fail','Perbelanjaan tidak ditambahkan');
    }
     /**
        * Display the specified resource.
        *
        * @param  int  $id
        * @return Response
        */
        public function show($id)
        {
            //
        }
    
        /**
            * Show the form for editing the specified resource.
            *
            * @param  int  $id
            * @return Response
            */
        public function edit($id)
        {
            $expenses = Expenses::join('recurrings','recurrings.id','=','expenses.recurring_id')
                ->select('expenses.*',
                'recurrings.name as recurring_type')
                ->where('expenses.id', $id)
                ->first();
            
            $start_date_recurring = date('Y-m-d', strtotime(Expenses::join('recurrings','recurrings.id','=','expenses.recurring_id')
            ->where('expenses.id', $id)
            ->value('recurrings.start_date')));

            $end_date_recurring = date('Y-m-d', strtotime(Expenses::join('recurrings','recurrings.id','=','expenses.recurring_id')
            ->where('expenses.id', $id)
            ->value('recurrings.end_date')));

            $start_date = date('Y-m-d', strtotime(Expenses::join('recurrings','recurrings.id','=','expenses.recurring_id')
            ->where('expenses.id', $id)
            ->value('expenses.start_date')));

            $end_date = date('Y-m-d', strtotime(Expenses::join('recurrings','recurrings.id','=','expenses.recurring_id')
            ->where('expenses.id', $id)
            ->value('expenses.end_date')));

            $organization = $this->getOrganizationByUserId();

            return view('pentadbir.recurring-fees.update', compact('expenses', 'organization', 'id','start_date','end_date','start_date_recurring','end_date_recurring'));
        }
        /**
        * Update the specified resource in storage.
        *
        * @param  int  $id
        * @return Response
        */
    public function update(Request $request, $id)
    {

        $this->validate($request, [
            'name'         =>  'required',
            'recurring_type'     =>  'required',
            'end_date_recurring'       =>  'required',
            'start_date_recurring'   =>  'required',
            'end_date'       =>  'required',
            'start_date'   =>  'required',
            'organization' =>  'required',
        ]);

        $checkRecurring = Recurring::all();

        
            //check if the recurring existed in the db or not
            $getType = Recurring::where('name','=',$request->get('recurring_type'))
                        ->where('start_date','=',$request->get('start_date'))
                        ->where('end_date','=',$request->get('end_date'))
                        ->first();

            //if recurring existed got record
            if ($getType != null) {
                //QUESTION: how to auto update recurring fees date?
                    $recurring_id = $getType;
            }  
            else{
                    $recurring_id = Recurring::create([
                        'name'          => $request->get('recurring_type'),
                        'start_date'    => $request->get('start_date'),
                        'end_date'      => $request->get('end_date')
                    ]);
                    
            }
            $expenses = Expenses::where('id',$id)
                ->update([
                'name'          =>  $request->get('name'),
                'description'   =>  $request->get('description'),
                'start_date'    =>  $request->get('start_date'),
                'end_date'      =>  $request->get('end_date'),
                'recurring_id'  =>  $recurring_id->id,
                'organization_id'=> $request->get('organization')

            ]);
        

        if($expenses)
            return redirect('/recurring_fees')->with('success', 'The application has been updated');
         else 
            return redirect('/recurring_fees')->with('fail', 'The application has not been updated');
        
    }

    /**
        * Remove the specified resource from storage.
        *
        * @param  int  $id
        * @return Response
        */
    public function destroy($id)
    {
        //
        $result = Expenses::where('expenses.id', $id)
                            ->update(['status'=>"inactive"]);
        if ($result) {
            Session::flash('success', 'Perbelanjaan Berjaya Dipadam');
            return View::make('layouts/flash-messages');
        } else {
            Session::flash('error', 'Perbelanjaan Gagal Dipadam');
            return View::make('layouts/flash-messages');
        }
    }

    public function getOrganizationByUserId()
    {
        $userId = Auth::id();
        if (Auth::user()->hasRole('Superadmin')) {
            return Organization::all();
        } elseif (Auth::user()->hasRole('Pentadbir') || Auth::user()->hasRole('Guru')) {

            // user role pentadbir, guru and admin
            return Organization::whereHas('user', function ($query) use ($userId) {
                $query->where('user_id', $userId)->Where(function ($query) {
                    $query->where('organization_user.role_id', '=', 4)
                        ->Orwhere('organization_user.role_id', '=', 5)
                        ->Orwhere('organization_user.role_id','=',2);
                });
            })->get();
        } else {
            // user role ibu bapa
            return Organization::whereHas('user', function ($query) use ($userId) {
                $query->where('user_id', $userId)->where('role_id', '6');
            })->get();
        }
    }

    //get datatable for expenses
    public function getExpensesDatatable(Request $request)
    {
        if (request()->ajax()) {
            $oid = $request->oid;
            $fromTime = $request->fromTime;
            $untilTime = $request->untilTime;
            //assume that recurring type we get "annually","semester","monthly"
            $recurringType = $request->recurring_type;
            $payStatus = $request->payStatus;           
            $data = DB::table('expenses')
                ->join('recurrings','recurrings.id','=','expenses.recurring_id')
                ->join('student_expenses','student_expenses.expenses_id','=','expenses.id')
                ->select('expenses.*','recurrings.id as recurrings_id',
                            'recurrings.name as recurrings_name',
                             DB::raw("count(student_expenses.status) AS payStatus"))
                ->where('expenses.organization_id','=',$oid)
                ->where('expenses.status','active')
                ->orderBy('expenses.start_date')
                ->groupBy('expenses.id');
                // ->get();
            // $data = DB::table('expenses')
            //         ->where('organization_id',$oid)
            //         ->where('status','active')
            //         ->orderBy('start_date');

            //['name', 'description', 'amount','start_date','end_date','status_recurring','action'];

            if ($oid != '' ) {
                  // if user select any time period AND select non recurring type
                  if ($fromTime == '' || $untilTime == '') {
                    $data = $data;
                }
                // if user select time period AND select non recurring type
                elseif ($fromTime != '' && $untilTime != '') {
                    $data = $data
                    ->where('expenses.start_date', '>=', $fromTime)
                    ->where('expenses.end_date', '<=', $untilTime);
                }
            
                if($payStatus == 'paid' || $payStatus == 'unpaid')
                {
                    $data = $data
                    ->where('student_expenses.status',$payStatus);
                }

                // $recurring_type = ['Semua','Tidak Berulang','Setiap Bulan','Setiap Tahun','Setiap Semester'];

                if ($recurringType == 'Setiap Bulan') {
                    $data = $data
                            ->where('recurrings.name', '=', 'monthly')
                            ->get();
                } elseif ($recurringType == 'Setiap Tahun') {
                    $data = $data
                            ->where('recurrings.name', '=', 'annually')
                            ->get();
                } elseif ($recurringType == 'Setiap Semester') {
                    $data = $data
                            ->where('recurrings.name', '=', 'semester')
                            ->get();
                } else {
                    $data = $data->get();
                }
                $table = Datatables::of($data);

                $table->addColumn('status_recurring', function ($row) {
                    // if the expenses is recurring
                    if ($row->recurrings_name == 'monthly') {
                        $btn = '<div class="d-flex justify-content-center">';
                        $btn = $btn . '<span class="badge badge-success"> Setiap Bulan </span></div>';
                        return $btn;
                    // else the expenses is not recurring
                    } elseif ($row->recurrings_name == 'semester') {
                        $btn = '<div class="d-flex justify-content-center">';
                        $btn = $btn . '<span class="badge badge-success"> Setiap Semester </span></div>';

                        return $btn;
                    }
                    elseif ($row->recurrings_name == 'annually') {
                        $btn = '<div class="d-flex justify-content-center">';
                        $btn = $btn . '<span class="badge badge-success"> Setiap Tahun </span></div>';

                        return $btn;
                    }
                });

                $rawColumn = ['status_recurring'];
             
                if($payStatus == '')
                {
                    $table->addColumn('action', function ($row) {
                        $token = csrf_token();
                        $btn = '<div class="d-flex justify-content-center">';
                        $btn = $btn . '<a href="' . route('recurring_fees.edit', $row->id) . '" class="btn btn-primary m-1">Ubah</a>';
                        $btn = $btn . '<button id="' . $row->id . '" data-token="' . $token . '" class="btn btn-danger m-1 destroyExpenses">Buang</button></div>';
                        return $btn;

                        // $table->rawColumns(['status_recurring','action']);    
                    });
                    $rawColumn = ['status_recurring','action'];
                }
                
            $table->rawColumns($rawColumn);
            return $table->make(true);
            }
        }
    }

    public function related_fees()
    {
        $userid = Auth::id();

        // ************************* get list dependent from user id  *******************************

        $list = DB::table('organizations')
            ->join('organization_user', 'organization_user.organization_id', '=', 'organizations.id')
            ->join('users', 'users.id', '=', 'organization_user.user_id')
            ->join('organization_user_student', 'organization_user_student.organization_user_id', '=', 'organization_user.id')
            ->join('students', 'students.id', '=', 'organization_user_student.student_id')
            ->join('class_student', 'class_student.student_id', '=', 'students.id')
            ->join('class_organization', 'class_organization.id', '=', 'class_student.organclass_id')
            ->join('classes', 'classes.id', '=', 'class_organization.class_id')
            ->select('organizations.id as oid', 'organizations.nama as nschool', 'students.id as studentid', 'students.nama as studentname', 'classes.nama as classname','users.id')
            ->where('organization_user.user_id', $userid)
            ->where('organization_user.role_id',6)
            // ->orWhere('organization_user.role_id',1)
            ->where('organization_user.status', 1)
            ->orderBy('organizations.id')
            ->orderBy('classes.nama')
            ->get();

        // ************************* get list organization by parent  *******************************

        $organization = DB::table('organizations')
            ->join('organization_user', 'organization_user.organization_id', '=', 'organizations.id')
            ->join('organization_user_student', 'organization_user_student.organization_user_id', '=', 'organization_user.id')
            ->join('students', 'students.id', '=', 'organization_user_student.student_id')
            ->select('organizations.*', 'organization_user.user_id')
            ->distinct()
            ->where('organization_user.user_id', $userid)
            ->where('organization_user.role_id', 6)
            ->where('organization_user.status', 1)
            ->orderBy('organizations.nama')
            ->get();

            // dd($organization);

        // *********************** get expenses by recurring and status is unpaid ***************
        $get_class_student_id = DB::table('users')
                                    ->join('organization_user','organization_user.user_id','=','users.id')
                                    ->join('organization_user_student','organization_user_student.organization_user_id','=','organization_user.id')
                                    ->join('students','students.id','=','organization_user_student.student_id')
                                    ->join('class_student','class_student.student_id','=','students.id')
                                    // ->select('class_student.id')
                                    ->where('organization_user.user_id',$userid)
                                    ->pluck('class_student.id');
                                    // $array = array();
                                    // dd($get_class_student_id);
                                    // PROBLEM: HOW TO CAST THIS INTO ARRAY
        // $array2 = array_push($array,$get_class_student_id);
        // dd(array($get_class_student_id));
         $getfees_by_recurring = DB::table('expenses')
        ->join('recurrings','recurrings.id','=','expenses.recurring_id')
        ->join('student_expenses','student_expenses.expenses_id','=','expenses.id')
        ->join('class_student','class_student.id','=','student_expenses.class_student_id')

        ->join('organization_user','organization_user.organization_id','=','expenses.organization_id')
        ->join('organization_user_student','organization_user_student.organization_user_id','=','organization_user.id')
        ->whereIn('student_expenses.class_student_id',$get_class_student_id)

        ->where('organization_user.status',1)
        ->where('expenses.status','active')
        ->where('student_expenses.status','unpaid')
        ->select('expenses.*',
                    'student_expenses.id as student_expenses_id','recurrings.name as recurring_name','student_expenses.status',
                    'student_expenses.expenses_id','organization_user_student.id as parentid')
        ->groupBy('student_expenses.expenses_id')
        ->get();

        // ************************* get list fees  *******************************

            $recurring_type = ['monthly','semester','annually'];

            $getfees_by_parent = DB::table('expenses')
            ->join('recurrings','recurrings.id','=','expenses.recurring_id')
            ->join('student_expenses','student_expenses.expenses_id','=','expenses.id')
            ->join('class_student','class_student.id','=','student_expenses.class_student_id')
            ->join('organization_user','organization_user.organization_id','=','expenses.organization_id')
            ->join('organization_user_student','organization_user_student.organization_user_id','=','organization_user.id')
            ->join('students','students.id','=','organization_user_student.student_id')
            // ->orderby('expenses.recurring_id')
            ->distinct()
            // ->where('organization_user.role_id',6)
            ->where('organization_user.status',1)
            ->where('expenses.status','active')
            ->where('student_expenses.status','unpaid')
            ->where('organization_user.user_id',$userid)
            // ->where('student_expenses.')
            ->select('expenses.*','recurrings.name as recurring_name','student_expenses.status')
            ->get();
            // dd($get_class_student_id,$getfees_by_recurring);

            return view('parent.recurring-fees.index', compact('list', 'organization', 'getfees_by_parent','recurring_type','getfees_by_recurring'));
    }

    public function reportExpenses()
    {
        $organization = $this->getOrganizationByUserId();

        $minDate = date('Y-m-d', strtotime(Expenses::orderBy('start_date')
        ->value('start_date')));

        $maxDate = date('Y-m-d', strtotime(Expenses::orderBy('end_date', 'desc')
        ->value('end_date')));

        $recurring_type = ['Semua', 'Setiap Bulan','Setiap Tahun','Setiap Semester'];

        return view('pentadbir.recurring-fees.report.index', compact('recurring_type', 'minDate','maxDate', 'organization'));
    }

    public function payStatusExpenses($id)
    {
        // display user who paid and unpaid
        // display parentName, parentTel, studentName, className, payStatus, 
        $user = DB::table('student_expenses')
                ->join('class_student','class_student.id','=','student_expenses.class_student_id')
                ->join('class_organization','class_organization.id','=','class_student.organclass_id')
                ->join('classes','classes.id','=','class_organization.class_id')
                ->join('students','students.id','=','class_student.student_id')
                ->join('organization_user_student','organization_user_student.student_id','=','students.id')
                ->join('organization_user','organization_user.id','=','organization_user_student.organization_user_id')
                ->join('users','users.id','=','organization_user.user_id')
                ->where('student_expenses.expenses_id',$id)
                ->select('users.name as parentName','students.nama as studentName','classes.nama as className','students.parent_tel as parentTel','student_expenses.status as payStatus')
                ->get();

        $expenses = Expenses::where('id',$id)->first();

        return view('pentadbir.recurring-fees.report.payStatus', compact('user','expenses','id'));
    }

    //get datatable for pay status
    public function getPayStatusDatatable(Request $request)
    {
        if (request()->ajax()) {
            $expensesId = $request->expensesId;
            // all, paid unpaid
            $payStatus = $request->payStatus;

            // create recurring data that only contain data which have recurring id


            $data = DB::table('student_expenses')
            ->join('class_student', 'class_student.id', '=', 'student_expenses.class_student_id')
            ->join('class_organization', 'class_organization.id', '=', 'class_student.organclass_id')
            ->join('classes', 'classes.id', '=', 'class_organization.class_id')
            ->join('students', 'students.id', '=', 'class_student.student_id')
            ->join('organization_user_student', 'organization_user_student.student_id', '=', 'students.id')
            ->join('organization_user', 'organization_user.id', '=', 'organization_user_student.organization_user_id')
            ->join('users', 'users.id', '=', 'organization_user.user_id')
            ->select('users.name as parentName', 'students.nama as studentName', 'classes.nama as className', 'students.parent_tel as parentTel', 'student_expenses.status')
            ->where('student_expenses.expenses_id', $expensesId)
            ->orderBy('users.name');

            if ($payStatus == 'paid') {
                $data = $data->where('student_expenses.status', '=', 'paid')
                            ->get();
            } elseif ($payStatus == 'unpaid') {
                $data = $data->where('student_expenses.status', '=', 'unpaid')
                        ->get();
            } else {
                $data = $data->get();
            }
            $table = Datatables::of($data);

            $table->addColumn('payStatus', function ($row) {
                // if the expenses is recurring
                $btn = '<div class="d-flex justify-content-center">';
                if($row->status == 'unpaid')
                {
                    $btn = $btn . '<span class="badge badge-danger"> '.$row->status.' </span></div>';
                }
                else{
                    $btn = $btn . '<span class="badge badge-success"> '.$row->status.' </span></div>';
                }
                return $btn;
            });

            $table->rawColumns(['payStatus']);
            return $table->make(true);
            }
    }

    public function exportParentPayStatusReport(Request $request)
    {
        $this->validate($request, [
            'payStatus'      =>  'required',
            'expensesId'    =>  'required'
        ]);

        $filename = DB::table('expenses')
            ->where('expenses.id', $request->expensesId)
            ->value('expenses.name');

        // dd($filename);

        return Excel::download(new ParentPayStatusExport($request->payStatus, $request->expensesId), $filename . '.xlsx');
    }

    public function exportExpensesPayStatusReport(Request $request)
    {
        $this->validate($request, [
            'payStatus'      =>  'required',
            'organ'    =>  'required'
        ]);

        $filename = DB::table('expenses')
            ->join('organizations','organizations.id','=','expenses.organization_id')
            ->where('expenses.organization_id', $request->organ)
            ->value('organizations.nama');

        // dd($filename);

        return Excel::download(new ExpensesPayStatusExport($request->payStatus, $request->organ), 'Perbelanjaan '.$filename . '.xlsx');
    }

    public function printExpensesPayStatusReport(Request $request)
    {
        // $student_id = $request->student_id;

        $this->validate($request, [
            'organ'      =>  'required',
            'payStatus'      =>  'required',
        ]);

        $details = DB::table('organizations')
                    ->select('organizations.nama as schoolName',
                            'organizations.address as schoolAddress',
                            'organizations.postcode as schoolPostcode',
                            'organizations.state as schoolState')
                    ->where('id',$request->organ)
                    ->first();

        $list = DB::table('expenses')
                ->join('recurrings','recurrings.id','=','expenses.recurring_id')
                ->join('student_expenses','student_expenses.expenses_id','=','expenses.id')
                ->select('expenses.name as expenses_name','expenses.description','expenses.amount',
                            'recurrings.name as recurrings_name',
                            DB::raw("count(student_expenses.status) AS payStatus")
                            )
                ->where('expenses.organization_id','=',$request->organ)
                ->where('expenses.status','active')
                ->orderBy('expenses.start_date')
                ->groupBy('expenses.id');

        if($request->payStatus == 'all')
        {
            $list = $list->get();
        }
        elseif($request->payStatus == 'paid')
        {
            $list = $list->where('student_expenses.status','=','paid')->get();
        }
        elseif($request->payStatus == 'unpaid')
        {
            $list = $list->where('student_expenses.status','=','unpaid')->get();
        }

        $pdf = PDF::loadView('pentadbir.recurring-fees.report.reportExpensesPayStatusPdfTemplate', compact('list','details'));

        return $pdf->download('Report.pdf');
    }

    public function printParentPayStatusReport(Request $request)
    {
        // $student_id = $request->student_id;

        $this->validate($request, [
            'expensesId'      =>  'required',
            'payStatus'      =>  'required',
        ]);

        $details = DB::table('organizations')
                    ->join('expenses','expenses.organization_id','=','organizations.id')
                    ->select('organizations.nama as schoolName',
                            'organizations.address as schoolAddress',
                            'organizations.postcode as schoolPostcode',
                            'organizations.state as schoolState',
                            'expenses.name as expensesName')
                    ->where('expenses.id',$request->expensesId)
                    ->first();

        $list = DB::table('student_expenses')
                    ->join('class_student','class_student.id','=','student_expenses.class_student_id')
                    ->join('class_organization','class_organization.id','=','class_student.organclass_id')
                    ->join('classes','classes.id','=','class_organization.class_id')
                    ->join('students','students.id','=','class_student.student_id')
                    ->join('organization_user_student','organization_user_student.student_id','=','students.id')
                    ->join('organization_user','organization_user.id','=','organization_user_student.organization_user_id')
                    ->join('users','users.id','=','organization_user.user_id')
                    ->where('student_expenses.expenses_id',$request->expensesId)
                    ->select('users.name as parentName','students.parent_tel as parentTel','students.nama as studentName','classes.nama as className','student_expenses.status as payStatus');
                    
        if($request->payStatus == 'all')
        {
            $list = $list->get();
        }
        elseif($request->payStatus == 'paid')
        {
            $list = $list->where('student_expenses.status','=','paid')->get();
        }
        elseif($request->payStatus == 'unpaid')
        {
            $list = $list->where('student_expenses.status','=','unpaid')->get();
        }

        $pdf = PDF::loadView('pentadbir.recurring-fees.report.reportParentPayStatusPdfTemplate', compact('list','details'));

        return $pdf->download('Report '.$details->expensesName.'.pdf');
    }

    public function printReceipt(Request $request){
        // dd(123);
        $this->validate($request, [
            'user_expenses_array'      =>  'required'
        ]);
      
        if(str_contains($request->user_expenses_array,','))
        {
            $array = array();
            $case= explode(",", $request->user_expenses_array);
            $array = array_merge($array, $case);
        }
        else{
            $array = $request->user_expenses_array;
        }

        $details = DB::table('user_expenses')
                    ->join('user_expenses_item as item','item.user_expenses_id','=','user_expenses.id')
                    ->join('expenses','expenses.id','=','item.expenses_id')
                    ->join('organizations','organizations.id','=','expenses.organization_id')
                    ->join('organization_user_student as ous','ous.id','=','user_expenses.organization_user_student_id')
                    ->join('organization_user','organization_user.id','=','ous.organization_user_id')
                    ->join('users','users.id','=','organization_user.user_id')
                    ->select('organizations.nama as schoolName',
                    'organizations.address as schoolAddress',
                    'organizations.postcode as schoolPostcode',
                    'organizations.state as schoolState',
                    'users.name as parentName','organizations.telno as telno')
                    ->whereIn('item.user_expenses_id',$array)
                    ->first();

        $student = DB::table('user_expenses')
                    ->join('organization_user_student','organization_user_student.id','=','user_expenses.organization_user_student_id')
                    ->join('students','students.id','=','organization_user_student.student_id')
                    ->join('class_student','class_student.student_id','=','students.id')
                    ->join('class_organization','class_organization.id','=','class_student.organclass_id')
                    ->join('classes','classes.id','=','class_organization.class_id')
                    ->whereIn('user_expenses.id',$array)
                    ->select('students.nama as studentName','classes.nama as className')
                    ->get();

        $list = DB::table('user_expenses_item as item')
                    ->join('expenses','expenses.id','=','item.expenses_id')
                    ->join('user_expenses','item.user_expenses_id','=','user_expenses.id')
                    ->join('recurrings','recurrings.id','=','expenses.recurring_id')
                    ->join('transactions','transactions.id','=','user_expenses.transaction_id')
                    ->whereIn('item.user_expenses_id',$array)
                    ->select('transactions.transac_no as transac_no','user_expenses.total_amount as total_amount',
                    'expenses.name as expensesName','expenses.amount as expensesAmount',
                    'expenses.description','recurrings.name as recurringType')
                    ->get();
                    
        $date = date('Y-m-d', strtotime(now()));

        $total = DB::table('user_expenses')
        ->whereIn('user_expenses.id',$array)
        ->pluck('total_amount');

        $count = DB::table('user_expenses')
        ->whereIn('user_expenses.id',$array)->count();

        $total_amount = 0;
        $myArray = json_decode(json_encode($total), true);
        $value = array_map('intval',$myArray);
        // dd($value);
        for($i = 0; $i < $count; $i++)
        {
            $total_amount = $total_amount + $value[$i];
        }
        // dd($total_amount,$count);


        $pdf = PDF::loadView('parent.recurring-fees.receipt', compact('list','details','date','student','total_amount'));

        // dd($pdf);
        return $pdf->download('Resit '.$details->parentName.'.pdf');
    }

    public function paymentFake(Request $request){
        // dd($request->all());

        // ************  id from value checkbox (hidden) **************
        $user_id_and_expenses_id  = collect($request)->get('user_id_and_expenses_id');
        //oid_and_student_id
        $oid_and_student_id  = collect($request)->get('oid_and_student_id');
        $size = sizeof($user_id_and_expenses_id);
        $size_student = sizeof($oid_and_student_id);
        // dd($size_cb, $data_cb);
        $check=0;
        
        $user_expenses_id = array();
        
        for($j=0;$j<$size_student;$j++)
        {
            $oid = array();
        $student_id  = array();
        $expenses_id  = array();
        $user_id   = array();
        $total_amount = 0;
        $check++;
       
            for ($i = 0; $i < $size; $i++) {

                //want seperate data from request
                //case 0 = student id
                //case 1 = fees id
                //format req X-X
    
                $case            = explode("-", $user_id_and_expenses_id[$i]);
                $user_id[]       = $case[0];
                $expenses_id[]   = $case[1];
                $case2           = explode("-", $oid_and_student_id[$j]);
                $oid[]           = $case2[0];
                $student_id[]    = $case2[1];
                
                //update transaction table
                $random_code = uniqid();
                $user = DB::table('users')
                        ->where('id',$user_id[$i])
                        ->first();
                        
                $expenses = Expenses::where('id',$expenses_id[$i])
                            ->first();
                          
                $total_amount = $total_amount + $expenses->amount;
               
                     
                $organ_user_student = DB::table('organization_user_student')
                                        ->join('organization_user','organization_user.id','=','organization_user_student.organization_user_id')
                                        ->where('organization_user.organization_id',$oid[$i])
                                        ->where('organization_user.user_id',$user_id[$i])
                                        ->where('organization_user_student.student_id',$student_id[$i])
                                        ->value('organization_user_student.id');
                                                   
                if($i==0)
                {
                    //only allow run once
                    $new_transaction =  DB::table('transactions')->insertGetId([
                        'nama'  =>  'Transaction'.$random_code,
                        'user_id'   =>  $user->id,
                        'username'  =>  $user->username,
                        'telno'     =>  $user->telno,
                        'transac_no'=>  $random_code
                    ]);

                    //insert user expenses
                    $new_user_expenses = DB::table('user_expenses')
                    ->insertGetId([
                        'organization_user_student_id' =>  $organ_user_student,
                        'transaction_id'        =>  $new_transaction,
                        'payment_type_id'       =>  4,
                        'status'                =>  'active',
                        'total_amount'          => 0
                    ]);
                }
               
                $new_user_expenses_item = DB::table('user_expenses_item')
                                            ->insert([
                                                'status'    =>  'active',
                                                'expenses_id'=> $expenses->id,
                                                'user_expenses_id'  =>  $new_user_expenses
                                            ]);
                                            
                //update student_expenses status
                $class_student = DB::table('class_student')
                                ->where('student_id',$student_id[$i])
                                ->first();
                $update = DB::table('student_expenses')
                            ->where('class_student_id',$class_student->id)
                            ->where('expenses_id',$expenses->id)
                            ->update(['status'=>'paid']);
               
            }
            // update transaction and user_expenses total amount
            $update_transaction = DB::table('transactions')
            ->where('id',$new_transaction)
            ->update(['amount'=>$total_amount]);

            $update_user_expenses = DB::table('user_expenses')
            ->where('id',$new_user_expenses)
            ->update(['total_amount'=>$total_amount]);

            $update_payment = DB::table('payments')
                            ->insert([
                                'nama'  =>  'payment'.$new_transaction,
                                'total_amount'  =>  $total_amount,
                                'status'    =>  'complete',
                                'transac_id'    =>  $new_transaction
                            ]);

            array_push($user_expenses_id,$new_user_expenses);
           
        }
        // dd($user_expenses_id);
        // $update_total_amount = DB::table('user_expenses')->where('id',$new_user_expenses)->update(['total_amount'=>$total_amount]);
        // return receipt needed info --> user_expenses_id
        return $user_expenses_id;
    }
}
