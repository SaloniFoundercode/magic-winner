<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DB;
use App\Models\{User,BusinessSetting};
// use App\Models\Project_maintenance;

class WidthdrawlController extends Controller
{
    public function widthdrawl_index($id)
    {
		
        // Fetch all records from the Project_maintenance model
        $widthdrawls = DB::select("SELECT withdraws.*, users.name AS uname, users.mobile AS mobile, bank_details.account_num AS acno, bank_details.bank_name AS bname, bank_details.ifsc_code AS ifsc FROM withdraws LEFT JOIN users ON withdraws.user_id = users.id LEFT JOIN bank_details ON bank_details.id = withdraws.account_id WHERE withdraws.`status`=$id && withdraws.type=0 order by withdraws.id desc ;");

        // Pass the data to the view and load the 'project_maintenance.index' Blade file
        return view('widthdrawl.index', compact('widthdrawls'))->with($id,'id');
	 
			
    }

	public function success(Request $request, $id)
{
    // Check if session has 'id'
    $value = $request->session()->has('id');
    
    if (!empty($value)) {

        // Perform the SQL query
        $data = DB::select("SELECT bank_details.*, users.email AS email, users.mobile AS mobile, withdraws.amount AS amount, business_settings.longtext AS mid, 
                            (SELECT business_settings.longtext FROM business_settings WHERE id = 13) AS token, 
                            (SELECT business_settings.longtext FROM business_settings WHERE id = 14 ) AS orderid 
                            FROM bank_details 
                            LEFT JOIN users ON bank_details.userid = users.id 
                            LEFT JOIN withdraws ON withdraws.user_id = users.id AND withdraws.account_id = bank_details.id 
                            LEFT JOIN business_settings ON business_settings.id = 12 
                            WHERE withdraws.id = ?", [$id]);

        // Check if data is not empty
        if (!empty($data)) {
            // Extract values from the result
            $object = $data[0]; // Assuming you only have one record
            $name = $object->name;
            $ac_no = $object->account_num;
            $ifsc = $object->ifsc_code;
            $bankname = $object->bank_name;
            $email = $object->email;
            $mobile = $object->mobile;
            $amount = $object->amount;
            $mid = $object->mid;
            $token = $object->token;
            $orderid = $object->orderid;
        } else {
            return redirect()->route('withdrawal')->with('error', 'No data found for the given withdrawal ID.');
        }

        // Generate a random order ID
        $rand = rand(11111111, 99999999);
        $randid = "$rand";

        // Prepare the payout data as an array
        $payoutdata = json_encode([
            "merchant_id" => $mid,
            "merchant_token" => $token,
            "account_no" => $ac_no,
            "ifsccode" => $ifsc,
            "amount" => $amount,
            "bankname" => $bankname,
            "remark" => "payout",
            "orderid" => $randid,
            "name" => $name,
            "contact" => $mobile,
            "email" => $email
        ]);
        
        // Encode the payout data in base64
        $salt = base64_encode($payoutdata);
        
        // Prepare the JSON payload
        $json = [
            "salt" => $salt
        ];
        
        // Initialize cURL session
        $curl = curl_init();
        
        // Set cURL options
        curl_setopt_array($curl, [
            CURLOPT_URL => 'https://indianpay.co.in/admin/single_transaction',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($json), // Send the JSON data
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json' // Set the Content-Type header to application/json
            ],
        ]);
        
        // Execute cURL request and get the response
        $response = curl_exec($curl);
        
        // Check if cURL executed successfully
        if (curl_errno($curl)) {
            // Handle cURL errors
            echo 'Error: ' . curl_error($curl);
            curl_close($curl);
            return redirect()->route('withdrawal')->with('error', 'Payment processing error.');
        }
        
        // Decode the JSON response
        $datta = json_decode($response);
        
        // Check if response contains valid data
        if (isset($datta->status) && isset($datta->error)) {
            $status = $datta->status;
            $error = $datta->error;
            
            // If the status is 400, return an error message
            if ($status == 400) {
				 DB::table('withdraws')->where('id', $id)->update([
            'status' => 3,
            'response' => $response
        ]);
         DB::table('users')->where('id', $id)->update([
    'wallet' => DB::raw('wallet + ?', [$object->amount])
]);

        
                return redirect()->back()->with('error', $error);
            }
        } else {
            return redirect()->route('widthdrawl', '1')->with('error', 'Invalid response from payment gateway.');
        }
        
        // Update the withdrawal status in the database
        DB::table('withdraws')->where('id', $id)->update([
            'status' => 2,
            'response' => $response
        ]);
        //return redirect()->route('widthdrawl', '1')->with('key', 'value');
        // Redirect back with a success message
        return redirect()->route('widthdrawl', '1')->with('success', 'Withdrawal request successfully processed.');

    } else {
        // If no session ID is present, redirect to login page
        return redirect()->route('login');
    }
}


    public function success_oold(Request $request,$id)
    {
		$value = $request->session()->has('id');
		
     if(!empty($value))
        {
        
         $data=DB::select("SELECT bank_details.*, users.email AS email, users.mobile AS mobile, withdraws.amount AS amount, business_settings.longtext AS mid, (SELECT business_settings.longtext FROM business_settings WHERE id = 13) AS token, (SELECT business_settings.longtext FROM business_settings WHERE id = 14 ) AS orderid FROM bank_details LEFT JOIN users ON bank_details.userid = users.id LEFT JOIN withdraws ON withdraws.user_id = users.id && withdraws.account_id=bank_details.id LEFT JOIN business_settings ON business_settings.id = 12 WHERE withdraws.id=$id;");
      
       //dd($data);
         foreach ($data as $object) {
            
            // $object->amount
            $name= $object->name;
            $ac_no= $object->account_num;
            $ifsc=$object->ifsc_code;
            $bankname= $object->bank_name;
            $email= $object->email;
            $mobile=$object->mobile;
            $amount=$object->amount;
            $mid=$object->mid;
            $token=$object->token;
            $orderid=$object->orderid;
        }
		//echo $mid;
        $rand=rand(11111111,99999999);
      $randid="$rand";
      //$amount
       $payoutdata=  json_encode(array(    
         "merchant_id"=>$mid,
         "merchant_token"=>$token,
         "account_no"=>$ac_no,
         "ifsccode"=>$ifsc,
         "amount"=>$amount,
         "bankname"=>$bankname,
         "remark"=>"payout",
         "orderid"=>$randid,
         "name"=>$name,
         "contact"=>$mobile,
         "email"=>$email
      ));
       //dd($payoutdata);
    // Encode the payout data using base64
    $salt = base64_encode($payoutdata);
    
    // Prepare the JSON data
    $json = [
        "salt" => $salt
    ];
    
    // Initialize cURL session
    $curl = curl_init();
    
    // Set cURL options
    curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://indianpay.co.in/admin/single_transaction',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => json_encode($json), // Encode JSON data
        CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json' // Set Content-Type header
        ),
    ));
    
    // Execute cURL request and get the response
    $response = curl_exec($curl);
		 dd($response);
		    $datta=json_decode($response);
		 dd($datta);
         // dd($datta);
   $status = $datta->status;
$error = $datta->error;

// Check if the status is 400
if ($status == 400) {
    return redirect()->back()->with('error', $error);
}

    //dd($response);
    // Check for errors
    if (curl_errno($curl)) {
        echo 'Error: ' . curl_error($curl);
    } else {
        // Print the response
        echo $response;
    }
    
    // Close cURL session
    curl_close($curl);

    

		 
		
    DB::select("UPDATE `withdraws` SET `status`='2',`response`='$response' WHERE id=$id;");
		 return redirect()->route('widthdrawl', '1')->with('key', 'value');

    }
		else
        {
           return redirect()->route('login');  
        }
			
			
    }
		
		
		
    public function reject(Request $request,$id)
  {
		
		
  $rejectionReason = $request->input('msg');
		
		$data=DB::select("SELECT * FROM `withdraws` WHERE id=$id;");
	
		$amt=$data[0]->amount;
		$useid=$data[0]->user_id;
         $value = $request->session()->has('id');
			
     if(!empty($value))
        {
            // dd("UPDATE `withdraws` SET `status`='3' WHERE id=$id;");
     $ss= DB::select("UPDATE `withdraws` SET `status`='3',`rejectmsg`='$rejectionReason' WHERE id=$id;");
    //dd("UPDATE `users` SET `wallet`=`wallet`+'$amt' WHERE id=$useid;");
	DB::select("UPDATE `users` SET `wallet`=`wallet`+'$amt' WHERE id=$useid;");
		 $deduct_jili = jilli::add_in_jilli_wallet($useid,$amt);
	//DB::select("UPDATE `users` SET `wallet`=`wallet`+'$amt',`winning_wallet`=`winning_wallet`+'$amt' WHERE id=$useid;");
		         //return view('widthdrawl.index', compact('widthdrawls'))->with($id,'0');
return redirect()->route('widthdrawl', '1')->with('key', 'value');
		  }
		 else
        {
           return redirect()->route('login');  
        }
			

       // return redirect()->route('widthdrawl/0');
  }

    
    public function all_success()    
    {           
		$value = $request->session()->has('id');
		
     if(!empty($value))
        {
      DB::select("UPDATE `withdraws` SET `status`='2' WHERE `status`='1';");
		         return view('widthdrawl.index', compact('widthdrawls'))->with($id,'1');
	 }
else
        {
           return redirect()->route('login');  
        }
			
      //return redirect()->route('widthdrawl/0');
    }
	
	public function indiaonlin_payout(Request $request,$id)
    {
		$value = $request->session()->has('id');
		
     if(!empty($value))
        {
        
         $data=DB::select("SELECT bank_details.*, users.email AS email, users.mobile AS mobile, withdraws.amount AS amount, business_settings.longtext AS mid, (SELECT business_settings.longtext FROM business_settings WHERE id = 13) AS token, (SELECT business_settings.longtext FROM business_settings WHERE id = 14 ) AS orderid FROM bank_details LEFT JOIN users ON bank_details.userid = users.id LEFT JOIN withdraws ON withdraws.user_id = users.id && withdraws.account_id=bank_details.id LEFT JOIN business_settings ON business_settings.id = 12 WHERE withdraws.id=$id;");
       
         foreach ($data as $object) {
            
            $name= $object->name;
            $ac_no= $object->account_num;
            $ifsc=$object->ifsc_code;
            $bankname= $object->bank_name;
            $email= $object->email;
            $mobile=$object->mobile;
            $amount=$object->amount;
           
            $token=$object->token;
            $orderid=$object->orderid;
        }
$rand = rand(11111111, 99999999);
$date = date('YmdHis');
$invoiceNumber = $date . $rand;
		 
		$data = [
    "merchantId" => "",
    "secretKey" => "",
    "apiKey" => "5692d831-decd-450c-8ff5-d1d11943dc82",
    "invoiceNumber" => $invoiceNumber,
    "customerName" => $name,
    "phoneNumber" => $mobile,
    "payoutMode" => "IMPS",
    "payoutAmount" => 1,
    "accountNo" => $ac_no,
    "ifscBankCode" => $ifsc,
    "ipAddress" => "35.154.155.190"
];

		 
         $encodeddata=json_encode($data);
		
			$curl = curl_init();

			curl_setopt_array($curl, array(
			  CURLOPT_URL => 'https://indiaonlinepay.com/api/iop/payout',
			  CURLOPT_RETURNTRANSFER => true,
			  CURLOPT_ENCODING => '',
			  CURLOPT_MAXREDIRS => 10,
			  CURLOPT_TIMEOUT => 0,
			  CURLOPT_FOLLOWLOCATION => true,
			  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			  CURLOPT_CUSTOMREQUEST => 'POST',
			  CURLOPT_POSTFIELDS =>$encodeddata,
			  CURLOPT_HTTPHEADER => array(
				'Content-Type: application/json',
				'Cookie: Path=/'
			  ),
			));

			$response = curl_exec($curl);

			curl_close($curl);
		 
			echo  $response; 
		 $dataArray = json_decode($response, true);

         $referenceId=$dataArray['Data']['ReferenceId'];
		 $Status=$dataArray['Data']['Status'];
		 if($Status == "Received"){
		 
   
         DB::select("UPDATE `withdraws` SET `referenceId`='$referenceId',`response`='$response',status='2' WHERE id=$id;");
		 return redirect()->route('widthdrawl', '1')->with('key', 'value');
		 }
       return redirect()->route('widthdrawl', '1')->with('key', 'value');
    }
		else
        {
           return redirect()->route('login');  
        }
			
			
    }
	
	
	



}
