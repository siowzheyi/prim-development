<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Organization;
use App\Models\Recurring;
use Yajra\DataTables\DataTables;

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
        //
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
                    ->value('id');

        //if recurring existed got record
        if ($getType != null) {
            //QUESTION: how to auto update recurring fees date?
                $recurring_id = $getType;
        }  
        else{
            $recurring_created =Recurring::create([
                    'name'          => $request->get('recurring_type'),
                    'start_date'    => $request->get('start_date'),
                    'end_date'      => $request->get('end_date')
                ]);
               
        }
        $expenses = Expenses::create([
            'name'          =>  $request->get('name'),
            'description'   =>  $request->get('description'),
            'amount'        =>  $request->get('amount'),
            'start_date'    =>  $request->get('start_date'),
            'end_date'      =>  $request->get('end_date'),
            'recurring_id'  =>  $recurring_created->id,
            'organization_id'=> $request->get('organization')

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

            // dd($expenses->id);
            foreach($related_student as $row)
            {

                $result = DB::table('student_expenses')
                ->insert([
                    'status'             => 'unpaid',
                    'expenses_id'        => $expenses->id, 
                    'class_student_id'   => $related_student[$i++]->id           
                ]);
                
            }

            //if successfully updated expenses link with students
            if($result)
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
                'recurrings.name as recurring_type',
                'recurrings.start_date as start_date_recurring',
                'recurrings.end_date as end_date_recurring')
                ->where('expenses.id', $id)
                ->first();

            $organization = $this->getOrganizationByUserId();

            return view('pentadbir.recurring-fees.update', compact('expenses', 'organization', 'id'));
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
                        ->value('id');

            //if recurring existed got record
            if ($getType != null) {
                //QUESTION: how to auto update recurring fees date?
                    $recurring_id = $getType;
            }  
            else{
                    $recurring_created = Recurring::create([
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
                'recurring_id'  =>  $recurring_created->id,
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
            $hasOrganizaton = $request->hasOrganization;
            $payStatus = $request->payStatus;            

            // create recurring data that only contain data which have recurring id
           

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

            if ($oid != '' && !is_null($hasOrganizaton)) {
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
                                    //
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
            ->where('organization_user.role_id',6)
            ->where('organization_user.status',1)
            ->where('expenses.status','active')
            ->where('student_expenses.status','unpaid')
            ->where('organization_user_student.organization_user_id',$userid)
            ->select('expenses.*','recurrings.name as recurring_name')
            ->get();

            return view('parent.recurring-fees.index', compact('list', 'organization', 'getfees_by_parent','recurring_type'));
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
    public function getpayStatusDatatable(Request $request)
    {
        if (request()->ajax()) {
            $oid = $request->oid;
            $fromTime = $request->fromTime;
            $untilTime = $request->untilTime;
            //assume that recurring type we get "annually","semester","monthly"
            $recurringType = $request->recurring_type;
            $hasOrganizaton = $request->hasOrganization;
            $payStatus = $request->payStatus;            

            // create recurring data that only contain data which have recurring id
           

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

            if ($oid != '' && !is_null($hasOrganizaton)) {
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
                                    //
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


}
