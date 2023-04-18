<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\AppBaseController;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;

class FPXController extends AppBaseController
{
    public function getBankList()
    {
        //Merchant will need to edit the below parameter to match their environment.
        error_reporting(E_ALL);

        /* Generating String to send to fpx */
        /*For B2C, message.token = 01
        For B2B1, message.token = 02 */

        $fpx_msgToken="01";
        $fpx_msgType="BE";
        $fpx_sellerExId=config('app.env') == 'production' ? "EX00011125" : "EX00012323";
        $fpx_version="6.0";
        /* Generating signing String */
        $data=$fpx_msgToken."|".$fpx_msgType."|".$fpx_sellerExId."|".$fpx_version;
        /* Reading key */
        $priv_key = getenv('FPX_KEY');
        // $priv_key = file_get_contents('C:\\pki-keys\\DevExchange\\EX00012323.key');
        $pkeyid = openssl_get_privatekey($priv_key, null);
        openssl_sign($data, $binary_signature, $pkeyid, OPENSSL_ALGO_SHA1);
        $fpx_checkSum = strtoupper(bin2hex($binary_signature));


        //extract data from the post

        extract($_POST);
        $fields_string="";

        //set POST variables
        $url = config('app.env') == 'production' ? config('app.PRODUCTION_BE_URL') : config('app.UAT_BE_URL');

        $fields = array(
                    'fpx_msgToken' => urlencode($fpx_msgToken),
                    'fpx_msgType' => urlencode($fpx_msgType),
                    'fpx_sellerExId' => urlencode($fpx_sellerExId),
                    'fpx_checkSum' => urlencode($fpx_checkSum),
                    'fpx_version' => urlencode($fpx_version)
                );
        $response_value=array();
        $bank_list=array();

        try {
            //url-ify the data for the POST
            foreach ($fields as $key=>$value) {
                $fields_string .= $key.'='.$value.'&';
            }
            rtrim($fields_string, '&');

            //open connection
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

            //set the url, number of POST vars, POST data
            curl_setopt($ch, CURLOPT_URL, $url);

            curl_setopt($ch, CURLOPT_POST, count($fields));
            curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);

            // receive server response ...
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
           
            //execute post
            $result = curl_exec($ch);
            // dd($result);
            //close connection
            curl_close($ch);

            $token = strtok($result, "&");
            while ($token !== false) {
                list($key1, $value1)=explode("=", $token);
                $value1=urldecode($value1);
                $response_value[$key1]=$value1;
                $token = strtok("&");
            }

            $fpx_msgToken=reset($response_value);

            //Response Checksum Calculation String
            $data=$response_value['fpx_bankList']."|".$response_value['fpx_msgToken']."|".$response_value['fpx_msgType']."|".$response_value['fpx_sellerExId'];

            // val == 00 verification success
            $token = strtok($response_value['fpx_bankList'], ",");

            $count = -1;

            while ($token !== false) {
                list($key1, $value1)=explode("~", $token);
                $count = $count + 1;
                $value1=urldecode($value1);
                $bank_list[$key1]=['code' => $key1 , 'value' => $value1 , 'nama' => '' ];
                
                $token = strtok(",");
            }

            // asort($bank_list);
            // dd($bank_list);
            // $bank_list['ABB0234']['nama']  = $bank_list['ABB0234']['value']  == "A" ? "Affin B2C - Test ID" : "Affin B2C - Test ID (OFFLINE)";
            if (config('app.env') == 'production') {
                $bank_list['ABB0233']['nama']   = $bank_list['ABB0233']['value']  == "A" ? "Affin Bank" : "Affin Bank (OFFLINE)";
                $bank_list['ABMB0212']['nama']  = $bank_list['ABMB0212']['value'] == "A" ? "Alliance Bank (Personal)" : "Alliance Bank (OFFLINE)";
                $bank_list['AGRO01']['nama']    = $bank_list['AGRO01']['value']   == "A" ? "AGRONet" : "AGRONet (OFFLINE)";
                $bank_list['AMBB0209']['nama']  = $bank_list['AMBB0209']['value'] == "A" ? "AmBank" : "AmBank (OFFLINE)";
                $bank_list['BIMB0340']['nama']  = $bank_list['BIMB0340']['value'] == "A" ? "Bank Islam" : "Bank Islam (OFFLINE)";
                $bank_list['BMMB0341']['nama']  = $bank_list['BMMB0341']['value'] == "A" ? "Bank Muamalat" : "Bank Muamalat (OFFLINE)";
                $bank_list['BKRM0602']['nama']  = $bank_list['BKRM0602']['value'] == "A" ? "Bank Rakyat" : "Bank Rakyat (OFFLINE)";
                $bank_list['BSN0601']['nama']   = $bank_list['BSN0601']['value']  == "A" ? "BSN" : "BSN (OFFLINE)";
                $bank_list['BCBB0235']['nama']  = $bank_list['BCBB0235']['value'] == "A" ? "CIMB Clicks" : "CIMB Clicks (OFFLINE)";
                $bank_list['HLB0224']['nama']   = $bank_list['HLB0224']['value']  == "A" ? "Hong Leong Bank" : "Hong Leong Bank (OFFLINE)";
                $bank_list['HSBC0223']['nama']  = $bank_list['HSBC0223']['value'] == "A" ? "HSBC Bank" : "HSBC Bank (OFFLINE)";
                $bank_list['KFH0346']['nama']   = $bank_list['KFH0346']['value']  == "A" ? "KFH" : "KFH (OFFLINE)";
                $bank_list['MBB0228']['nama']   = $bank_list['MBB0228']['value']  == "A" ? "Maybank2E" : "Maybank2E (OFFLINE)";
                $bank_list['MB2U0227']['nama']  = $bank_list['MB2U0227']['value'] == "A" ? "Maybank2U" : "Maybank2U (OFFLINE)";
                $bank_list['OCBC0229']['nama']  = $bank_list['OCBC0229']['value'] == "A" ? "OCBC Bank" : "OCBC Bank (OFFLINE)";
                $bank_list['PBB0233']['nama']   = $bank_list['PBB0233']['value']  == "A" ? "Public Bank" : "Public Bank (OFFLINE)";
                $bank_list['RHB0218']['nama']   = $bank_list['RHB0218']['value']  == "A" ? "RHB Bank" : "RHB Bank (OFFLINE)";
                $bank_list['SCB0216']['nama']   = $bank_list['SCB0216']['value']  == "A" ? "Standard Chartered" : "Standard Chartered (OFFLINE)";
                $bank_list['UOB0226']['nama']   = $bank_list['UOB0226']['value']  == "A" ? "UOB Bank" : "UOB Bank (OFFLINE)";
                $bank_list['BOCM01']['nama']    = $bank_list['BOCM01']['value']  == "A" ? "Bank Of China" : "Bank Of China (OFFLINE)";
            } elseif (config('app.env') == 'local' || config('app.env') == 'staging') {
                $bank_list['ABB0234']['nama']   = $bank_list['ABB0234']['value'] == "A" ? "Affin B2C - Test ID" : "Affin B2C - Test ID (OFFLINE)";
                $bank_list['ABB0233']['nama']   = $bank_list['ABB0233']['value'] == "A" ? "Affin Bank" : "Affin Bank (OFFLINE)";
                $bank_list['ABMB0212']['nama']  = $bank_list['ABMB0212']['value'] == "A" ? "Alliance Bank (Personal)" : "Alliance Bank (OFFLINE)";
                $bank_list['AGRO01']['nama']    = $bank_list['AGRO01']['value']  == "A" ? "AGRONet" : "AGRONet (OFFLINE)";
                $bank_list['AMBB0209']['nama']  = $bank_list['AMBB0209']['value'] == "A" ? "AmBank" : "AmBank (OFFLINE)";
                $bank_list['BIMB0340']['nama']  = $bank_list['BIMB0340']['value'] == "A" ? "Bank Islam" : "Bank Islam (OFFLINE)";
                $bank_list['BMMB0341']['nama']  = $bank_list['BMMB0341']['value'] == "A" ? "Bank Muamalat" : "Bank Muamalat (OFFLINE)";
                $bank_list['BKRM0602']['nama']  = $bank_list['BKRM0602']['value'] == "A" ? "Bank Rakyat" : "Bank Rakyat (OFFLINE)";
                $bank_list['BSN0601']['nama']   = $bank_list['BSN0601']['value']  == "A" ? "BSN" : "BSN (OFFLINE)";
                $bank_list['BCBB0235']['nama']  = $bank_list['BCBB0235']['value'] == "A" ? "CIMB Clicks" : "CIMB Clicks (OFFLINE)";
                $bank_list['CIT0219']['nama']   = $bank_list['CIT0219']['value']  == "A" ? "Citibank" : "Citibank (OFFLINE)";
                $bank_list['HLB0224']['nama']   = $bank_list['HLB0224']['value']  == "A" ? "Hong Leong Bank" : "Hong Leong Bank (OFFLINE)";
                $bank_list['HSBC0223']['nama']  = $bank_list['HSBC0223']['value'] == "A" ? "HSBC Bank" : "HSBC Bank (OFFLINE)";
                $bank_list['KFH0346']['nama']   = $bank_list['KFH0346']['value']  == "A" ? "KFH" : "KFH (OFFLINE)";
                $bank_list['MBB0228']['nama']   = $bank_list['MBB0228']['value']  == "A" ? "Maybank2E" : "Maybank2E (OFFLINE)";
                $bank_list['MB2U0227']['nama']  = $bank_list['MB2U0227']['value'] == "A" ? "Maybank2U" : "Maybank2U (OFFLINE)";
                $bank_list['OCBC0229']['nama']  = $bank_list['OCBC0229']['value'] == "A" ? "OCBC Bank" : "OCBC Bank (OFFLINE)";
                $bank_list['PBB0233']['nama']   = $bank_list['PBB0233']['value']  == "A" ? "Public Bank" : "Public Bank (OFFLINE)";
                $bank_list['RHB0218']['nama']   = $bank_list['RHB0218']['value']  == "A" ? "RHB Bank" : "RHB Bank (OFFLINE)";
                $bank_list['TEST0021']['nama']  = $bank_list['TEST0021']['value'] == "A" ? "SBI Bank A" : "SBI Bank A (OFFLINE)";
                $bank_list['TEST0022']['nama']  = $bank_list['TEST0022']['value'] == "A" ? "SBI Bank B" : "SBI Bank B (OFFLINE)";
                $bank_list['TEST0023']['nama']  = $bank_list['TEST0023']['value'] == "A" ? "SBI Bank C" : "SBI Bank C (OFFLINE)";
                $bank_list['SCB0216']['nama']   = $bank_list['SCB0216']['value']  == "A" ? "Standard Chartered" : "Standard Chartered (OFFLINE)";
                $bank_list['UOB0226']['nama']   = $bank_list['UOB0226']['value']  == "A" ? "UOB Bank" : "UOB Bank (OFFLINE)";
                $bank_list['BOCM01']['nama']    = $bank_list['BOCM01']['value']  == "A" ? "Bank Of China" : "Bank Of China (OFFLINE)";
                // $bank_list['UOB0229']['nama']   = $bank_list['UOB0229']['value']  == "A" ? "UOB Bank - Test ID" : "UOB Bank - Test ID (OFFLINE)";
            }
            
            function compareByName($a, $b)
            {
                return strcasecmp($a["nama"], $b["nama"]);
            }

            return $this->sendResponse($bank_list, "Success");
        } catch (\Throwable $th) {
            return $this->sendError($th->getMessage(), 500);
        }
    }
    
    public static function getStaticBankList()
    {
        //Merchant will need to edit the below parameter to match their environment.
        error_reporting(E_ALL);

        /* Generating String to send to fpx */
        /*For B2C, message.token = 01
        For B2B1, message.token = 02 */

        $fpx_msgToken="01";
        $fpx_msgType="BE";
        $fpx_sellerExId=config('app.env') == 'production' ? "EX00011125" : "EX00012323";
        $fpx_version="6.0";
        /* Generating signing String */
        $data=$fpx_msgToken."|".$fpx_msgType."|".$fpx_sellerExId."|".$fpx_version;
        /* Reading key */
        $priv_key = getenv('FPX_KEY');
        // $priv_key = file_get_contents('C:\\pki-keys\\DevExchange\\EX00012323.key');
        $pkeyid = openssl_get_privatekey($priv_key, null);
        openssl_sign($data, $binary_signature, $pkeyid, OPENSSL_ALGO_SHA1);
        $fpx_checkSum = strtoupper(bin2hex($binary_signature));


        //extract data from the post

        extract($_POST);
        $fields_string="";

        //set POST variables
        $url = config('app.env') == 'production' ? config('app.PRODUCTION_BE_URL') : config('app.UAT_BE_URL');

        $fields = array(
                    'fpx_msgToken' => urlencode($fpx_msgToken),
                    'fpx_msgType' => urlencode($fpx_msgType),
                    'fpx_sellerExId' => urlencode($fpx_sellerExId),
                    'fpx_checkSum' => urlencode($fpx_checkSum),
                    'fpx_version' => urlencode($fpx_version)
                );
        $response_value=array();
        $bank_list=array();

        try {
            //url-ify the data for the POST
            foreach ($fields as $key=>$value) {
                $fields_string .= $key.'='.$value.'&';
            }
            rtrim($fields_string, '&');

            //open connection
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

            //set the url, number of POST vars, POST data
            curl_setopt($ch, CURLOPT_URL, $url);

            curl_setopt($ch, CURLOPT_POST, count($fields));
            curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);

            // receive server response ...
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
           
            //execute post
            $result = curl_exec($ch);
            // dd($result);
            //close connection
            curl_close($ch);

            $token = strtok($result, "&");
            while ($token !== false) {
                list($key1, $value1)=explode("=", $token);
                $value1=urldecode($value1);
                $response_value[$key1]=$value1;
                $token = strtok("&");
            }

            $fpx_msgToken=reset($response_value);

            //Response Checksum Calculation String
            $data=$response_value['fpx_bankList']."|".$response_value['fpx_msgToken']."|".$response_value['fpx_msgType']."|".$response_value['fpx_sellerExId'];

            // val == 00 verification success
            $token = strtok($response_value['fpx_bankList'], ",");

            $count = -1;

            while ($token !== false) {
                list($key1, $value1)=explode("~", $token);
                $count = $count + 1;
                $value1=urldecode($value1);
                $bank_list[$key1]=['code' => $key1 , 'value' => $value1 , 'nama' => '' ];
                
                $token = strtok(",");
            }

            // asort($bank_list);
            // dd($bank_list);
            // $bank_list['ABB0234']['nama']  = $bank_list['ABB0234']['value']  == "A" ? "Affin B2C - Test ID" : "Affin B2C - Test ID (OFFLINE)";
            if (config('app.env') == 'production') {
                $bank_list['ABB0233']['nama']   = $bank_list['ABB0233']['value']  == "A" ? "Affin Bank" : "Affin Bank (OFFLINE)";
                $bank_list['ABMB0212']['nama']  = $bank_list['ABMB0212']['value'] == "A" ? "Alliance Bank (Personal)" : "Alliance Bank (OFFLINE)";
                $bank_list['AGRO01']['nama']    = $bank_list['AGRO01']['value']   == "A" ? "AGRONet" : "AGRONet (OFFLINE)";
                $bank_list['AMBB0209']['nama']  = $bank_list['AMBB0209']['value'] == "A" ? "AmBank" : "AmBank (OFFLINE)";
                $bank_list['BIMB0340']['nama']  = $bank_list['BIMB0340']['value'] == "A" ? "Bank Islam" : "Bank Islam (OFFLINE)";
                $bank_list['BMMB0341']['nama']  = $bank_list['BMMB0341']['value'] == "A" ? "Bank Muamalat" : "Bank Muamalat (OFFLINE)";
                $bank_list['BKRM0602']['nama']  = $bank_list['BKRM0602']['value'] == "A" ? "Bank Rakyat" : "Bank Rakyat (OFFLINE)";
                $bank_list['BSN0601']['nama']   = $bank_list['BSN0601']['value']  == "A" ? "BSN" : "BSN (OFFLINE)";
                $bank_list['BCBB0235']['nama']  = $bank_list['BCBB0235']['value'] == "A" ? "CIMB Clicks" : "CIMB Clicks (OFFLINE)";
                $bank_list['HLB0224']['nama']   = $bank_list['HLB0224']['value']  == "A" ? "Hong Leong Bank" : "Hong Leong Bank (OFFLINE)";
                $bank_list['HSBC0223']['nama']  = $bank_list['HSBC0223']['value'] == "A" ? "HSBC Bank" : "HSBC Bank (OFFLINE)";
                $bank_list['KFH0346']['nama']   = $bank_list['KFH0346']['value']  == "A" ? "KFH" : "KFH (OFFLINE)";
                $bank_list['MBB0228']['nama']   = $bank_list['MBB0228']['value']  == "A" ? "Maybank2E" : "Maybank2E (OFFLINE)";
                $bank_list['MB2U0227']['nama']  = $bank_list['MB2U0227']['value'] == "A" ? "Maybank2U" : "Maybank2U (OFFLINE)";
                $bank_list['OCBC0229']['nama']  = $bank_list['OCBC0229']['value'] == "A" ? "OCBC Bank" : "OCBC Bank (OFFLINE)";
                $bank_list['PBB0233']['nama']   = $bank_list['PBB0233']['value']  == "A" ? "Public Bank" : "Public Bank (OFFLINE)";
                $bank_list['RHB0218']['nama']   = $bank_list['RHB0218']['value']  == "A" ? "RHB Bank" : "RHB Bank (OFFLINE)";
                $bank_list['SCB0216']['nama']   = $bank_list['SCB0216']['value']  == "A" ? "Standard Chartered" : "Standard Chartered (OFFLINE)";
                $bank_list['UOB0226']['nama']   = $bank_list['UOB0226']['value']  == "A" ? "UOB Bank" : "UOB Bank (OFFLINE)";
                $bank_list['BOCM01']['nama']    = $bank_list['BOCM01']['value']  == "A" ? "Bank Of China" : "Bank Of China (OFFLINE)";
            } elseif (config('app.env') == 'local' || config('app.env') == 'staging') {
                $bank_list['ABB0234']['nama']   = $bank_list['ABB0234']['value'] == "A" ? "Affin B2C - Test ID" : "Affin B2C - Test ID (OFFLINE)";
                $bank_list['ABB0233']['nama']   = $bank_list['ABB0233']['value'] == "A" ? "Affin Bank" : "Affin Bank (OFFLINE)";
                $bank_list['ABMB0212']['nama']  = $bank_list['ABMB0212']['value'] == "A" ? "Alliance Bank (Personal)" : "Alliance Bank (OFFLINE)";
                $bank_list['AGRO01']['nama']    = $bank_list['AGRO01']['value']  == "A" ? "AGRONet" : "AGRONet (OFFLINE)";
                $bank_list['AMBB0209']['nama']  = $bank_list['AMBB0209']['value'] == "A" ? "AmBank" : "AmBank (OFFLINE)";
                $bank_list['BIMB0340']['nama']  = $bank_list['BIMB0340']['value'] == "A" ? "Bank Islam" : "Bank Islam (OFFLINE)";
                $bank_list['BMMB0341']['nama']  = $bank_list['BMMB0341']['value'] == "A" ? "Bank Muamalat" : "Bank Muamalat (OFFLINE)";
                $bank_list['BKRM0602']['nama']  = $bank_list['BKRM0602']['value'] == "A" ? "Bank Rakyat" : "Bank Rakyat (OFFLINE)";
                $bank_list['BSN0601']['nama']   = $bank_list['BSN0601']['value']  == "A" ? "BSN" : "BSN (OFFLINE)";
                $bank_list['BCBB0235']['nama']  = $bank_list['BCBB0235']['value'] == "A" ? "CIMB Clicks" : "CIMB Clicks (OFFLINE)";
                $bank_list['CIT0219']['nama']   = $bank_list['CIT0219']['value']  == "A" ? "Citibank" : "Citibank (OFFLINE)";
                $bank_list['HLB0224']['nama']   = $bank_list['HLB0224']['value']  == "A" ? "Hong Leong Bank" : "Hong Leong Bank (OFFLINE)";
                $bank_list['HSBC0223']['nama']  = $bank_list['HSBC0223']['value'] == "A" ? "HSBC Bank" : "HSBC Bank (OFFLINE)";
                $bank_list['KFH0346']['nama']   = $bank_list['KFH0346']['value']  == "A" ? "KFH" : "KFH (OFFLINE)";
                $bank_list['MBB0228']['nama']   = $bank_list['MBB0228']['value']  == "A" ? "Maybank2E" : "Maybank2E (OFFLINE)";
                $bank_list['MB2U0227']['nama']  = $bank_list['MB2U0227']['value'] == "A" ? "Maybank2U" : "Maybank2U (OFFLINE)";
                $bank_list['OCBC0229']['nama']  = $bank_list['OCBC0229']['value'] == "A" ? "OCBC Bank" : "OCBC Bank (OFFLINE)";
                $bank_list['PBB0233']['nama']   = $bank_list['PBB0233']['value']  == "A" ? "Public Bank" : "Public Bank (OFFLINE)";
                $bank_list['RHB0218']['nama']   = $bank_list['RHB0218']['value']  == "A" ? "RHB Bank" : "RHB Bank (OFFLINE)";
                $bank_list['TEST0021']['nama']  = $bank_list['TEST0021']['value'] == "A" ? "SBI Bank A" : "SBI Bank A (OFFLINE)";
                $bank_list['TEST0022']['nama']  = $bank_list['TEST0022']['value'] == "A" ? "SBI Bank B" : "SBI Bank B (OFFLINE)";
                $bank_list['TEST0023']['nama']  = $bank_list['TEST0023']['value'] == "A" ? "SBI Bank C" : "SBI Bank C (OFFLINE)";
                $bank_list['SCB0216']['nama']   = $bank_list['SCB0216']['value']  == "A" ? "Standard Chartered" : "Standard Chartered (OFFLINE)";
                $bank_list['UOB0226']['nama']   = $bank_list['UOB0226']['value']  == "A" ? "UOB Bank" : "UOB Bank (OFFLINE)";
                $bank_list['BOCM01']['nama']    = $bank_list['BOCM01']['value']  == "A" ? "Bank Of China" : "Bank Of China (OFFLINE)";
                // $bank_list['UOB0229']['nama']   = $bank_list['UOB0229']['value']  == "A" ? "UOB Bank - Test ID" : "UOB Bank - Test ID (OFFLINE)";
            }

            return $bank_list;
        } catch (\Throwable $th) {

        }
    }

    public function requery(Request $request)
    {
        $transaction = Transaction::find($request->transactionId);
        $fpx_msgType = "AE";
        $fpx_msgToken = "01";
        $fpx_sellerExId = config('app.env') == 'production' ? "EX00011125" : "EX00012323";
        $fpx_sellerExOrderNo = $transaction->nama;

        $date_time_string = $transaction->datetime_created;
        $date_time = date_create_from_format('Y-m-d H:i:s', $date_time_string);

        $fpx_sellerTxnTime = $date_time->format('YmdHis');
        $fpx_sellerOrderNo = $transaction->description;
        $fpx_productDesc = explode("_", $transaction->nama)[0];

        if ($fpx_productDesc == "Donation")
        {
            $organ = DB::table("transactions as t")
                ->leftJoin('donation_transaction as dt', 't.id', 'dt.transaction_id')
                ->leftJoin('donations as d', 'd.id', 'dt.donation_id')
                ->leftJoin('donation_organization as do', 'do.donation_id', 'd.id')
                ->leftJoin('organizations as o', 'o.id', 'do.organization_id')
                ->select('o.seller_id')
                ->where('t.id', $request->transactionId)
                ->first();
        }
        else if ($fpx_productDesc == "School")
        {

        }

        $fpx_sellerId = $organ->seller_id;
        $fpx_sellerBankCode = "01";
        $fpx_txnCurrency = "MYR";
        $fpx_txnAmount = $transaction->amount;
        $fpx_buyerEmail = $transaction->email;
        $fpx_checkSum = "";
        $fpx_buyerName = $transaction->username;
        $fpx_buyerBankId = $transaction->buyerBankId;
        $fpx_buyerAccNo = "";
        $fpx_buyerId = "";
        $fpx_buyerIban = "";
        $fpx_buyerBankBranch = "";
        $fpx_makerName = "";
        $fpx_version = "6.0";

        // dd($fpx_checkSum);
        $data = $fpx_buyerAccNo . "|" . $fpx_buyerBankBranch . "|" . $fpx_buyerBankId . "|" . $fpx_buyerEmail . "|" . $fpx_buyerIban . "|" . $fpx_buyerId . "|" . $fpx_buyerName . "|" . $fpx_makerName . "|" . $fpx_msgToken . "|" . $fpx_msgType . "|" . $fpx_productDesc . "|" . $fpx_sellerBankCode . "|" . $fpx_sellerExId . "|" . $fpx_sellerExOrderNo . "|" . $fpx_sellerId . "|" . $fpx_sellerOrderNo . "|" . $fpx_sellerTxnTime . "|" . $fpx_txnAmount . "|" . $fpx_txnCurrency . "|" . $fpx_version;
        // $data=$fpx_buyerBankBranch."|".$fpx_buyerBankId."|".$fpx_buyerIban."|".$fpx_buyerId."|".$fpx_buyerName."|".$fpx_creditAuthCode."|".$fpx_creditAuthNo."|".$fpx_debitAuthCode."|".$fpx_debitAuthNo."|".$fpx_fpxTxnId."|".$fpx_fpxTxnTime."|".$fpx_makerName."|".$fpx_msgToken."|".$fpx_msgType."|".$fpx_sellerExId."|".$fpx_sellerExOrderNo."|".$fpx_sellerId."|".$fpx_sellerOrderNo."|".$fpx_sellerTxnTime."|".$fpx_txnAmount."|".$fpx_txnCurrency;

        $priv_key = getenv('FPX_KEY');
        $pkeyid = openssl_get_privatekey($priv_key, null);
        openssl_sign($data, $binary_signature, $pkeyid, OPENSSL_ALGO_SHA1);
        $fpx_checkSum = strtoupper(bin2hex($binary_signature));

        $fields_string="";

        //set POST variables
        $url = ($fpx_buyerBankId == 'TEST0021' ||  $fpx_buyerBankId == 'TEST0022' || $fpx_buyerBankId == 'TEST0023') 
                ? config('app.UAT_AE_AQ_URL') 
                : config('app.PRODUCTION_AE_AQ_URL');

        $fields = array(
            'fpx_msgType' => urlencode("AE"),
            'fpx_msgToken' => urlencode($fpx_msgToken),
            'fpx_sellerExId' => urlencode($fpx_sellerExId),
            'fpx_sellerExOrderNo' => urlencode($fpx_sellerExOrderNo),
            'fpx_sellerTxnTime' => urlencode($fpx_sellerTxnTime),
            'fpx_sellerOrderNo' => urlencode($fpx_sellerOrderNo),
            'fpx_sellerId' => urlencode($fpx_sellerId),
            'fpx_sellerBankCode' => urlencode($fpx_sellerBankCode),
            'fpx_txnCurrency' => urlencode($fpx_txnCurrency),
            'fpx_txnAmount' => urlencode($fpx_txnAmount),
            'fpx_buyerEmail' => urlencode($fpx_buyerEmail),
            'fpx_checkSum' => urlencode($fpx_checkSum),
            'fpx_buyerName' => urlencode($fpx_buyerName),
            'fpx_buyerBankId' => urlencode($fpx_buyerBankId),
            'fpx_buyerBankBranch' => urlencode($fpx_buyerBankBranch),
            'fpx_buyerAccNo' => urlencode($fpx_buyerAccNo),
            'fpx_buyerId' => urlencode($fpx_buyerId),
            'fpx_makerName' => urlencode($fpx_makerName),
            'fpx_buyerIban' => urlencode($fpx_buyerIban),
            'fpx_productDesc' => urlencode($fpx_productDesc),
            'fpx_version' => urlencode($fpx_version)
        );

        $response_value = array();

        try{
            //url-ify the data for the POST
            foreach($fields as $key=>$value) { $fields_string .= $key.'='.$value.'&'; }
            rtrim($fields_string, '&');

            //open connection
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);

            //set the url, number of POST vars, POST data
            curl_setopt($ch,CURLOPT_URL, $url);

            curl_setopt($ch,CURLOPT_POST, count($fields));
            curl_setopt($ch,CURLOPT_POSTFIELDS, $fields_string);

            // receive server response ...
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            //execute post
            $result = curl_exec($ch);
            //echo "RESULT";
            //echo $result;

            //close connection
            curl_close($ch);

            $token = strtok($result, "&");
            while ($token !== false)
            {
                list($key1,$value1)=explode("=", $token);
                $value1=urldecode($value1);
                $response_value[$key1]=$value1;
                $token = strtok("&");
            }

            $fpx_debitAuthCode = reset($response_value);
            //Response Checksum Calculation String
            $data = $response_value['fpx_buyerBankBranch']."|".$response_value['fpx_buyerBankId']."|".$response_value['fpx_buyerIban']."|".$response_value['fpx_buyerId']."|".$response_value['fpx_buyerName']."|".$response_value['fpx_creditAuthCode']."|".$response_value['fpx_creditAuthNo']."|".$fpx_debitAuthCode."|".$response_value['fpx_debitAuthNo']."|".$response_value['fpx_fpxTxnId']."|".$response_value['fpx_fpxTxnTime']."|".$response_value['fpx_makerName']."|".$response_value['fpx_msgToken']."|".$response_value['fpx_msgType']."|".$response_value['fpx_sellerExId']."|".$response_value['fpx_sellerExOrderNo']."|".$response_value['fpx_sellerId']."|".$response_value['fpx_sellerOrderNo']."|".$response_value['fpx_sellerTxnTime']."|".$response_value['fpx_txnAmount']."|".$response_value['fpx_txnCurrency'];
            // dd($data);
        } 
        catch (\Throwable $th) {
            echo 'Error :', ($th->getMessage());
        }

        return $fpx_debitAuthCode;
    }
}
