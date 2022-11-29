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

        $recurring_type = ['Semua','Tidak Berulang','Setiap Bulan','Setiap Tahun','Setiap Semester'];



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
            'name'         =>  'required',
            'recurring'     =>  'required',
            'end_date'       =>  'required',
            'start_date'   =>  'required',
            'organization' =>  'required',
            'amount' =>  'required',
        ]);

        $checkRecurring = Recurring::all();

        //if the expenses is recurring
        if ($request->get('recurring')=="is_recurring")
        {
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
                    $recurring = Recurring::create([
                        'name'          => $request->get('recurring_type'),
                        'start_date'    => $request->get('start_date'),
                        'end_date'      => $request->get('end_date')
                    ]);
                    $recurring_id = $recurring->value('id');
            }
            $expenses = Expenses::create([
                'name'          =>  $request->get('name'),
                'description'   =>  $request->get('description'),
                'amount'        =>  $request->get('amount'),
                'start_date'    =>  $request->get('start_date'),
                'end_date'      =>  $request->get('end_date'),
                'recurring_id'  =>  $recurring_id,
                'organization_id'=> $request->get('organization')

            ]);
        }
        //if the expenses is not recurring
        else{
            $expenses = Expenses::create([
                'name'          =>  $request->get('name'),
                'description'   =>  $request->get('description'),
                'amount'        =>  $request->get('amount'),
                'start_date'    =>  $request->get('start_date'),
                'end_date'      =>  $request->get('end_date'),
                'organization_id'=> $request->get('organization')
            ]);
        }

        //if the expenses successfully created
        if($expenses)
        {
            
            $arrayStudent = DB::table('class_student')
                            ->get();

            foreach($arrayStudent as $row)
            {
                $related_student=DB::table('class_student as cs')
                            ->join('class_organization as co','co.id','=','cs.organclass_id')
                            ->where('co.organization_id','=',$request->get('organization'))
                            ->value('cs.id');
              
                $result = DB::table('student_expenses')
                ->insert([
                    'status'             => 'unpaid',
                    'expenses_id'        => $expenses->value('id'), 
                    'class_student_id'   => $related_student           
                ]);
            }

            //if successfully updated expenses link with students
            if($result)
                return redirect('/recurring_fees')->with('success', 'Perbelanjaan telah ditambahkan');
            else
                return redirect('/recurring_fees')->with('fail','Perbelanjaan tidak ditambahkan kenana tiada pelajar dalam sekolah ini');
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
            $start = date('Y-m-d');
            
            $expenses = Expenses::where('expenses.id', $id)
            // ->select('id', 'name', 'description', 'amount', 'organization_id')
            ->first();

            $recurring_type = Expenses::join('recurrings','recurrings.id','=','expenses.recurring_id')
                // ->select('recurrings.name as recurring_type')
                ->where('expenses.id', $id)
                ->value('recurrings.name');

            $organization = $this->getOrganizationByUserId();


            return view('pentadbir.recurring-fees.update', compact('expenses', 'organization', 'recurring_type', 'id','start'));
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
            'recurring'     =>  'required',
            'end_date'       =>  'required',
            'start_date'   =>  'required',
            'organization' =>  'required',
            'amount' =>  'required',
        ]);

        $checkRecurring = Recurring::all();

        //if the expenses is recurring
        if ($request->get('recurring')=="is_recurring")
        {
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
                    $recurring = Recurring::create([
                        'name'          => $request->get('recurring_type'),
                        'start_date'    => $request->get('start_date'),
                        'end_date'      => $request->get('end_date')
                    ]);
                    $recurring_id = $recurring->value('id');
            }
            $expenses = Expenses::where('id',$id)
                ->update([
                'name'          =>  $request->get('name'),
                'description'   =>  $request->get('description'),
                'amount'        =>  $request->get('amount'),
                'start_date'    =>  $request->get('start_date'),
                'end_date'      =>  $request->get('end_date'),
                'recurring_id'  =>  $recurring_id,
                'organization_id'=> $request->get('organization')

            ]);
        }
        //if the expenses is not recurring
        else{
            $expenses = Expenses::where('id',$id)
            ->update([
                'name'          =>  $request->get('name'),
                'description'   =>  $request->get('description'),
                'amount'        =>  $request->get('amount'),
                'start_date'    =>  $request->get('start_date'),
                'end_date'      =>  $request->get('end_date'),
                'organization_id'=> $request->get('organization')
            ]);
        }

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

            // create recurring data that only contain data which have recurring id
            $recurring_data = DB::table('expenses')
                    ->join('recurrings','recurrings.id','=','expenses.recurring_id')
                    ->select('expenses.id as id','expenses.name as name','expenses.description as description','expenses.amount as amount',
                                'expenses.start_date as start_date','expenses.end_date as end_date','expenses.status as status',
                                'expenses.recurring_id as recurring_id','expenses.organization_id as organization_id','recurrings.id as recurrings_id',
                                'recurrings.name as recurrings_name')
                    ->where('expenses.organization_id','=',$oid)
                    ->orderBy('expenses.start_date');

            $data = DB::table('expenses')
                    ->where('organization_id',$oid)
                    ->orderBy('start_date');

            //['name', 'description', 'amount','start_date','end_date','status_recurring','status','action'];

            if ($oid != '' && !is_null($hasOrganizaton)) {
                  // if user select any time period AND select non recurring type
                  if (($fromTime == '' || $untilTime == '') &&  ($recurringType =='Tidak Berulang')) {
                    $data = $data;
                }
                // if user select time period AND select non recurring type
                elseif (($fromTime != '' && $untilTime != '') &&  ($recurringType =='Tidak Berulang')) {
                    $data = $data
                    ->where('expenses.start_date', '>=', $fromTime)
                    ->where('expenses.end_date', '<=', $untilTime);
                }
                
                // if user got select time period AND select all or didnt select recurring type
                elseif (($fromTime != '' && $untilTime != '') &&  ($recurringType == 'Semua' || $recurringType == '')) {
                    $data = $data
                            ->where('expenses.start_date', '>=', $fromTime)
                            ->where('expenses.end_date', '<=', $untilTime);
                }
                // if user select any time period AND select all or didnt select recurring type
                elseif (($fromTime == '' || $untilTime == '') &&  ($recurringType == 'Semua' || $recurringType == '')) {
                    $data = $data;
                }
                // if user select time period AND select recurring type
                elseif (($fromTime != '' && $untilTime != '') &&  ($recurringType != 'Semua' && $recurringType != '')) {
                    $data = $recurring_data
                    ->where('expenses.start_date', '>=', $fromTime)
                    ->where('expenses.end_date', '<=', $untilTime);
                }
              
                else {
                    $data = $recurring_data;
                }


                // $recurring_type = ['Semua','Tidak Berulang','Setiap Bulan','Setiap Tahun','Setiap Semester'];

                if ($recurringType == 'Tidak Berulang' ) {
                    $data = $data
                            ->whereNull('expenses.recurring_id')
                            ->get();
                } elseif ($recurringType == 'Setiap Bulan') {
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
                    if ($row->recurring_id != null) {
                        $btn = '<div class="d-flex justify-content-center">';
                        $btn = $btn . '<span class="badge badge-success"> Berulang </span></div>';
                                    //
                        return $btn;
                    // else the expenses is not recurring
                    } else {
                        $btn = '<div class="d-flex justify-content-center">';
                        $btn = $btn . '<span class="badge badge-danger"> Tidak Berulang </span></div>';

                        return $btn;
                    }
                });

                $table->addColumn('status', function ($row) {
                    // if the expenses is active
                    if ($row->status == 'active') {
                        $btn = '<div class="d-flex justify-content-center">';
                        $btn = $btn . '<span class="badge badge-success"> Active </span></div>';

                        return $btn;
                    // else the expenses is not active
                    } else {
                        $btn = '<div class="d-flex justify-content-center">';
                        $btn = $btn . '<span class="badge badge-danger"> Inactive </span></div>';

                        return $btn;
                    }
                });

                $table->addColumn('action', function ($row) {
                    $token = csrf_token();
                    $btn = '<div class="d-flex justify-content-center">';
                    $btn = $btn . '<a href="' . route('recurring_fees.edit', $row->id) . '" class="btn btn-primary m-1">Ubah</a>';
                    $btn = $btn . '<button id="' . $row->id . '" data-token="' . $token . '" class="btn btn-danger m-1 destroyExpenses">Buang</button></div>';
                    return $btn;
                });
            }
            $table->rawColumns(['status_recurring','status','action']);
            return $table->make(true);
            
           
        }
    }

}
