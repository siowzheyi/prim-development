<?php
/// Summary description for Controller
///  ErrorCode  : Description
///  00         : Your signature has been verified successfully.  
///  06         : No Certificate found 
///  07         : One Certificate Found and Expired
///  08         : Both Certificates Expired
///  09         : Your Data cannot be verified against the Signature.
error_reporting(E_ALL);

extract($_POST);

$fpx_msgType="AE";
$fpx_msgToken="01";
$fpx_sellerExId= config('app.env') == 'production' ? "EX00011125" : "EX00012323";
$fpx_sellerExOrderNo=$request->fpx_sellerExOrderNo;
$fpx_sellerTxnTime=$request->fpx_fpxTxnTime;
$fpx_sellerOrderNo=$request->fpx_sellerOrderNo;
// $fpx_sellerId="SE00013841";
$fpx_sellerId=$request->fpx_sellerId;
$fpx_sellerBankCode="01";
$fpx_txnCurrency="MYR";
$fpx_txnAmount=$request->fpx_txnAmount;
$fpx_buyerEmail=$transaction->email;
// $fpx_checkSum=$user->fpx_checksum;
$fpx_checkSum="";
$fpx_buyerName=$transaction->username;
$fpx_buyerBankId=$request->fpx_buyerBankId;
$fpx_buyerAccNo="";
$fpx_buyerId="";
$fpx_buyerIban="";
$fpx_buyerBankBranch="";
$fpx_makerName="";
$fpx_productDesc=explode("_", $request->fpx_sellerExOrderNo)[0];
$fpx_version="6.0";

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
$response_value=array();

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

$fpx_debitAuthCode=reset($response_value);
//Response Checksum Calculation String
$data=$response_value['fpx_buyerBankBranch']."|".$response_value['fpx_buyerBankId']."|".$response_value['fpx_buyerIban']."|".$response_value['fpx_buyerId']."|".$response_value['fpx_buyerName']."|".$response_value['fpx_creditAuthCode']."|".$response_value['fpx_creditAuthNo']."|".$fpx_debitAuthCode."|".$response_value['fpx_debitAuthNo']."|".$response_value['fpx_fpxTxnId']."|".$response_value['fpx_fpxTxnTime']."|".$response_value['fpx_makerName']."|".$response_value['fpx_msgToken']."|".$response_value['fpx_msgType']."|".$response_value['fpx_sellerExId']."|".$response_value['fpx_sellerExOrderNo']."|".$response_value['fpx_sellerId']."|".$response_value['fpx_sellerOrderNo']."|".$response_value['fpx_sellerTxnTime']."|".$response_value['fpx_txnAmount']."|".$response_value['fpx_txnCurrency'];
// dd($data);

} catch (Exception $e) {
    echo 'Error :', ($e->getMessage());
}

// $val=verifySign_fpx($fpx_checkSum, $data);

// $fpx_buyerBankBranch=$request->fpx_buyerBankBranch;
// $fpx_buyerBankId=$request->fpx_buyerBankId;
// $fpx_buyerIban=$request->fpx_buyerIban;
// $fpx_buyerId=$request->fpx_buyerId;
// $fpx_buyerName=$request->fpx_buyerName;
// $fpx_creditAuthCode=$request->fpx_creditAuthCode;
// $fpx_creditAuthNo=$request->fpx_creditAuthNo;
// $fpx_debitAuthCode=$request->fpx_debitAuthCode;
// $fpx_debitAuthNo=$request->fpx_debitAuthNo;
// $fpx_fpxTxnId=$request->fpx_fpxTxnId;
// $fpx_fpxTxnTime=$request->fpx_fpxTxnTime;
// $fpx_makerName=$request->fpx_makerName;
// $fpx_msgToken=$request->fpx_msgToken;
// $fpx_msgType=$request->fpx_msgType;
// $fpx_sellerExId=$request->fpx_sellerExId;
// $fpx_sellerExOrderNo=$request->fpx_sellerExOrderNo;
// $fpx_sellerId=$request->fpx_sellerId;
// $fpx_sellerOrderNo=$request->fpx_sellerOrderNo;
// $fpx_sellerTxnTime=$request->fpx_sellerTxnTime;
// $fpx_txnAmount=$request->fpx_txnAmount;
// $fpx_txnCurrency=$request->fpx_txnCurrency;
// $fpx_checkSum=$request->fpx_checkSum;

 $val="00";
 $ErrorCode=" Your signature has been verified successfully. "." ErrorCode :[00]";

// if val is 00 sucess 
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>PRIM</title>
    <style>
        /* -------------------------------------
            GLOBAL
            A very basic CSS reset
        ------------------------------------- */
        * {
            margin: 0;
            padding: 0;
            font-family: "Helvetica Neue", "Helvetica", Helvetica, Arial, sans-serif;
            box-sizing: border-box;
            font-size: 14px;
        }

        img {
            max-width: 100%;
        }

        body {
            -webkit-font-smoothing: antialiased;
            -webkit-text-size-adjust: none;
            width: 100% !important;
            height: 100%;
            line-height: 1.6;
        }

        /* Let's make sure all tables have defaults */
        table td {
            vertical-align: top;
        }

        /* -------------------------------------
            BODY & CONTAINER
        ------------------------------------- */
        body {
            background-color: #f6f6f6;
        }

        .body-wrap {
            background-color: #f6f6f6;
            width: 100%;
        }

        .container {
            display: block !important;
            max-width: 600px !important;
            margin: 0 auto !important;
            /* makes it centered */
            clear: both !important;
        }

        .content {
            max-width: 600px;
            margin: 0 auto;
            display: block;
            padding: 20px;
        }

        /* -------------------------------------
            HEADER, FOOTER, MAIN
        ------------------------------------- */
        .main {
            background: #fff;
            border: 1px solid #e9e9e9;
            border-radius: 3px;
        }

        /* .content-wrap {
            padding: 20px;
        } */

        .content-block {
            padding: 30px 0 20px;
            text-align: center;
        }

        .header {
            width: 100%;
            margin-bottom: 20px;
        }

        .footer {
            width: 100%;
            clear: both;
            color: #999;
            padding: 20px;
        }
        .footer a {
            color: #999;
        }
        .footer p, .footer a, .footer unsubscribe, .footer td {
            font-size: 12px;
        }

        /* -------------------------------------
            TYPOGRAPHY
        ------------------------------------- */
        h1, h2, h3 {
            font-family: "Helvetica Neue", Helvetica, Arial, "Lucida Grande", sans-serif;
            color: #000;
            margin: 40px 0 0;
            line-height: 1.2;
            font-weight: 400;
        }

        h1 {
            font-size: 32px;
            font-weight: 500;
        }

        h2 {
            font-size: 24px;
        }

        h3 {
            font-size: 12px;
        }

        h4 {
            font-size: 14px;
            font-weight: 600;
        }

        p, ul, ol {
            margin-bottom: 10px;
            font-weight: normal;
        }
        p li, ul li, ol li {
            margin-left: 5px;
            list-style-position: inside;
        }

        /* -------------------------------------
            LINKS & BUTTONS
        ------------------------------------- */
        a {
            color: #1ab394;
            text-decoration: underline;
        }

        .btn-primary {
            text-decoration: none;
            color: #FFF;
            background-color: #1ab394;
            border: solid #1ab394;
            border-width: 5px 10px;
            line-height: 2;
            font-weight: bold;
            text-align: center;
            cursor: pointer;
            display: inline-block;
            border-radius: 5px;
            text-transform: capitalize;
        }

        /* -------------------------------------
            OTHER STYLES THAT MIGHT BE USEFUL
        ------------------------------------- */
        .last {
            margin-bottom: 0;
        }

        .first {
            margin-top: 0;
        }

        .aligncenter {
            text-align: center;
        }

        .alignright {
            text-align: right;
        }

        .alignleft {
            text-align: left;
        }

        .clear {
            clear: both;
        }

        /* -------------------------------------
            ALERTS
            Change the class depending on warning email, good email or bad email
        ------------------------------------- */
        .alert {
            font-size: 16px;
            color: #fff;
            font-weight: 500;
            padding: 20px;
            text-align: center;
            border-radius: 3px 3px 0 0;
        }
        .alert a {
            color: #fff;
            text-decoration: none;
            font-weight: 500;
            font-size: 16px;
        }
        .alert.alert-warning {
            background: #f8ac59;
        }
        .alert.alert-bad {
            background: #ed5565;
        }
        .alert.alert-good {
            background: #1ab394;
        }

        /* -------------------------------------
            INVOICE
            Styles for the billing table
        ------------------------------------- */
        .invoice {
            margin: 40px auto;
            text-align: left;
            width: 80%;
        }
        .invoice td {
            padding: 5px 0;
        }
        .invoice .invoice-items {
            width: 100%;
        }
        .invoice .invoice-items td {
            border-top: #eee 1px solid;
        }
        .invoice .invoice-items .total td {
            border-top: 2px solid #333;
            border-bottom: 2px solid #333;
            font-weight: 700;
        }

        .center {
            display: flex;
            justify-content: center;
        }

        /* -------------------------------------
            RESPONSIVE AND MOBILE FRIENDLY STYLES
        ------------------------------------- */
        @media only screen and (max-width: 640px) {
            h1, h2, h3, h4 {
                font-weight: 600 !important;
                margin: 20px 0 5px !important;
            }

            h1 {
                font-size: 22px !important;
            }

            h2 {
                font-size: 18px !important;
            }

            h3 {
                font-size: 16px !important;
            }

            .container {
                width: 100% !important;
            }

            .content, .content-wrap {
                padding: 10px !important;
            }

            .invoice {
                width: 100% !important;
            }

            
        }
    </style>
    @include('layouts.head')

</head>
<body>
    <table class="body-wrap">
    <tbody><tr>
        <td></td>
        <td class="container" width="600">
            <div class="content">
                <table class="main" width="100%" cellpadding="0" cellspacing="0">
                    <tbody><tr>
                        <td class="content-wrap aligncenter">
                            <table width="100%" cellpadding="0" cellspacing="0">
                                <tbody>
                                <tr>
                                    <td>
                                        <h2>{{ $organizationName }}</h2>
                                        <h3 style="margin: 20px 0 0 0; font-size: 14px !important">({{ $ogranizationAddress }})</h3>
                                        <h3 style="margin: 20px 0 0 0; font-size: 14px !important">{{ $organizationTelNo }} | {{ $organizationEmail }}</h3>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <table class="invoice">
                                            <tbody>
                                                <tr>
                                                <td>
                                                    <b>Nama :</b> {{ $transactionUsername }} ({{ $transactionIcno }})<br>
                                                    <b>Email :</b> {{ $transactionEmail }}<br>
                                                    <b>Alamat Menyurat :</b> {{ $transactionUserAdress }}<br>
                                                    <b>Nombor Resit :</b> {{ $transactionName }}<br>
                                                    <b>Tarikh Derma :</b> {{ date('d-m-Y', strtotime($transactionDate)) }}
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>
                                                    <table class="invoice-items" cellpadding="0" cellspacing="0">
                                                        <tbody><tr>
                                                            <td>{{ $donationName }} ({{ $doantionLHDNcode }} : {{ date('d-m-Y', strtotime($doantionStartDate)) }} - {{ date('d-m-Y', strtotime($doantionEndDate)) }})</td>
                                                            <td class="alignright">RM {{ number_format($transactionAmount , 2, '.', '') }}</td>
                                                        </tr>
                                                        <tr class="total">
                                                            <td class="alignright" width="80%">Total</td>
                                                            <td class="alignright">RM  {{ number_format($transactionAmount , 2, '.', '') }}</td>
                                                        </tr>
                                                    </tbody></table>
                                                </td>
                                            </tr>
                                        </tbody></table>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        </td>
                    </tr>
                </tbody>
            </table>
            </div>
        </td>
        <td></td>
    </tr>
</tbody>
</table>

<div class="center">
    <button class="btn btn-primary" style="font-size:18px" onclick="window.print();">Cetak Resit</button>
</div>
</body>
</html>
