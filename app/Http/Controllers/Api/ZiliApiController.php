<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str; 
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\URL;
use App\Models\User;
use App\Models\Slider;
use App\Models\BankDetail; // Import your model
use Carbon\Carbon;
use App\Models\Payin;
use App\Models\WalletHistory;
use App\Models\withdraw;
use App\Models\GiftCard;
use App\Models\GiftClaim;
use App\Models\CustomerService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;


class ZiliApiController extends Controller
{
	
	public function get_reseller_info(?string $manager_key=null){
		$manager_key = $manager_key??'FEGISo8cR74cf';
		$apiUrl = 'https://api.gamebridge.co.in/seller/v1/get-reseller-info';
		//$manager_key = 'FEGISo8cR74cf';
		$authorizationtoken='1740116434200';
	    $headers = [
			'authorization' => 'Bearer ' .$manager_key,
			'authorizationtoken' => 'Bearer ' .$authorizationtoken
		];
		
		try {
				$response = Http::withHeaders($headers)->get($apiUrl);
				$apiResponse = json_decode($response->body());
               if ($response->successful() && isset($apiResponse->error) && $apiResponse->error == false) {
					return response()->json(['status'=>200,'message'=>$apiResponse,]);
				}
				// Handle API errors
				return response()->json(['status'=>400,'message'=>$apiResponse]);
			} catch (\Exception $e) {
				// Log exception
				Log::error('PayIn API Error:', ['error' => $e->getMessage()]);
				// Return server error response
				return response()->json(['status'=>400,'message'=>$e->getMessage()]);
			}
	}
	
	public function test_get_user_info(){
	/*	 $apiUrl = 'https://jiliapi.igtechgaming.com/jili/get-jiliuser-info?userId=llf7llbqvyea23naa8ikehjkr6';
		$response = Http::get($apiUrl);
		$apiResponse = json_decode($response->body());
		dd($apiResponse);    */
		
		
		//$token=User::where('id',$user_id)->first();
		$user_token='eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJkYXRhIjp7ImppbGxpX2FjY291bnRfaWQiOiJsNndxYm92d2w2ajN3YTE1M2x0NDJoY2E2YiJ9LCJhbGdvcml0aG0iOiJSUzI1NiIsImlhdCI6MTczNTIyMzA0M30.fGj4yHHkkzfAJcmiXxcU2qMvBTJb_VzXY6gLzA1rq5c';
		
		$apiUrl = 'https://api.gamebridge.co.in/seller/v1/get-user-info';
		$manager_key = 'FEGISo8cR74cf';
	    $headers = [
				'authorization' => 'Bearer ' .$manager_key,
				'validateuser' => 'Bearer '.$user_token
			];
		$payloadpar = ['payload'=>''];
		
		try {
				$response = Http::withHeaders($headers)->post($apiUrl, $payloadpar);
				$apiResponse = json_decode($response->body());
			dd($apiResponse);
			
			$money=$apiResponse->money;
			//dd($money);
				// Check if API call was successful
				if ($response->successful() && isset($apiResponse->error) && $apiResponse->error == false) {
					$update=User::where('id', $user_id)->update(['wallet' => $money]);
					return response()->json(['status'=>200,'message'=>$apiResponse->msg,'Updated_money'=>$money]);
				}
				// Handle API errors
				return response()->json(['status'=>400,'message'=>$apiResponse->msg]);
			} catch (\Exception $e) {
				// Log exception
				Log::error('PayIn API Error:', ['error' => $e->getMessage()]);
				// Return server error response
				return response()->json(['status'=>400,'message'=>$e->getMessage()]);
			}
		

	}
	
	
	public function user_register(Request $request){
	
         $validator = Validator::make($request->all(), [
					'mobile' => 'required|unique:users,mobile'
					//'email' => 'required|email|unique:users,email'
				]);
				$validator->stopOnFirstFailure();
				if ($validator->fails()) {
					return response()->json(['status' => 400, 'message' => $validator->errors()->first()], 200);
				}     
			$mobile = $request->mobile;
			//$email = $request->email;   
				$authorizationtoken='1740116434200';
			$manager_key = 'FEGISo8cR74cf';
			$apiUrl = 'https://api.gamebridge.co.in/seller/v1/get-newjilli-game-registration';
			
			    // Custom headers
			$headers = [
				'authorization' => 'Bearer ' . $manager_key,
				'authorizationtoken' => 'Bearer ' . $authorizationtoken
			];

				//   request data //
			$requestData = ['mobile' => $mobile];
			$requestData  = json_encode($requestData);
			$requestData  = base64_encode($requestData);
		    $payload = ['payload'=>$requestData];
		
			try {
				// Make API request with headers and JSON body
				$response = Http::withHeaders($headers)->post($apiUrl, $payload);

				// Log response
			   // Log::info('PayIn API Response:', ['response' => $response->body()]);
			   // Log::info('PayIn API Status Code:', ['status' => $response->status()]);
                //dd($response->body());
				// Parse API response
				$apiResponse = json_decode($response->body());
				//dd($apiResponse);
				

				// Check if API call was successful
				if ($response->successful() && isset($apiResponse->error) && $apiResponse->error == false) {
					  $account_no = $apiResponse->accountNo;
					//dd($account_token);
					  $inserted_id = DB::table('users')->insertGetId(['mobile'=>$mobile , 'accountNo'=>$account_no]);
					  return response()->json([
						  'status' => 200,
						  'message' => 'user registered successfully.',
						   'data' =>$apiResponse,'id'=>$inserted_id
					  ], 200); 
				}

				// Handle API errors
				return response()->json(['status' => 400,'message' => 'Failed to register.', 'api_response' => $response->body()], 400);
			} catch (\Exception $e) {
				// Log exception
				Log::error('PayIn API Error:', ['error' => $e->getMessage()]);
				// Return server error response
				return response()->json(['status' => 400, 'message' => 'Internal Server Error','error' => $e->getMessage()], 400);
			}
       }
	public function all_game_list(Request $request){
			
			$apiUrl = 'https://api.gamebridge.co.in/seller/v1/get-all-games-list';
			$token = 'FEGISo8cR74cf';
			$authorizationtoken='1740116434200';
			$headers = [
				'authorization' => 'Bearer ' .$token,
				'authorizationtoken' => 'Bearer ' .$authorizationtoken
			];
			$payload = ['payload'=>''];
			
			try {
				// Make API request with headers and JSON body
				$response = Http::withHeaders($headers)->post($apiUrl, $payload);
				//dd($response);
				// Parse API response
				$apiResponse = json_decode($response->body());
				//dd($apiResponse);
                
				// Check if API call was successful
				if ($response->successful() && isset($apiResponse->error) && $apiResponse->error == false) {
					  return response()->json([
						  'status' => 200,
						  'message' => 'Game list..',
						   'data' =>$apiResponse->data,
						  'fish' =>$apiResponse->fish,
						  'slot' =>$apiResponse->slot,
						  'tableandcard' =>$apiResponse->tableandcard,
						  'crash' =>$apiResponse->crash
					  ], 200); 
				}

				// Handle API errors
				return response()->json(['status' => 400,'message' => 'Failed to get game list.', 'api_response' => $response->body()], 400);
			} catch (\Exception $e) {
				// Log exception
				Log::error('PayIn API Error:', ['error' => $e->getMessage()]);
				// Return server error response
				return response()->json(['status' => 400, 'message' => 'Internal Server Error','error' => $e->getMessage()], 400);
			}
		}
	
		public function all_game_list_old(Request $request){
			$validator = Validator::make($request->all(), [
				'user_id' => 'required|exists:users,id'
			]);
			$validator->stopOnFirstFailure();
			if ($validator->fails()) {
				return response()->json(['status' => 400, 'message' => $validator->errors()->first()], 200);
			} 
			$user_id = $request->user_id;
			$account_token = DB::table('users')->where('id',$user_id)->value('accountNo');
			
			$apiUrl = 'https://api.gamebridge.co.in/seller/v1/get-all-games-list';
			$token = 'FEGISo8cR74cf';
			
			$headers = [
				'authorization' => 'Bearer ' .$token,
				'validateuser' => 'Bearer '.$account_token,
				'authorizationtoken' => '1740116434200'
			];
			$payload = ['payload'=>''];
			
			try {
				// Make API request with headers and JSON body
				$response = Http::withHeaders($headers)->post($apiUrl, $payload);

				// Log response
			   // Log::info('PayIn API Response:', ['response' => $response->body()]);
			   // Log::info('PayIn API Status Code:', ['status' => $response->status()]);

				// Parse API response
				$apiResponse = json_decode($response->body());
                
				// Check if API call was successful
				if ($response->successful() && isset($apiResponse->error) && $apiResponse->error == false) {
					  return response()->json([
						  'status' => 200,
						  'message' => 'Game list..',
						   'data' =>$apiResponse->data,
						  'fish' =>$apiResponse->fish,
						  'slot' =>$apiResponse->slot,
						  'tableandcard' =>$apiResponse->tableandcard,
						  'crash' =>$apiResponse->crash
					  ], 200); 
				}

				// Handle API errors
				return response()->json(['status' => 400,'message' => 'Failed to get game list.', 'api_response' => $response->body()], 400);
			} catch (\Exception $e) {
				// Log exception
				Log::error('PayIn API Error:', ['error' => $e->getMessage()]);
				// Return server error response
				return response()->json(['status' => 400, 'message' => 'Internal Server Error','error' => $e->getMessage()], 400);
			}
		}
	
	public function get_game_url_old(Request $request){
                $validator = Validator::make($request->all(), [
								'user_id' => 'required|exists:users,id',
								'game_id' => 'required'
							]);
				$validator->stopOnFirstFailure();
				if ($validator->fails()) {
					return response()->json(['status' => 400, 'message' => $validator->errors()->first()], 200);
				} 
		
				$user_id = $request->user_id;
				$game_id = $request->game_id;
		        $apiUrl = 'https://api.gamebridge.co.in/seller/v1//get-newjilli-game-url-by-gameid';
		        $account_token = DB::table('users')->where('id',$user_id)->value('accountNo');
		        $manager_key = 'FEGISo8cR74cf';
		        $headers = [
							'authorization' => 'Bearer ' .$manager_key,
							'validateuser' => 'Bearer '.$account_token
						];
		       $pay_load = ['game_id'=>$game_id,'mobile'=>$account_token];
		       $pay_load = json_encode($pay_load);
		       $pay_load = base64_encode($pay_load);
		       $payloadpar = ['payload'=>$pay_load];  
		
		try {
				$response = Http::withHeaders($headers)->post($apiUrl, $payloadpar);
				$apiResponse = json_decode($response->body());
			    //dd($apiResponse);
			    $data = $apiResponse->gameUrl;
		        $game_url = $data->gameUrl;
			
			
				// Check if API call was successful
				if ($response->successful() && isset($apiResponse->error) && $apiResponse->error == false) {
					  return response()->json([
						  'status' => 200,
						  'message' => 'Game url..',
						  'game_url'=>$game_url,
						   //'data' =>$apiResponse->gameUrl,
					  ], 200); 
				}

				// Handle API errors
				return response()->json(['status' => 400,'message' => 'Failed to get game list.', 'api_response' => $response->body()], 400);
			} catch (\Exception $e) {
				// Log exception
				Log::error('PayIn API Error:', ['error' => $e->getMessage()]);
				// Return server error response
				return response()->json(['status' => 400, 'message' => 'Internal Server Error','error' => $e->getMessage()], 400);
			}
	}
	
	public function get_game_url(Request $request)
{
    $validator = Validator::make($request->all(), [
        'user_id' => 'required|exists:users,id',
        'game_id' => 'required',
    ]);
    $validator->stopOnFirstFailure();

    if ($validator->fails()) {
        return response()->json(['status' => 400, 'message' => $validator->errors()->first()], 200);
    }

    $user_id = $request->user_id;
    $game_id = $request->game_id;
		/// add winning_wallet_transfer///
		$user = User::findOrFail($user_id);
    $status = $user->status;
    $main_wallet = $user->wallet;
    $thirdpartywallet = $user->winning_wallet;
    $add_main_wallet = $main_wallet + $thirdpartywallet;
    
    if ($status == 1) { 
        $user->winning_wallet = $add_main_wallet;
        $user->wallet = 0;
        $user->save();
    }
		////end add winning_wallet_transfer///
 // First, update the wallet
    $this->update_jilli_wallets($request);
		
		
		
    $apiUrl = 'https://api.gamebridge.co.in/seller/v1/get-newjilli-game-url-by-gameid';
    $manager_key = 'FEGISo8cR74cf';

    // Get the user's account token
    $account_token = DB::table('users')->where('id', $user_id)->value('accountNo');
    if (!$account_token) {
        return response()->json(['status' => 400, 'message' => 'Invalid account token.'], 400);
    }
$authorizationtoken='1740116434200';
    // Prepare headers and payload
    $headers = [
        'authorization' => 'Bearer ' . $manager_key,
        'validateuser' => 'Bearer ' . $account_token,
		'authorizationtoken' => 'Bearer ' . $authorizationtoken
    ];

    $payload = base64_encode(json_encode(['gameId' => $game_id, 'mobile' => $account_token]));
    $payloadPar = ['payload' => $payload];

    try {
        // Send API request
        $response = Http::withHeaders($headers)->post($apiUrl, $payloadPar);
        $apiResponse = json_decode($response->body());

        // Check if the response and gameUrl exist
        if ($response->successful() && isset($apiResponse->error) && !$apiResponse->error) {
            if (isset($apiResponse->gameUrl)) {
                $game_url = $apiResponse->gameUrl;
                return response()->json([
                    'status' => 200,
                    'message' => 'Game URL retrieved successfully.',
                    'game_url' => $game_url,
                ], 200);
            } else {
                return response()->json([
                    'status' => 400,
                    'message' => 'Game URL not found in the response.',
                ], 400);
            }
        }

        // Handle API error responses
        return response()->json([
            'status' => 400,
            'message' => 'Failed to get game URL.',
            'api_response' => $response->body(),
        ], 400);
    } catch (\Exception $e) {
        // Log the exception
        Log::error('Get Game URL API Error:', ['error' => $e->getMessage()]);

        // Return an internal server error response
        return response()->json([
            'status' => 500,
            'message' => 'Internal Server Error',
            'error' => $e->getMessage(),
        ], 500);
    }
}

	public function update_jilli_wallets(Request $request){
		 $validator = Validator::make($request->all(), [
								'user_id' => 'required|exists:users,id',
		                    	//'amount'=>'required|numeric|gt:0'
							]);
				$validator->stopOnFirstFailure();
				if ($validator->fails()) {
					return response()->json(['status' => 400, 'message' => $validator->errors()->first()], 200);
				} 
		
		$user_id = $request->user_id;
		//$amount = $request->amount;
		$account_token = DB::table('users')
    ->where('id', $user_id)
    ->select('accountNo', 'winning_wallet')
    ->first();
		$userId=$account_token->accountNo;
		$wallet=$account_token->winning_wallet;

		//dd($userId,$wallet);
		$apiUrl = 'https://api.gamebridge.co.in/seller/v1/assign-same-new-jilli-wallet';
		$manager_key = 'FEGISo8cR74cf';
		$authorizationtoken='1740116434200';
	    $headers = [
				'authorization' => 'Bearer ' .$manager_key,
				'validateuser' => 'Bearer '.$userId,
			'authorizationtoken' => 'Bearer '.$authorizationtoken
			];
		$pay_load = ['amount'=>$wallet,'mobile'=>$userId];
		$pay_load = json_encode($pay_load);
		$pay_load = base64_encode($pay_load);
		$payloadpar = ['payload'=>$pay_load];
		
		try {
				$response = Http::withHeaders($headers)->post($apiUrl, $payloadpar);
				$apiResponse = json_decode($response->body());
			   //dd($apiResponse);
				// Check if API call was successful
				if ($response->successful() && isset($apiResponse->error) && $apiResponse->error == false) {
					return response()->json(['status'=>200,'message'=>$apiResponse->msg]);
				}
				// Handle API errors
				return response()->json(['status'=>400,'message'=>$apiResponse->msg]);
			} catch (\Exception $e) {
				// Log exception
				Log::error('PayIn API Error:', ['error' => $e->getMessage()]);
				// Return server error response
				return response()->json(['status'=>400,'message'=>$e->getMessage()]);
			}
	}
	
	
	public function get_jilli_transactons_details(Request $request){
                 $validator = Validator::make($request->all(), [
								'user_id' => 'required|exists:users,id'
							]);
				$validator->stopOnFirstFailure();
				if ($validator->fails()) {
					return response()->json(['status' => 400, 'message' => $validator->errors()->first()], 200);
				} 
		$user_id = $request->user_id;
		$account_token = DB::table('users')->where('id',$user_id)->value('accountNo');
		//dd($account_token);
		$apiUrl = 'https://api.gamebridge.co.in/seller/v1/get-new-jilli-transaction-his';
	    $manager_key = 'FEGISo8cR74cf';
		$authorizationtoken='1740116434200';
	    $headers = [
					'authorization' => 'Bearer ' .$manager_key,
					'validateuser' => 'Bearer '.$account_token,
			'authorizationtoken' => 'Bearer '.$authorizationtoken
				   ];
		//dd($headers);
		 $payload = base64_encode(json_encode(['mobile' => $account_token]));
		//dd($payload);
        $payloadpar = ['payload' => $payload];
		//$payloadpar = ['payload'=>''];
		
		try {
				$response = Http::withHeaders($headers)->post($apiUrl, $payloadpar);
			    //dd($response);
				$apiResponse = json_decode($response->body());
			    //dd($apiResponse);
				// Check if API call was successful
				if ($response->successful() && isset($apiResponse->error) && $apiResponse->error == false) {
					  return response()->json([
						  'status' => 200,
						  'message' => 'Transaction details..',
						  'data' =>$apiResponse->data
					  ], 200); 
				}

				// Handle API errors
				return response()->json(['status' => 400,'message' => 'Failed to get transaction details.', 'api_response' => $response->body()], 400);
			} catch (\Exception $e) {
				// Log exception
				Log::error('PayIn API Error:', ['error' => $e->getMessage()]);
				// Return server error response
				return response()->json(['status' => 400, 'message' => 'Internal Server Error','error' => $e->getMessage()], 400);
			}
	}
	 //deduct-newjilliuser-wallet-by-id
	
	public function jilli_deduct_from_wallet(Request $request){  
		 $validator = Validator::make($request->all(), [
								'user_id' => 'required|exists:users,id',
			                     'amount'=>'required|numeric|gt:0'
							]);
				$validator->stopOnFirstFailure();
				if ($validator->fails()) {
					return response()->json(['status' => 400, 'message' => $validator->errors()->first()], 200);
				} 
		$user_id = $request->user_id;
		$amount = $request->amount;
		$account_token = DB::table('users')->where('id',$user_id)->value('accountNo');
		$apiUrl = 'https://api.gamebridge.co.in/seller/v1/deduct-newjilliuser-wallet-by-id';
	    $manager_key = 'FEGISo8cR74cf';
		$authorizationtoken='1740116434200';
	    $headers = [
					'authorization' => 'Bearer ' .$manager_key,
					'validateuser' => 'Bearer '.$account_token,
			'authorizationtoken' => 'Bearer '.$authorizationtoken
				   ];
	
		       $pay_load = ['amount'=>$amount,'mobile'=>$account_token];
		       $pay_load = json_encode($pay_load);
		       $pay_load = base64_encode($pay_load);
		       $payloadpar = ['payload'=>$pay_load];
		
		try {
				$response = Http::withHeaders($headers)->post($apiUrl, $payloadpar);
				$apiResponse = json_decode($response->body());
			   //dd($apiResponse);
				// Check if API call was successful
				if ($response->successful() && isset($apiResponse->error) && $apiResponse->error == false) {
					  return response()->json(['status'=>200,'message'=>$apiResponse->msg]);
				}

				// Handle API errors
				return response()->json(['status'=>400,'message'=>$apiResponse->msg]);
			} catch (\Exception $e) {
				// Log exception
				Log::error('PayIn API Error:', ['error' => $e->getMessage()]);
				// Return server error response
				return response()->json(['status' => 400, 'message' => 'Internal Server Error','error' => $e->getMessage()], 400);
			}
	}
	
	public function jilli_get_bet_history(Request $request){
		 $validator = Validator::make($request->all(), [
								'user_id' => 'required|exists:users,id'
							]);
				$validator->stopOnFirstFailure();
				if ($validator->fails()) {
					return response()->json(['status' => 400, 'message' => $validator->errors()->first()], 200);
				} 
		
				$user_id = $request->user_id;
		        $apiUrl = 'https://api.gamebridge.co.in/seller/v1/jilli_get_bet_history';
		        $account_token = DB::table('users')->where('id',$user_id)->value('account_token');
		        $manager_key = 'FEGISo8cR74cf';
		$authorizationtoken='1740116434200';
		        $headers = [
							'authorization' => 'Bearer ' .$manager_key,
							'validateuser' => 'Bearer '.$account_token,
					'authorizationtoken' => 'Bearer '.$authorizationtoken
						];
		       $payloadpar = ['payload'=>''];  
		
		try {
				$response = Http::withHeaders($headers)->post($apiUrl, $payloadpar);
				$apiResponse = json_decode($response->body());
			//dd($apiResponse);
			
				// Check if API call was successful
			   //if ($response->successful() && isset($apiResponse->error) && $apiResponse->error == false) {
				if ($response->successful()) {
					  return response()->json([
						  'status' => 200,
						  'message' => 'Jilli bet history..',
						   'data' =>$apiResponse->data,
					  ], 200); 
				}

				// Handle API errors
				return response()->json(['status' => 400,'message' => 'Jili bet history.', 'api_response' => $response->body()], 400);
			} catch (\Exception $e) {
				// Log exception
				Log::error('PayIn API Error:', ['error' => $e->getMessage()]);
				// Return server error response
				return response()->json(['status' => 400, 'message' => 'Internal Server Error','error' => $e->getMessage()], 400);
			}
	}
	
	
	public function add_in_jilli_wallet(Request $request){
		 $validator = Validator::make($request->all(), [
								'user_id' => 'required|exists:users,id',
		                    	'amount'=>'required|numeric|gt:0'
							]);
				$validator->stopOnFirstFailure();
				if ($validator->fails()) {
					return response()->json(['status' => 400, 'message' => $validator->errors()->first()], 200);
				} 
		
		$user_id = $request->user_id;
		$amount = $request->amount;
		 $account_token = DB::table('users')->where('id',$user_id)->value('accountNo');
		$apiUrl = 'https://api.gamebridge.co.in/seller/v1/add-newjilliuser-wallet-by-id';
		$manager_key = 'FEGISo8cR74cf';
		$authorizationtoken='1740116434200';
	    $headers = [
				'authorization' => 'Bearer ' .$manager_key,
				'validateuser' => 'Bearer '.$account_token,
			'authorizationtoken' => 'Bearer '.$authorizationtoken
			];
		$pay_load = ['amount'=>$amount,'mobile'=>$account_token];
		$pay_load = json_encode($pay_load);
		$pay_load = base64_encode($pay_load);
		$payloadpar = ['payload'=>$pay_load];
		
		try {
				$response = Http::withHeaders($headers)->post($apiUrl, $payloadpar);
				$apiResponse = json_decode($response->body());
			   //dd($apiResponse);
				// Check if API call was successful
				if ($response->successful() && isset($apiResponse->error) && $apiResponse->error == false) {
					return response()->json(['status'=>200,'message'=>$apiResponse->msg]);
				}
				// Handle API errors
				return response()->json(['status'=>400,'message'=>$apiResponse->msg]);
			} catch (\Exception $e) {
				// Log exception
				Log::error('PayIn API Error:', ['error' => $e->getMessage()]);
				// Return server error response
				return response()->json(['status'=>400,'message'=>$e->getMessage()]);
			}
	}
	
	public function get_jilli_wallet(Request $request){
		 $validator = Validator::make($request->all(), [
								'user_id' => 'required|exists:users,id'
							]);
				$validator->stopOnFirstFailure();
				if ($validator->fails()) {
					return response()->json(['status' => 400, 'message' => $validator->errors()->first()], 200);
				} 
		
		$user_id = $request->user_id;
		$amount = $request->amount;
		 $account_token = DB::table('users')->where('id',$user_id)->value('accountNo');
		$apiUrl = 'https://api.gamebridge.co.in/seller/v1/get-newjilliuser-wallet-by-id';
		$manager_key = 'FEGISo8cR74cf';
		$authorizationtoken='1740116434200';
	    $headers = [
				'authorization' => 'Bearer ' .$manager_key,
				'validateuser' => 'Bearer '.$account_token,
			'authorizationtoken' => 'Bearer '.$authorizationtoken
			];
		$pay_load = ['mobile'=>$account_token];
		$pay_load = json_encode($pay_load);
		$pay_load = base64_encode($pay_load);
		$payloadpar = ['payload'=>$pay_load];
		
		try {
				$response = Http::withHeaders($headers)->post($apiUrl, $payloadpar);
				$apiResponse = json_decode($response->body());
			//dd($apiResponse);
			    $data = $apiResponse->data;

// Ensure $winning_wallet is defined properly
$winning_wallet = $data; // Assuming $data contains the required values

// Assign the values to $wallet and $winning_wallet
$wallet = $data[0]->njl_money;
$winning_wallet_value = $data[0]->njl_winning;

// Combine them into an array
$combined = [
    'wallet' => $wallet,
    'winning_wallet' => $winning_wallet_value,
];


			     //dd($data);
			     
			   //dd($apiResponse->data);
				// Check if API call was successful
				if ($response->successful() && isset($apiResponse->error) && $apiResponse->error == false) {
					return response()->json(['status'=>200,'message'=>$apiResponse->msg,'data'=>$combined]);
				}
				// Handle API errors
				return response()->json(['status'=>400,'message'=>$apiResponse->msg]);
			} catch (\Exception $e) {
				// Log exception
				Log::error('PayIn API Error:', ['error' => $e->getMessage()]);
				// Return server error response
				return response()->json(['status'=>400,'message'=>$e->getMessage()]);
			}
	}
	
	
	
	public function update_main_wallet(Request $request)
	{
	 $validator = Validator::make($request->all(), [
								'user_id' => 'required|exists:users,id',
							]);
				$validator->stopOnFirstFailure();
				if ($validator->fails()) {
					return response()->json(['status' => 400, 'message' => $validator->errors()->first()], 200);
				} 
		
		$user_id = $request->user_id;
		
		$token=User::where('id',$user_id)->first();
		$user_token=$token->account_token;
		$apiUrl = 'https://api.gamebridge.co.in/seller/v1/get-user-info';
		$manager_key = 'FEGISo8cR74cf';
		$authorizationtoken='1740116434200';
	    $headers = [
				'authorization' => 'Bearer ' .$manager_key,
				'validateuser' => 'Bearer '.$user_token,
				'authorizationtoken' => 'Bearer '.$authorizationtoken
			];
		$payloadpar = ['payload'=>''];
		
		try {
				$response = Http::withHeaders($headers)->post($apiUrl, $payloadpar);
				$apiResponse = json_decode($response->body());
			$money=$apiResponse->money;
			//dd($money);
				// Check if API call was successful
				if ($response->successful() && isset($apiResponse->error) && $apiResponse->error == false) {
					$update=User::where('id', $user_id)->update(['wallet' => $money]);
					return response()->json(['status'=>200,'message'=>$apiResponse->msg,'Updated_money'=>$money]);
				}
				// Handle API errors
				return response()->json(['status'=>400,'message'=>$apiResponse->msg]);
			} catch (\Exception $e) {
				// Log exception
				Log::error('PayIn API Error:', ['error' => $e->getMessage()]);
				// Return server error response
				return response()->json(['status'=>400,'message'=>$e->getMessage()]);
			}
		
	}
	
	
	public function all_game_list_test(Request $request){
			
			$apiUrl = 'https://api.gamebridge.co.in/seller/v1/get-all-games-list';
			$token = 'FEGISo8cR74cf';
			
			$headers = [
				'authorization' => 'Bearer ' .$token
			];
			$payload = ['payload'=>''];
			
			try {
				// Make API request with headers and JSON body
				$response = Http::withHeaders($headers)->post($apiUrl, $payload);
				//dd($response);
				// Parse API response
				$apiResponse = json_decode($response->body());
				//dd($apiResponse);
                
				// Check if API call was successful
				if ($response->successful() && isset($apiResponse->error) && $apiResponse->error == false) {
					  return response()->json([
						  'status' => 200,
						  'message' => 'Game list..',
						   'data' =>$apiResponse->data,
						  'fish' =>$apiResponse->fish,
						  'slot' =>$apiResponse->slot,
						  'tableandcard' =>$apiResponse->tableandcard,
						  'crash' =>$apiResponse->crash
					  ], 200); 
				}

				// Handle API errors
				return response()->json(['status' => 400,'message' => 'Failed to get game list.', 'api_response' => $response->body()], 400);
			} catch (\Exception $e) {
				// Log exception
				Log::error('PayIn API Error:', ['error' => $e->getMessage()]);
				// Return server error response
				return response()->json(['status' => 400, 'message' => 'Internal Server Error','error' => $e->getMessage()], 400);
			}
		}
	
	public function update_jilli_wallet(Request $request){
		 $validator = Validator::make($request->all(), [
								'user_id' => 'required|exists:users,id',
		                    	//'amount'=>'required|numeric|gt:0'
							]);
				$validator->stopOnFirstFailure();
				if ($validator->fails()) {
					return response()->json(['status' => 400, 'message' => $validator->errors()->first()], 200);
				} 
		
		$user_id = $request->user_id;
		//$amount = $request->amount;
		$account_token = DB::table('users')
    ->where('id', $user_id)
    ->select('accountNo', 'winning_wallet')
    ->first();
		$userId=$account_token->accountNo;
		$wallet=$account_token->winning_wallet;

		//dd($userId,$wallet);
		$apiUrl = 'https://api.gamebridge.co.in/seller/v1/assign-same-new-jilli-wallet';
		$manager_key = 'FEGISo8cR74cf';
		$authorizationtoken='1740116434200';
	    $headers = [
				'authorization' => 'Bearer ' .$manager_key,
				'validateuser' => 'Bearer '.$userId,
				'authorizationtoken' => 'Bearer '.$authorizationtoken
			];
		$pay_load = ['amount'=>$wallet,'mobile'=>$userId];
		$pay_load = json_encode($pay_load);
		$pay_load = base64_encode($pay_load);
		$payloadpar = ['payload'=>$pay_load];
		
		try {
				$response = Http::withHeaders($headers)->post($apiUrl, $payloadpar);
				$apiResponse = json_decode($response->body());
			   //dd($apiResponse);
				// Check if API call was successful
				if ($response->successful() && isset($apiResponse->error) && $apiResponse->error == false) {
					return response()->json(['status'=>200,'message'=>$apiResponse->msg]);
				}
				// Handle API errors
				return response()->json(['status'=>400,'message'=>$apiResponse->msg]);
			} catch (\Exception $e) {
				// Log exception
				Log::error('PayIn API Error:', ['error' => $e->getMessage()]);
				// Return server error response
				return response()->json(['status'=>400,'message'=>$e->getMessage()]);
			}
	}
	
	public function update_jilli_to_user_wallet(Request $request){
		 $validator = Validator::make($request->all(), [
								'user_id' => 'required|exists:users,id'
							]);
				$validator->stopOnFirstFailure();
				if ($validator->fails()) {
					return response()->json(['status' => 400, 'message' => $validator->errors()->first()], 200);
				} 
		
		$user_id = $request->user_id;
		//$amount = $request->amount;
		 $account_token = DB::table('users')->where('id',$user_id)->value('accountNo');
		$apiUrl = 'https://api.gamebridge.co.in/seller/v1/get-newjilliuser-wallet-by-id';
		$manager_key = 'FEGISo8cR74cf';
		$authorizationtoken='1740116434200';
	    $headers = [
				'authorization' => 'Bearer ' .$manager_key,
				'validateuser' => 'Bearer '.$account_token,
			'authorizationtoken' => 'Bearer '.$authorizationtoken
			];
		$pay_load = ['mobile'=>$account_token];
		$pay_load = json_encode($pay_load);
		$pay_load = base64_encode($pay_load);
		$payloadpar = ['payload'=>$pay_load];
		
		try {
				$response = Http::withHeaders($headers)->post($apiUrl, $payloadpar);
				$apiResponse = json_decode($response->body());
			//dd($apiResponse);
			    $data = $apiResponse->data;

// Ensure $winning_wallet is defined properly
$winning_wallet = $data; // Assuming $data contains the required values

// Assign the values to $wallet and $winning_wallet
$wallet = $data[0]->njl_money;
$winning_wallet_value = $data[0]->njl_winning;

// Combine them into an array
//$combined = [
//    'wallet' => $wallet,
//    'winning_wallet' => $winning_wallet_value,
//	];
			DB::table('users')
    ->where('id', $user_id) // Find the user by ID
    ->update(['winning_wallet' => $winning_wallet_value]); // Update the wallet field



			     //dd($data);
			     
			   //dd($apiResponse->data);
				// Check if API call was successful
				if ($response->successful() && isset($apiResponse->error) && $apiResponse->error == false) {
					return response()->json(['status'=>200,'message'=>$apiResponse->msg,'data'=>$combined]);
				}
				// Handle API errors
				return response()->json(['status'=>400,'message'=>$apiResponse->msg]);
			} catch (\Exception $e) {
				// Log exception
				Log::error('PayIn API Error:', ['error' => $e->getMessage()]);
				// Return server error response
				return response()->json(['status'=>400,'message'=>$e->getMessage()]);
			}
	}
	
	
	
	
}







