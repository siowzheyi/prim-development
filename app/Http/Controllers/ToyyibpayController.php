<?php

namespace App\Http\Controllers;

use Omnipay\Omnipay;
use App\User;
use App\Models\Order;
use App\Models\Fee_New;
use App\Models\Student;
use App\Models\Donation;
use App\Mail\OrderReceipt;
use App\Mail\MerchantOrderReceipt;
use App\Models\PgngOrder;
use App\Models\Transaction;
use App\Models\Organization;
use Illuminate\Http\Request;
use App\Mail\DonationReceipt;
use App\Models\Dev\DevTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Redirect;
use phpDocumentor\Reflection\Types\Null_;
use App\Http\Controllers\AppBaseController;
use League\CommonMark\Inline\Parser\EscapableParser;

class ToyyibpayController extends Controller
{
    //
    private $gateway;

    public function __construct(){
        $this->gateway = Omnipay::create('PayPal_Rest');
        $this->gateway->setClientId(config('toyyibpay.PAYPAL_CLIENT_ID'));
        $this->gateway->setSecret(config('toyyibpay.PAYPAL_CLIENT_SECRET'));
        $this->gateway->setTestMode(true);
    }

    //handle payment
    public function pay(Request $request){
        $getstudentfees = ($request->student_fees_id) ? $request->student_fees_id : "";
        $getparentfees  = ($request->parent_fees_id) ? $request->parent_fees_id : "";

        $user = User::find(Auth::id());
        $organization = Organization::find($request->o_id);
        
        $fpx_buyerEmail         = $user->email;
        $telno                  = $user->telno;
        $fpx_buyerName          = User::where('id', '=', Auth::id())->pluck('name')->first();
        $fpx_sellerExOrderNo    = $request->desc . "_" . date('YmdHis');
        $fpx_sellerOrderNo      = "YSPRIM" . date('YmdHis') . rand(10000, 99999);
        $fpx_txnAmount          = $request->amount;
        $fpx_txnCurrency        = config('toyyibpay.PAYPAL_CURRENCY');
        $fpx_checkSum           = "";
        $fpx_buyerBankId        = $request->bankid;
        $fpx_buyerBankBranch    = "";
        $fpx_buyerAccNo         = "";
        $fpx_buyerId            = "";
        $fpx_makerName          = "";
        $fpx_productDesc        = $request->desc;
        $fpx_version            = "6.0";

        $transaction                    = new Transaction();
        $transaction->nama              = $fpx_sellerExOrderNo;
        $transaction->description       = $fpx_sellerOrderNo;
        $transaction->transac_no        = NULL;
        $transaction->datetime_created  = now();
        $transaction->amount            = $fpx_txnAmount;
        $transaction->status            = 'Pending';
        $transaction->email             = $fpx_buyerEmail;
        $transaction->telno             = $telno;
        $transaction->user_id           = $user ? $user->id : null;
        $transaction->username          = strtoupper($fpx_buyerName);
        $transaction->fpx_checksum      = NULL;
        $transaction->user_id           = $user ? $user->id : null;
        $transaction->username          = strtoupper($fpx_buyerName);
        $transaction->fpx_checksum      = NULL;

        $list_student_fees_id   = $getstudentfees;
        $list_parent_fees_id    = $getparentfees;

        $id = explode("_", $fpx_sellerOrderNo);
        $id = (int) str_replace("PRIM", "", $id[0]);

        if ($transaction->save()) {
            
            if (substr($fpx_sellerExOrderNo, 0, 1) == 'S') {
                // ********* student fee id

                if ($list_student_fees_id) {
                    for ($i = 0; $i < count($list_student_fees_id); $i++) {
                        $array[] = array(
                            'student_fees_id' => $list_student_fees_id[$i],
                            'payment_type_id' => 1,
                            'transactions_id' => $transaction->id,
                        );
                    }

                    DB::table('fees_transactions_new')->insert($array);
                }
                if ($list_parent_fees_id) {
                    
                    for ($i = 0; $i < count($list_parent_fees_id); $i++) {
                        $result = DB::table('fees_new_organization_user')
                            ->where('id', $list_parent_fees_id[$i])
                            ->update([
                                'transaction_id' => $transaction->id
                            ]);
                    }
                    
                }
            }

            try{
                $response = $this->gateway->purchase(array(
                    'amount'    => $request->amount,
                    'currency'  => 'USD',
                    'transactionId' => $transaction->id,
                    'returnURL' => route('success'),
                    'cancelURL' => url('error')
                ))->send();
    
                if($response->isRedirect()){
                    $response->redirect();
                }
                else{
                    return $response->getMessage();
                }
            }
            catch(\Throwable $th){
                return $th->getMessage();
            }
        }
        else
        {
            return view('errors.500');
        }
    }

    public function success(Request $request){
        // how to get transaction id and organization_id;
        if($request->input('paymentId') && $request->input('PayerID')){
            $transaction = $this->gateway->completePurchase(array(
                'payer_id'  => $request->input('PayerID'),
                'transactionReference'  => $request->input('paymentId')
            ));

            // dd($this->transaction_id);
            $response = $transaction->send();
            $user = User::find(Auth::id());
            if($response->isSuccessful()){
                $transaction = DB::table('transactions as t')
                ->where('user_id', $user->id)
                ->latest('datetime_created')
                ->first();

                $userid = DB::table("transactions")
                ->where('id', $transaction->id)
                ->select('user_id as id')
                ->first();
        
                $userid = $userid->id;
                
                $id = $transaction->id;

                // details parents
                $getparent = DB::table('users')
                    ->where('id', $userid)
                    ->first();

                // details transaction
                $get_transaction = Transaction::where('id', $id)->first();

                // get organization id
                $oid = DB::table('fees_new as fn')
                ->leftJoin('student_fees_new as sfn', 'sfn.fees_id', '=', 'fn.id')
                ->leftJoin('fees_new_organization_user as fnou', 'fnou.fees_new_id', '=', 'fn.id')
                ->leftJoin('fees_transactions_new as ftn', 'ftn.student_fees_id', '=', 'sfn.id')
                ->where('ftn.transactions_id', $id)
                ->orWhere('fnou.transaction_id', $id)
                ->value('fn.organization_id');

                // update transaction status
                $updateTransaction = DB::table('transactions')
                ->where('id', $id)
                ->update(['status' => 'Success']);

                // update debt status for student and parent
                $list_student_fees_id = DB::table('student_fees_new')
                        ->join('fees_transactions_new', 'fees_transactions_new.student_fees_id', '=', 'student_fees_new.id')
                        ->join('transactions', 'transactions.id', '=', 'fees_transactions_new.transactions_id')
                        ->select('student_fees_new.id as student_fees_id', 'student_fees_new.class_student_id')
                        ->where('transactions.id', $transaction->id)
                        ->get();

                $list_parent_fees_id  = DB::table('fees_new')
                    ->join('fees_new_organization_user', 'fees_new_organization_user.fees_new_id', '=', 'fees_new.id')
                    ->join('organization_user', 'organization_user.id', '=', 'fees_new_organization_user.organization_user_id')
                    ->select('fees_new_organization_user.*')
                    ->orderBy('fees_new.category')
                    ->where('organization_user.user_id', $transaction->user_id)
                    ->where('organization_user.role_id', 6)
                    ->where('organization_user.status', 1)
                    ->where('fees_new_organization_user.transaction_id', $transaction->id)
                    ->get();

                for ($i = 0; $i < count($list_student_fees_id); $i++) {

                    // ************************* update student fees status fees by transactions *************************
                    $res  = DB::table('student_fees_new')
                        ->where('id', $list_student_fees_id[$i]->student_fees_id)
                        ->update(['status' => 'Paid']);

                    // ************************* check the student if have still debt *************************
                    
                    if ($i == count($list_student_fees_id) - 1)
                    {
                        $check_debt = DB::table('students')
                            ->join('class_student', 'class_student.student_id', '=', 'students.id')
                            ->join('student_fees_new', 'student_fees_new.class_student_id', '=', 'class_student.id')
                            ->select('students.*')
                            ->where('class_student.id', $list_student_fees_id[$i]->class_student_id)
                            ->where('student_fees_new.status', 'Debt')
                            ->count();


                        // ************************* update status fees for student if all fees completed paid*************************

                        if ($check_debt == 0) {
                            DB::table('class_student')
                                ->where('id', $list_student_fees_id[$i]->class_student_id)
                                ->update(['fees_status' => 'Completed']);
                        }
                    }
                }

                for ($i = 0; $i < count($list_parent_fees_id); $i++) {

                    // ************************* update status fees for parent *************************
                    DB::table('fees_new_organization_user')
                        ->where('id', $list_parent_fees_id[$i]->id)
                        ->update([
                            'status' => 'Paid'
                        ]);

                    // ************************* check the parent if have still debt *************************
                    if ($i == count($list_student_fees_id) - 1)
                    {
                        $check_debt = DB::table('organization_user')
                            ->join('fees_new_organization_user', 'fees_new_organization_user.organization_user_id', '=', 'organization_user.id')
                            ->where('organization_user.user_id', $transaction->user_id)
                            ->where('organization_user.role_id', 6)
                            ->where('organization_user.status', 1)
                            ->where('fees_new_organization_user.status', 'Debt')
                            ->count();

                        // ************************* update status fees for organization user (parent) if all fees completed paid *************************

                        if ($check_debt == 0) {
                            DB::table('organization_user')
                                ->where('user_id', $transaction->user_id)
                                ->where('role_id', 6)
                                ->where('status', 1)
                                ->update(['fees_status' => 'Completed']);
                        }
                    }
                }

                // details students by transactions 
                $get_student = DB::table('students')
                    ->join('class_student', 'class_student.student_id', '=', 'students.id')
                    ->join('class_organization', 'class_organization.id', 'class_student.organclass_id')
                    ->join('classes', 'classes.id', 'class_organization.class_id')
                    ->join('student_fees_new', 'student_fees_new.class_student_id', '=', 'class_student.id')
                    ->join('fees_new', 'fees_new.id', '=', 'student_fees_new.fees_id')
                    ->join('fees_transactions_new', 'fees_transactions_new.student_fees_id', '=', 'student_fees_new.id')
                    ->select('students.*', 'classes.nama as classname')
                    ->distinct()
                    ->orderBy('students.id')
                    ->orderBy('fees_new.category')
                    ->where('fees_transactions_new.transactions_id', $id)
                    ->where('student_fees_new.status', 'Paid')
                    ->get();

                // get category fees by transactions
                $get_category = DB::table('fees_new')
                    ->join('student_fees_new', 'student_fees_new.fees_id', '=', 'fees_new.id')
                    ->join('fees_transactions_new', 'fees_transactions_new.student_fees_id', '=', 'student_fees_new.id')
                    ->join('class_student', 'class_student.id', '=', 'student_fees_new.class_student_id')
                    ->join('students', 'students.id', '=', 'class_student.student_id')
                    ->select('fees_new.category', 'students.id as studentid')
                    ->distinct()
                    ->orderBy('students.id')
                    ->orderBy('fees_new.category')
                    ->where('fees_transactions_new.transactions_id', $id)
                    ->where('student_fees_new.status', 'Paid')
                    ->get();

                // dd($get_category);

                // get fees
                $get_fees = DB::table('fees_new')
                    ->join('student_fees_new', 'student_fees_new.fees_id', '=', 'fees_new.id')
                    ->join('fees_transactions_new', 'fees_transactions_new.student_fees_id', '=', 'student_fees_new.id')
                    ->join('class_student', 'class_student.id', '=', 'student_fees_new.class_student_id')
                    ->join('students', 'students.id', '=', 'class_student.student_id')
                    ->select('fees_new.*', 'students.id as studentid')
                    ->orderBy('students.id')
                    ->orderBy('fees_new.category')
                    ->where('fees_transactions_new.transactions_id', $id)
                    ->where('student_fees_new.status', 'Paid')
                    ->get();

                // get transaction for fees category A
                $getfees_categoryA  = DB::table('fees_new')
                    ->join('fees_new_organization_user', 'fees_new_organization_user.fees_new_id', '=', 'fees_new.id')
                    ->join('organization_user', 'organization_user.id', '=', 'fees_new_organization_user.organization_user_id')
                    ->select('fees_new.*')
                    ->orderBy('fees_new.name')
                    ->where('organization_user.user_id', $userid)
                    ->where('organization_user.role_id', 6)
                    ->where('organization_user.status', 1)
                    ->where('fees_new_organization_user.status', 'Paid')
                    ->where('fees_new_organization_user.transaction_id', $id)
                    ->get();

                // $getfees_categoryA ? $getfees_categoryA = 1 : $getfees_categoryA = "";
                // dd(count($getfees_categoryA), $id);
                if (count($get_category) != 0) {
                    $oid = DB::table('fees_new')
                        ->join('student_fees_new', 'student_fees_new.fees_id', '=', 'fees_new.id')
                        ->join('fees_transactions_new', 'fees_transactions_new.student_fees_id', '=', 'student_fees_new.id')
                        ->select('fees_new.organization_id')
                        ->distinct()
                        ->where('fees_transactions_new.transactions_id', $id)
                        ->where('student_fees_new.status', 'Paid')
                        ->first();

                    $get_organization = DB::table('organizations')->where('id', $oid->organization_id)->first();
                }

                if (count($getfees_categoryA) != 0) {
                    // dd($getfees_categoryA);
                    $oid = DB::table('fees_new')
                        ->join('fees_new_organization_user', 'fees_new_organization_user.fees_new_id', '=', 'fees_new.id')
                        ->join('organization_user', 'organization_user.id', '=', 'fees_new_organization_user.organization_user_id')
                        ->select('fees_new.organization_id')
                        ->distinct()
                        ->where('organization_user.user_id', $userid)
                        ->where('organization_user.role_id', 6)
                        ->where('organization_user.status', 1)
                        ->where('fees_new_organization_user.status', 'Paid')
                        ->where('fees_new_organization_user.transaction_id', $id)
                        ->first();

                    $get_organization = DB::table('organizations')->where('id', $oid->organization_id)->first();
                }
                // dd($get_fees);

                return view('fee.pay.receipt', compact('getparent', 'get_transaction', 'get_student', 'get_category', 'get_fees', 'getfees_categoryA', 'get_organization'));
            }
            else{
                return $this->error();
            }
        }
    }

    public function error(){
        if(Auth::user()->hasRole('Superadmin')){
            // ************************* get list dependent from user id  *******************************

            $list = DB::table('organizations')
            ->join('organization_user', 'organization_user.organization_id', '=', 'organizations.id')
            ->join('users', 'users.id', '=', 'organization_user.user_id')
            ->join('organization_user_student', 'organization_user_student.organization_user_id', '=', 'organization_user.id')
            ->join('students', 'students.id', '=', 'organization_user_student.student_id')
            ->join('class_student', 'class_student.student_id', '=', 'students.id')
            ->join('class_organization', 'class_organization.id', '=', 'class_student.organclass_id')
            ->join('classes', 'classes.id', '=', 'class_organization.class_id')
            ->select('organizations.id as oid', 'organizations.nama as nschool', 'students.id as studentid', 'students.nama as studentname', 'classes.nama as classname')
            // ->where('organization_user.user_id', $userid)
            ->where('organization_user.role_id', 6)
            ->where('organization_user.status', 1)
            ->where('class_student.status', 1)
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
                // ->where('organization_user.user_id', $userid)
                ->where('organization_user.role_id', 6)
                ->where('organization_user.status', 1)
                ->groupBy('organizations.id')
                ->orderBy('organizations.nama')
                ->get();


            // dd($organization);
            // ************************* get list fees  *******************************

            $getfees = DB::table('students')
                ->join('class_student', 'class_student.student_id', '=', 'students.id')
                ->join('student_fees_new', 'student_fees_new.class_student_id', '=', 'class_student.id')
                ->join('fees_new', 'fees_new.id', '=', 'student_fees_new.fees_id')
                ->select('fees_new.category', 'fees_new.organization_id', 'students.id as studentid')
                ->distinct()
                ->orderBy('students.id')
                ->orderBy('fees_new.category')
                ->where('fees_new.status', 1)
                ->where('class_student.status', 1)
                ->where('student_fees_new.status', 'Debt')
                ->get();

            $getfees_bystudent = DB::table('students')
                ->join('class_student', 'class_student.student_id', '=', 'students.id')
                ->join('student_fees_new', 'student_fees_new.class_student_id', '=', 'class_student.id')
                ->join('fees_new', 'fees_new.id', '=', 'student_fees_new.fees_id')
                ->select('fees_new.*', 'students.id as studentid')
                ->orderBy('fees_new.name')
                ->where('fees_new.status', 1)
                ->where('student_fees_new.status', 'Debt')
                ->where('class_student.status', 1)
                // ->where('fees_new.category', 'Kategory C')
                // ->where('fees_new.organization_id', 4)
                ->get();

            // dd($getfees_bystudent);

            // ************************* get fees category A  *******************************

            $getfees_category_A = DB::table('fees_new')
                ->join('fees_new_organization_user', 'fees_new_organization_user.fees_new_id', '=', 'fees_new.id')
                ->join('organization_user', 'organization_user.id', '=', 'fees_new_organization_user.organization_user_id')
                ->select('fees_new.category', 'organization_user.organization_id')
                ->distinct()
                ->orderBy('fees_new.category')
                ->where('fees_new.status', 1)
                // ->where('organization_user.user_id', $userid)
                ->where('organization_user.role_id', 6)
                ->where('organization_user.status', 1)
                ->where('fees_new_organization_user.status', 'Debt')
                ->get();

            // dd($getfees_category_A);
            $getfees_category_A_byparent  = DB::table('fees_new')
                ->join('fees_new_organization_user', 'fees_new_organization_user.fees_new_id', '=', 'fees_new.id')
                ->join('organization_user', 'organization_user.id', '=', 'fees_new_organization_user.organization_user_id')
                ->select('fees_new.*')
                ->orderBy('fees_new.category')
                ->where('fees_new.status', 1)
                // ->where('organization_user.user_id', $userid)
                ->where('organization_user.role_id', 6)
                ->where('organization_user.status', 1)
                ->where('fees_new_organization_user.status', 'Debt')
                ->get();
        }
        else{
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
            ->select('organizations.id as oid', 'organizations.nama as nschool', 'students.id as studentid', 'students.nama as studentname', 'classes.nama as classname')
            ->where('organization_user.user_id', $userid)
            ->where('organization_user.role_id', 6)
            ->where('organization_user.status', 1)
            ->where('class_student.status', 1)
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
                ->groupBy('organizations.id')
                ->orderBy('organizations.nama')
                ->get();


            // dd($organization);
            // ************************* get list fees  *******************************

            $getfees = DB::table('students')
                ->join('class_student', 'class_student.student_id', '=', 'students.id')
                ->join('student_fees_new', 'student_fees_new.class_student_id', '=', 'class_student.id')
                ->join('fees_new', 'fees_new.id', '=', 'student_fees_new.fees_id')
                ->select('fees_new.category', 'fees_new.organization_id', 'students.id as studentid')
                ->distinct()
                ->orderBy('students.id')
                ->orderBy('fees_new.category')
                ->where('fees_new.status', 1)
                ->where('class_student.status', 1)
                ->where('student_fees_new.status', 'Debt')
                ->get();

            $getfees_bystudent = DB::table('students')
                ->join('class_student', 'class_student.student_id', '=', 'students.id')
                ->join('student_fees_new', 'student_fees_new.class_student_id', '=', 'class_student.id')
                ->join('fees_new', 'fees_new.id', '=', 'student_fees_new.fees_id')
                ->select('fees_new.*', 'students.id as studentid')
                ->orderBy('fees_new.name')
                ->where('fees_new.status', 1)
                ->where('student_fees_new.status', 'Debt')
                ->where('class_student.status', 1)
                // ->where('fees_new.category', 'Kategory C')
                // ->where('fees_new.organization_id', 4)
                ->get();

            // dd($getfees_bystudent);

            // ************************* get fees category A  *******************************

            $getfees_category_A = DB::table('fees_new')
                ->join('fees_new_organization_user', 'fees_new_organization_user.fees_new_id', '=', 'fees_new.id')
                ->join('organization_user', 'organization_user.id', '=', 'fees_new_organization_user.organization_user_id')
                ->select('fees_new.category', 'organization_user.organization_id')
                ->distinct()
                ->orderBy('fees_new.category')
                ->where('fees_new.status', 1)
                ->where('organization_user.user_id', $userid)
                ->where('organization_user.role_id', 6)
                ->where('organization_user.status', 1)
                ->where('fees_new_organization_user.status', 'Debt')
                ->get();

            // dd($getfees_category_A);
            $getfees_category_A_byparent  = DB::table('fees_new')
                ->join('fees_new_organization_user', 'fees_new_organization_user.fees_new_id', '=', 'fees_new.id')
                ->join('organization_user', 'organization_user.id', '=', 'fees_new_organization_user.organization_user_id')
                ->select('fees_new.*')
                ->orderBy('fees_new.category')
                ->where('fees_new.status', 1)
                ->where('organization_user.user_id', $userid)
                ->where('organization_user.role_id', 6)
                ->where('organization_user.status', 1)
                ->where('fees_new_organization_user.status', 'Debt')
                ->get();
        }

        // dd($organization);
        return view('fee.pay.index', compact('list', 'organization', 'getfees', 'getfees_bystudent', 'getfees_category_A', 'getfees_category_A_byparent'));
    }
}
