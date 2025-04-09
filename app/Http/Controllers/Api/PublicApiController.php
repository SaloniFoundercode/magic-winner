<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str; 
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\URL;
use Carbon\Carbon;
use DateTime;
use DateTimeZone;
use App\Models\{withdraw,GiftCard,GiftClaim,Version,CustomerService,WalletHistory,Payin,BankDetail,Slider,User};
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Helper\jilli;
class PublicApiController extends Controller
{
    protected function generateRandomUID() {
		$alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$digits = '0123456789';
		$uid = '';
		for ($i = 0; $i < 4; $i++) {
			$uid .= $alphabet[rand(0, strlen($alphabet) - 1)];
		}
		for ($i = 0; $i < 4; $i++) {
			$uid .= $digits[rand(0, strlen($digits) - 1)];
		}
		return $this->check_exist_memid($uid);
	}
	protected function check_exist_memid($uid){
		$check = DB::table('users')->where('u_id',$uid)->first();
		if($check){
			return $this->generateRandomUID(); 
		} else {
			return $uid;
		}
	}
    private function getUserByCredentials($identity, $password) {
        $user = User::where(function ($query) use ($identity) {
                $query->where('email', $identity)
                ->orWhere('mobile', $identity);
        })
        ->where('password', $password)
        ->where('status', 1)
        ->first();
    
        return $user;
    }
    public function registers(Request $request)
    {
        date_default_timezone_set('Asia/Kolkata');
    	$validator = Validator::make($request->all(), [
		'mobile' => 'required|regex:/^\d{10}$/|unique:users,mobile',
		'password' => 'required|string|min:6',
		'referral_code' => 'nullable|string|exists:users,referral_code'
    ]);

	
    	$validator->stopOnFirstFailure();
    	
        if($validator->fails()){
		
		$response = [
            'status' => 400,
            'message' => $validator->errors()->first()
            ]; 
		
		return response()->json($response,400);
    }
     $referral_code = $request->input('referral_code');
     if (!empty($referral_code)) {
            $refer = DB::table('users')->where('referral_code', $referral_code)->first();
        if ($refer) {
            $referral_user_id=$refer->id;
            $wallet = 0; 
        } else {
            return response()->json([
                'success' => "400",
                'message' => 'Invalid referral code',
            ], 400);
        }
    }

    $username = Str::random(6, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ');
	$u_id = $this->generateRandomUID();
	     
	$referral_code = Str::upper(Str::random(6, 'alpha'));
	     
    	$rrand = rand(1,20);
        $all_image = DB::table('all_images')->find($rrand);
        $uniqueId = substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789'), 0, 16);
        $uid = $this->generateSecureRandomString(6);
        $image = $all_image->image;
        $userId = DB::table('users')->insertGetId([
            'mobile' => $request->mobile,
            'email' => $request->email,
            'name' => $username,
            'password' =>$request->password,
            'referral_code' =>isset($referral_user_id)? $referral_user_id : '1',
            'referral_code' => $referral_code,
    		'u_id' => $u_id,
    		'status' => 1,
    		'spribe_id' => $uniqueId,
    		'image' => $image
        ]);
       $refid= isset($referral_user_id)? $referral_user_id : '1';
         DB::select("UPDATE `users` SET `yesterday_register`=yesterday_register+1 WHERE `id`=$refid");
    	
    	if($userId){
           $user = DB::table('users')->where('id', $userId)->first();
        $response = [
            'status' =>200,
            'message' => 'User is created successfully.',
    		'id' => $user->id,
    		'mobile'=>$user->mobile
    		
        ];
    
        return response()->json($response);
    	}else{
    		
    		 $response = [
            'status' =>400,
            'message' => 'Failed to register!',
        ];
        return response()->json($response,400);
    		
    	}
    }
	public function image_all()
	{
      
         $user = DB::select("SELECT `image`, `id` FROM `all_images`");
          if($user){
          $response =[ 'success'=>"200",'data'=>$user,'message'=>'Successfully'];return response ()->json ($response,200);
      }
      else{
       $response =[ 'success'=>"400",'data'=>[],'message'=>'Not Found Data'];return response ()->json ($response,400); 
      } 
    }
	public function commission_details(Request $request)
    {
    $validator = Validator::make($request->all(), [
        'userid' => 'required|integer',
        'typeid' => 'required|integer',
        'date' => 'required|date'
    ]);

    $validator->stopOnFirstFailure();

    if ($validator->fails()) {
        $response = [
            'status' => 400,
            'message' => $validator->errors()->first()
        ];
        return response()->json($response, 400);
    }
    $userid = $request->userid; 
    $typeid = $request->typeid; 
    $date = $request->date;
    $commission = DB::select("SELECT * FROM `wallet_histories` WHERE `user_id` = ? AND `type_id` = ? AND `created_at` LIKE ?", [$userid, $typeid, "%$date%"]);
    $data = [];
        foreach ($commission as $item) {
            $data[] = [
                'number_of_bettors' => $item->description_2 ?? '',
                'bet_amount' => $item->description ?? '',
                'commission_payout' => $item->amount ?? 0,
                'date' => $item->created_at ?? '',
                'settlement_date' => $item->updated_at ?? ''
            ];
        }
    
        if (!empty($data)) {
            $response = [
                'message' => 'commission_details',
                'status' => 200,
                'data' => $data,
            ];
            return response()->json($response);
        } else {
            return response()->json([
                'message' => 'Not found..! ',
                'status' => 400,
                'data' => []
            ], 400);
        }
    }
	public function payin_usdt(Request $request)
	{
				$validator = Validator::make($request->all(), [
					'user_id' => 'required|exists:users,id',
					'amount' => 'required|numeric|gt:0',
					'type' => 'required|in:1',
				]);

				$validator->stopOnFirstFailure();

				if ($validator->fails()) {
					return response()->json(['status' => 400, 'message' => $validator->errors()->first()], 200);
				}

				$user_id = $request->user_id;
				$amount = $request->amount;
				$type = $request->type;
					$inr_amt=$amount * 94;
					
                $email = 'techjupiter3133@gmail.com'; 
				$token = '57682082025451629461939305377137'; // Replace with a secure token or config value
				$apiUrl = "https://cryptofit.biz/Payment/coinpayments_api_call";
				$coin = 'USDT.BEP20';
					
				do {
					$orderId = str_pad(mt_rand(1000000000, 9999999999), 10, '0', STR_PAD_LEFT);
				} while (DB::table('payins')->where('order_id', $orderId)->exists());
					
				// Check if user exists and email matches
				$user_exist = DB::table('users')->where('id', $user_id)->first();

				// Prepare data for PayIn API request
				$formData = [
					'txtamount' => $amount,
					'coin' => $coin,
					'UserID' => $email,
					'Token' => $token,
					'TransactionID' => $orderId,
				];

				try {
					// Make API request
					$response = Http::asForm()->post($apiUrl, $formData);

					// Log response
					Log::info('PayIn API Response:', ['response' => $response->body()]);
					Log::info('PayIn API Status Code:', ['status' => $response->status()]);

					// Parse API response
					$apiResponse = json_decode($response->body());

					// Check if API call was successful
					if ($response->successful() && isset($apiResponse->error) && $apiResponse->error === 'ok') {
						// Deduct amount from wallet
						
						// Insert payin record
						$inserted_id = DB::table('payins')->insertGetId([
							'user_id' => $user_id,
							'status' => 1,
							'order_id' => $orderId,
							'cash' => $inr_amt,
							'usdt_amount'=>$amount,
							'type' => $type,
						]);
						
						return response()->json([
							'status' => 200,
							'message' => 'Payment initiated successfully.',
							'data' => $apiResponse,
						], 200);
					}

					// Handle API errors
					return response()->json([
						'status' => 400,
						'message' => 'Failed to initiate payment.',
						'api_response' => $response->body(),
					], 400);
				} catch (\Exception $e) {
					// Log exception
					Log::error('PayIn API Error:', ['error' => $e->getMessage()]);
					// Return server error response
					return response()->json(['status' => 400, 'message' => 'Internal Server Error'], 400);
				}
			}
	public function payin_call_back(Request $request)
	{
		
		$validator = Validator::make($request->all(), [
					'invoice' => 'required',
					'status_text' => 'required',
					'amount' => 'required'
				]);

				$validator->stopOnFirstFailure();

				if ($validator->fails()) {
					return response()->json(['status' => 400, 'message' => $validator->errors()->first()], 200);
				}
		
		$invoice = $request->invoice;
		$status_text = $request->status_text;
		$amount = $request->amount;
		if($status_text=='complete'){
			
          $a =  DB::table('payins')->where('order_id',$invoice)->update(['status'=>2]);
			
			if($a){
				$user_detail = Payin::where('order_id', $invoice)
                            ->where('status', 2)
                            ->first();
				$user_id=$user_detail->user_id;
				$amount1=$user_detail->cash;
				//$update_wallet = jilli::update_user_wallet($user_id);
				$update=User::where('id', $user_id)->update(['wallet' => $amount1]);
				$add_jili = jilli::add_in_jilli_wallet($user_id,$amount1);
				return response()->json(['status'=>200,'message'=>'Payment successful.'],200);
			}else{
			   return response()->json(['status'=>400,'message'=>'Failed to update!'],400);
			}
		}else{
           return response()->json(['status'=>400,'message'=>'Something went wrong!'],400);
		}
	}
	public function otp_register(Request $request)
	{
    try {    
        $validator = Validator::make($request->all(), [
            'mobile' => ['required', 'string', 'regex:/^\d{10}$/'], // Ensure 10 digits
            'otp' => 'required',
        ]);
        $validator->stopOnFirstFailure();
        if ($validator->fails()) {
            $response = [
                'status' => 400,
                'message' => $validator->errors()->first()
            ]; 
            return response()->json($response, 400);
        }
        $mobile = $request->mobile; // Define $mobile from the request
        $username = Str::random(6); // Generate random username
        $u_id = $this->generateRandomUID();
        $referral_code = Str::upper(Str::random(6)); // Generate referral code
        $rrand = rand(1, 20);
        $all_image = All_image::find($rrand);     
        $image = $all_image->image;
        $exist_user = User::where('mobile', $mobile)->where('type', 1)->first();
        if (!empty($exist_user)) {
            // Update existing user with new OTP
            $exist_user->otp = $request->otp;
            $exist_user->save();
            return response()->json([
                'status' => 200,
                'message' => 'OTP updated successfully for existing user.',
                'userid' => $exist_user->id,
                'mobile' => $exist_user->mobile,
            ]);   
        } else {
            $userId = DB::table('users')->insertGetId([
                'mobile' => $mobile,
                'otp' => $request->otp,
                'name' => $username,
                'referral_code' => $referral_code,
                'u_id' => $u_id,
                'status' => 1,
                'type' => 1,
                'image' => $image,
                'created_at' => now()
            ]);
            if ($userId) {
                $user = DB::table('users')->where('id', $userId)->first();
                $response = [
                    'status' => 200,
                    'message' => 'User is created successfully.',
                    'userid' => $userId,
                    'mobile' => $user->mobile
                ];
                return response()->json($response);
            } else {
                $response = [
                    'status' => 400,
                    'message' => 'Failed to register!'
                ];
                return response()->json($response, 400);
            }
        }
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
}
    private function generateSecureRandomString($length = 8)
    {
        $characters = '0123456789'; // You can expand this to include more characters if needed.
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[random_int(0, strlen($characters) - 1)];
        }
        return $randomString;
    }
    public function check_existsnumber(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'mobile' => 'required|string|size:10',
        ]);
    
        if ($validator->fails()) {
            return response()->json([
                'status' => 400,
                'message' => $validator->errors()->first()
            ]);
        }
    
        $user = User::where('mobile', $request->mobile)->first();
    
        if ($user) {
            return response()->json([
                'status' => 400,
                'message' => "This mobile number is already registered. Please login ..!"
            ]);
        }
    
        return response()->json([
            'status' => 200,
            'message' => "This mobile number is not registered. Please register ..!"
        ]);
}
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'mobile' => 'required|digits:10',
            'password' => 'required'
        ]);
    
        if ($validator->fails()) {
            return response()->json([
                'status' => 400,
                'message' => $validator->errors()->first()
            ], 200);
        }
    
        $user = User::where('mobile', $request->mobile)->first();
        $passr = User::where('password', $request->password)->first();
    
        if (!$user) {
            return response()->json([
                'status' => 400,
                'message' => 'Invalid mobile number'
            ], 200);
        }
        if (!$passr) {
            return response()->json([
                'status' => 400,
                'message' => 'Invalid password'
            ], 200);
        }
        return response()->json([
            'status' => 200,
            'message' => 'Login successful',
            'id' => $user->id
        ], 200);
    }

    public function Profile($id)
    {
        $ldate = new DateTime('now', new DateTimeZone('Asia/Kolkata'));
        try {
            $user = User::find($id);
    
            if ($user) {
                return response()->json([
                    'success' => 200,
                    'message' => 'User found..!',
                    'data' => $user,
                    'aviator_link' => "https://aviatorudaan.com/",
                    'aviator_event_name' => "magicwinnerav",
                    'apk_link' => env('APP_URL') . "jupiter.apk",
                    'usdt_payin_amount' => 94,
                    'usdt_payout_amount' => 92,
                    'telegram' => "https://t.me/Help_jupiter",
                    'referral_code_url' => env('APP_URL') . "registerwithref/" . $user->referral_code,
                    'last_login_time' => $ldate->format('Y-m-d H:i:s'),
                ]);
            }
            return response()->json(['success' => 400, 'message' => 'User not found..!'], 200);
        } catch (Exception $e) {
            return response()->json(['error' => 'API request failed: ' . $e->getMessage()], 500);
        }
    }
	public function update_profile(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'userid' => 'required|exists:users,id',
            'image_id' => 'required|exists:all_images,id',
        ])->stopOnFirstFailure();
        
        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first()
            ]);
        }
        $userId = $request->input('userid');
        $imageId = $request->input('image_id');
        $image = DB::table('all_images')->where('id', $imageId)->first();
        if ($image) {
            DB::table('users')->where('id', $userId)->update([
                'image' => $image->image
            ]);
            return response()->json([
                'status' => 200,
                'message' => 'Image updated successfully.',
                'data' => $image
            ]);
        } else {
            return response()->json([
                'status' => 404,
                'message' => 'Image not found or invalid image ID.',
            ], 404);
        }
    }
	public function update_profile11(Request $request)
    {
    $request->validate([
        'id' => 'required',
        'name' => 'required|string',
        'image' => 'nullable|image|all:jpeg,png,jpg,gif|max:2048' 
    ]);
    $id = $request->id;
    $user = User::findOrFail($id);
    if ($user->status == 1) {
        $user->name = $request->name;
        if ($request->hasFile('image')) {
            // Delete old image if it exists
            if ($user->image) {
                $oldImagePath = public_path('uploads/profile_images/' . $user->image);
                if (file_exists($oldImagePath)) {
                    unlink($oldImagePath);
                }
            }
            $image = $request->file('image');
            $imageName = uniqid() . '.' . $image->getClientOriginalExtension();
            $image->move(public_path('uploads/profile_images'), $imageName);
            $user->image = $imageName;
        }
        $user->save();
        return response()->json([
            'status' => 200,
            'message' => "Profile updated successfully",
            'data' => [
                'name' => $user->name,
                'image' => $user->image ? url('uploads/profile_images/' . $user->image) : null
            ]
        ]);
        } else {
            return response()->json([
                'status' => 401,
                'message' => "User blocked by admin!"
            ]);
        }
    }
	public function update_profile_old(Request $request)
    {
    $request->validate([
        'id' => 'required',
        'name' => 'required|string',
        'mobile' => 'required|numeric'
    ]);
    $id = $request->id;
    $user = User::findOrFail($id);

    if ($user->status == 1) {
        $user->name = $request->name;
        $user->mobile = $request->mobile;
        $user->save();

        return response()->json([
            'status' => 200,
            'message' => "Successfully updated"
        ], 200);
        } else {
            return response()->json([
                'status' => 401,
                'message' => "User blocked by admin!"
            ], 401);
        }
    }
	public function main_wallet_transfer(Request $request)
    {
     $validator = Validator::make($request->all(), [
        'id' => 'required|exists:users,id'
    ]);

    if ($validator->fails()) {
        return response()->json([
            'status' => 400,
            'message' => $validator->errors()->first()
        ],200);
    }
    
    $id = $request->id;
    
    $user = User::findOrFail($id);
    $status = $user->status;
    $main_wallet = $user->wallet;
    $thirdpartywallet = $user->winning_wallet;
    $add_main_wallet = $main_wallet + $thirdpartywallet;
    
    if ($status == 1) {
        $user->wallet = $add_main_wallet;
        $user->winning_wallet = 0;
        $user->save();

        $response = [
            'status' => 200,
            'message' => "Wallet transfer Successfully ....!"
        ];

        return response()->json($response, 200);
        } else {
        $response = [
            'status' => 401,
            'message' => "User blocked by admin..!"
        ];
        return response()->json($response, 401);
    }
    }   
	public function winning_wallet_transfers(Request $request)
    {
     $validator = Validator::make($request->all(), [
        'id' => 'required|exists:users,id'
    ]);

    if ($validator->fails()) {
        return response()->json([
            'status' => 400,
            'message' => $validator->errors()->first()
        ],200);
    }
    
    $id = $request->id;
    
    $user = User::findOrFail($id);
    $status = $user->status;
    $main_wallet = $user->wallet;
    $thirdpartywallet = $user->winning_wallet;
    $add_main_wallet = $main_wallet + $thirdpartywallet;
    
    if ($status == 1) { 
        $user->winning_wallet = $add_main_wallet;
        $user->wallet = 0;
        $user->save();

        $response = [
            'status' => 200,
            'message' => "Winning Wallet transfer Successfully ....!"
        ];

        return response()->json($response, 200);
        } else {
            $response = [
                'status' => 401,
                'message' => "User blocked by admin..!"
            ];
            return response()->json($response, 401);
        }
    }
    public function slider_image_view()
    {
    $slider = Slider::all();

    if ($slider->isNotEmpty()) {
        return response()->json(['success' => 200, 'message' => 'Sliders found..!', 'data' => $slider]);
    }
    return response()->json(['success' => 400, 'message' => 'Sliders not found..!'],200);
    }
    public function changepassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'userid' => 'required|exists:users,id',
            'password' => 'required|string|min:8',
            'newpassword' => 'required|string|min:8',
            'confirm_newpassword' => 'required|string|same:newpassword',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => "400",
                'message' => $validator->errors()->first()
            ], 200);
        }
        $user = User::find($request->userid);
        if ($user->password !== $request->password) {
            return response()->json([
                'status' => "400",
                'message' => 'Current password is incorrect'
            ], 200);
        }
        $user->password = $request->newpassword;
        $user->save();
    
        return response()->json([
            'status' => "200",
            'message' => 'Password updated successfully'
        ], 200);
    }
    public function resetPassword(Request $request)
    {
        // Validate the request inputs
        $validator = Validator::make($request->all(), [
            'mobile' => 'required|string|size:10',
            'password' => 'required|string|min:8',
            'confirm_password' => 'required|string|same:password',
        ]);
    
        // Check if validation fails
        if ($validator->fails()) {
            return response()->json([
                'status' => "400",
                'message' => $validator->errors()->first()
            ], 200);
        }
    
        // Find the user by mobile number
        $user = User::where('mobile', $request->mobile)->first();
    
        // If user is not found
        if (!$user) {
            return response()->json([
                'status' => "400",
                'message' => 'Invalid mobile number'
            ], 200);
        }
    
        // Update the user's password (plain text, no hashing)
        $user->password = $request->password;
        $user->save();
    
        // Return success response
        return response()->json([
            'status' => "200",
            'message' => 'Password updated successfully'
        ], 200);
    }
    public function addAccount(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'userid' => 'required',
            'name' => 'required',
            'account_number' => 'required',
            'bank_name' => 'required',
            'ifsc_code' => 'required',
            'email' => 'required',
            'mobile' => 'required',
        ]);
    
        if ($validator->fails()) {
            return response()->json([
                'status' => "400",
                'message' => $validator->errors()->first(),
            ],200);
        }
    
        $userid = $request->input('userid');
        $name = $request->input('name');
        $account_number = $request->input('account_number');
        $bank_name = $request->input('bank_name');
        $ifsc_code = $request->input('ifsc_code');
        $email = $request->input('email');
        $mobile = $request->input('mobile');
    // dd($email);
        $datetime = Carbon::now();
        // Create a new account
        $account = BankDetail::create([
            'userid' => $userid,
            'name' => $name,
            'account_num' => $account_number,
            'bank_name' => $bank_name,
            'ifsc_code' => $ifsc_code,
            'email' => $email,
            'mobile' => $mobile,
            'status' => 1,
            'created_at' => $datetime,
            'updated_at' => $datetime,
        ]);
    
        if ($account) {
            return response()->json([
                'id' => $account->id,
                'status' => "200",
                'message' => 'Account Added Successfully.',
            ]);
        } else {
            return response()->json([
                'status' => "400",
                'message' => 'Account Not Added',
            ],200);
        }
}
    public function accountView(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'userid' => 'required'
        ]);
    
        if ($validator->fails()) {
            return response()->json([
                'status' => 400,
                'message' => $validator->errors()->first()
            ],200);
        }
         $userid=$request->userid;
       
        $result = BankDetail::where('userid', $userid)->get();
    
        if ($result->isNotEmpty()) {  
            return response()->json([
                'status' => "200",
                'message' => 'Success',
                'data' => $result,
            ], 200);
        } else {
            return response()->json([
                'status' => "400",
                'message' => 'No data found.',
            ], 200);
        }
}
    public function accountDelete($id)
    {
        $bankDetail = BankDetail::find($id);
    
        if (!$bankDetail) {
            return response()->json(['status' => "404", 'message' => 'Account not found'], 200);
        }
    
        $bankDetail->delete();
        return response()->json(['status' => "200", 'message' => 'Account deleted successfully'], 200);
    }
    public function payin(Request $request)
    {
        // Validate request data
        $validator = Validator::make($request->all(), [
            'userid' => 'required|exists:users,id',
            'amount' => 'required|numeric',
            'type' => 'required|in:0,1' // Assuming 'type' can only be '0' or '1'
        ]);
    
        $validator->stopOnFirstFailure();
    
        // Handle validation failure
        if ($validator->fails()) {
            return response()->json([
                'status' => "400",
                'message' => $validator->errors()->first()
            ], 200);
        }
    
        // Assign request data to variables
        $cash = $request->amount;
        $userid = $request->userid;
        $type = $request->type;
        
        // Generate order id
        $dateTime = new \DateTime('now', new \DateTimeZone('Asia/Kolkata'));
    	//dd($dateTime);
        $formattedDateTime = $dateTime->format('YmdHis');
        $rand = rand(11111, 99999);
        $orderid = $formattedDateTime . $rand;
        $datetime = now();
    
        // Check if the user exists
        $user = User::find($userid);
    
        if ($user) {
            if ($cash >= 100) {
            if ($type == '0') {
                $baseUrl = URL::to('/');
                $redirect_url = $baseUrl . "/api/checkPayment?order_id=$orderid";
    
                // Insert payin record using Eloquent
                $payin = new Payin();
                $payin->user_id = $user->id;
                $payin->cash = $cash;
                $payin->type = $type;
                $payin->order_id = $orderid;
                $payin->redirect_url = $redirect_url;
                $payin->status = 1;
                $payin->created_at = $dateTime;
                $payin->updated_at = $dateTime;
                
                if (!$payin->save()) {
                    return response()->json(['status' => "400", 'message' => 'Failed to store record in payin history!'], 200);
                }
    
                // Prepare data for the external API call
                $postParameter = [
                    "merchantid" => "INDIANPAY00INDIANPAY0096",
                    "orderid" => $orderid,
                    "amount" => $cash,
                    "name" => $user->name,
                    "email" => "test@gmail.com", // Update with the real email if needed
                    "mobile" => $user->mobile,
                    "remark" => "payIn",
                    "type" => "2",
                    "redirect_url" => $redirect_url
                ];
    
                // External API call using cURL
                $curl = curl_init();
                curl_setopt_array($curl, [
                    CURLOPT_URL => 'https://indianpay.co.in/admin/paynow',
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => '',
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 0,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST => 'POST',
                    CURLOPT_POSTFIELDS => json_encode($postParameter),
                    CURLOPT_HTTPHEADER => [
                        'Content-Type: application/json',
                        'Cookie: ci_session=oo35jvjuvh3ukuk9t7biecukphiiu8vl'
                    ],
                ]);
    
                $response = curl_exec($curl);
                curl_close($curl);
                echo $response;
            } else {
                // Handle case where type is not '0'
                return response()->json([
                    'status' => "400",
                    'message' => 'Invalid type value!'
                ], 200);
            }
            } else {
                // Handle case where type is not '0'
                return response()->json([
                    'status' => "400",
                    'message' => 'Minimum Deposit amount is 100 rs!'
                ], 200);
            }
        } else {
            return response()->json([
                'status' => "400",
                'message' => 'Internal error! User not found.'
            ], 400);
        }
}
    public function checkPayment(Request $request)
    {
    $validator = Validator::make($request->all(), [
        'order_id' => 'required|exists:payins,order_id', 
    ]);

    $validator->stopOnFirstFailure();

    if ($validator->fails()) {
        return response()->json([
            'status' => 400,
            'message' => $validator->errors()->first(),
        ], 200);
    }
    $orderid = $request->order_id;
    $currentDateTime = Carbon::now()->format('Y-m-d H:i:s');
    $payment = Payin::where('order_id', $orderid)
                    ->where('status', 1)
                    ->first();

    if (!$payment) {
        return response()->json([
            'status' => 404,
            'message' => 'Payment not found or already processed.',
        ], 200);
    }

    $userid = $payment->user_id; 
    $amount = $payment->cash;
    $user = User::where('id', $userid)
                ->where('status', 1) 
                ->first();

    if (!$user) {
        return response()->json([
            'status' => 404,
            'message' => 'User not found or inactive.',
        ], 200);
    }

    $referral_user_id = $user->referrer_id;
    $userPercentage = ($amount * 10) / 100;
    $secondPercentage = ($amount * 5) / 100;
    $final_amt = $amount + $secondPercentage;
    if ($user->first_recharge == '1') {
        DB::table('users')
            ->where('id', $userid)
            ->update([
                'wallet' => DB::raw("wallet + $amount + $userPercentage"),
                'recharge' => DB::raw("recharge + $amount + $userPercentage"),
                'first_recharge' => 0,
            ]);
        if ($referral_user_id) {
            DB::table('users')
                ->where('id', $referral_user_id)
                ->update([
                    'wallet' => DB::raw("wallet + $userPercentage"),
                    'recharge' => DB::raw("recharge + $userPercentage"),
                ]);
            DB::table('wallet_histories')->insert([
                'user_id' => $referral_user_id,
                'amount' => $userPercentage,
                'type_id' => 6,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        } else {
        DB::table('users')
            ->where('id', $userid)
            ->update([
                'wallet' => DB::raw("wallet + $final_amt"),
                'recharge' => DB::raw("recharge + $final_amt"),
            ]);

        if ($referral_user_id) {
            DB::table('users')
                ->where('id', $referral_user_id)
                ->update([
                    'wallet' => DB::raw("wallet + $secondPercentage"),
                    'recharge' => DB::raw("recharge + $secondPercentage"),
                ]);

            // Add wallet history for the referral user
            DB::table('wallet_histories')->insert([
                'user_id' => $referral_user_id,
                'amount' => $secondPercentage,
                'type_id' => 6,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
    $payment->status = 2;
    $payment->save();
        return redirect()->route('payin.successfully');
    }

    private function addToZiliWallet($user_id, $amount, $account_token)
    {
        $apiUrl = 'https://api.gamebridge.co.in/seller/v1/add-newjilliuser-wallet-by-id';
        $manager_key = 'FEGISo8cR74cf';
        $headers = [
            'authorization' => 'Bearer ' . $manager_key,
            'validateuser' => 'Bearer ' . $account_token,
        ];
        $pay_load = ['amount' => $amount, 'mobile' => $account_token];
        $pay_load = json_encode($pay_load);
        $pay_load = base64_encode($pay_load);
        $payloadpar = ['payload' => $pay_load];
    
        try {
            $response = Http::withHeaders($headers)->post($apiUrl, $payloadpar);
            $apiResponse = json_decode($response->body());
    
            if ($response->successful() && isset($apiResponse->error) && $apiResponse->error == false) {
                return ['status' => 200, 'msg' => $apiResponse->msg];
            }
    
            return ['status' => 400, 'msg' => $apiResponse->msg];
        } catch (\Exception $e) {
            Log::error('PayIn API Error:', ['error' => $e->getMessage()]);
            return ['status' => 400, 'msg' => $e->getMessage()];
        }
}
    private function addToSpribeWallet($user_id, $amount, $spribe_id)
    {
        $apiUrl = 'https://spribe.gamebridge.co.in/seller/v1/add-spribe-user-balance';
        $manager_key = 'FEGISo8cR74cf';
        $headers = [
            'authorization' => 'Bearer ' . $manager_key,
            'validateuser' => 'Bearer ' . $spribe_id,
        ];
        $pay_load = ['amount' => $amount, 'userId' => $spribe_id];
        $pay_load = json_encode($pay_load);
        $pay_load = base64_encode($pay_load);
        $payloadpar = ['payload' => $pay_load];
    
        try {
            $response = Http::withHeaders($headers)->post($apiUrl, $payloadpar);
            $apiResponse = json_decode($response->body());
    
            if ($response->successful() && isset($apiResponse->error) && $apiResponse->error == false) {
                return ['status' => 200, 'msg' => $apiResponse->msg];
            }
    
            return ['status' => 400, 'msg' => $apiResponse->msg];
        } catch (\Exception $e) {
            Log::error('Spribe API Error:', ['error' => $e->getMessage()]);
            return ['status' => 400, 'msg' => $e->getMessage()];
        }
}
	public function redirect_success(){
		 return view ('success');	
	 }
    public function wallet_transfer(Request $request)
    {
    	 $validator = Validator::make($request->all(), [
            'id' => 'required'
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => "400",
                'message' => $validator->errors()->first(),
            ],200);
        }
       $id=$request->id;
        $user = User::findOrFail($id);
        if ($user->status != 1) {
            return response()->json([
                'status' => 401,
                'message' => "User blocked by admin."
            ], 401);
        }
    
        $add_main_wallet = $user->wallet + $user->third_party_wallet;
            $user->update([
                'wallet' => $add_main_wallet,
                'third_party_wallet' => 0,
            ]);
            return response()->json([
                'status' => 200,
                'message' => "Wallet transfer successfully completed!"
            ], 200);
       
}
    public function withdraw_old(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required',
            'account_id' => 'required',
            'type' => 'required',
            'amount' => 'required|numeric',
        ]);
    
        $validator->stopOnFirstFailure();
    	
        if($validator->fails()){
             $response = [
                            'status' => 400,
                          'message' => $validator->errors()->first()
                          ]; 
    		
    		return response()->json($response,400);
    		
        }
    
        $userid = $request->input('user_id');
        $accountid = $request->input('account_id');
        $amount = $request->input('amount');
        $type = $request->input('type');
        
         $date = date('YmdHis');
            $rand = rand(11111, 99999);
            $orderid = $date . $rand;
        if ($amount >= 200 && $amount<=25000) {
          //($amount >= 550) 
    		dd("hii");
            $wallet=DB::select("SELECT ⁠ wallet ⁠,⁠ first_recharge ⁠,⁠ winning_wallet ⁠ FROM ⁠ users ⁠ WHERE id=$userid");
          $user_wallet=$wallet[0]->wallet;
          $first_recharge=$wallet[0]->first_recharge;
          if($user_recharge == 0){
              if($first_recharge == 1){
            if($user_wallet >= $amount){
          $data= DB::table('withdraws')->insert([
        'user_id' => $userid,
        'amount' => $amount,
        'account_id' => $accountid,
        'type' => $type,
        'order_id' => $orderid,
        'status' => 1,
    	'typeimage'=>"https://root.globalbet24.live/uploads/fastpay_image.png",
        'created_at' => now(),
        'updated_at' => now(),
    ]);
          DB::select("UPDATE ⁠ users ⁠ SET ⁠ wallet ⁠=⁠ wallet ⁠-$amount WHERE id=$userid;");
     if ($data) {
                 $response = [
            'status' =>200,
            'message' => 'Withdraw Request Successfully ..!',
        ];
    
        return response()->json($response,200);
    
            } else {
                 $response = [
            'status' =>400,
            'message' => 'Internal error..!',
        ];
    
        return response()->json($response,400);
                
            }
            }else{
          $response = [
            'status' =>400,
            'message' => 'insufficient Balance..!',
        ];
    
        return response()->json($response,400);
     }  
              }else{
          $response = [
            'status' =>400,
            'message' => 'first rechage is mandatory..!',
        ];
    
        return response()->json($response,400);
     }     
          }else {
             $response = [
            'status' =>400,
            'message' => 'need to bet amount 0 to be able to Withdraw',
        ];
    
        return response()->json($response,400);   
          }
            
        }else{
            $response['message'] = "minimum Withdraw 200 And Maximum Withdraw 25000";
                $response['status'] = "400";
                return response()->json($response,200); 
    	}    
}
	public function withdraw(Request $request)
    {
		$now = Carbon::now('Asia/Kolkata')->format('Y-m-d H:i:s');

    
    $validator = Validator::make($request->all(), [
        'user_id' => 'required',
        'account_id' => 'required',
        'type' => 'required',
        'amount' => 'required|numeric',
    ]);

    $validator->stopOnFirstFailure();
    
    if ($validator->fails()) {
        $response = [
            'status' => 400,
            'message' => $validator->errors()->first()
        ]; 
        
        return response()->json($response, 200);
    }


    $userid = $request->input('user_id');
		
		//$update_wallet = jilli::update_user_wallet($userid);

    $accountid = $request->input('account_id');
    $amount = $request->input('amount');
    $type = $request->input('type');
    
    // Define the minimum and maximum amounts based on the type
    if ($type == 0) {
        $minAmount = 110;
        $maxAmount = 25000;
    } elseif ($type == 1) {
        $minAmount = 100;
        $maxAmount = 25000;
    } else {
        // If type is invalid, return an error
        $response = [
            'status' => 400,
            'message' => 'Invalid withdrawal type!'
        ];
        return response()->json($response, 200);
    }

    // Check if the amount is within the valid range based on the type
    if ($amount < $minAmount || $amount > $maxAmount) {
        $response = [
            'status' => 400,
            'message' => "The minimum withdraw for this type is $minAmount and maximum withdraw is $maxAmount."
        ];
        return response()->json($response, 200);
    }
		

    $date = date('YmdHis');
    $rand = rand(11111, 99999);
    $orderid = $date . $rand;

    // Proceed with the logic if the amount is within the correct range
    if ($amount >= $minAmount && $amount <= $maxAmount) {
        // Here you can insert your logic to check the user's balance, first_recharge, etc.
        $wallet = DB::select("SELECT wallet, first_recharge FROM users WHERE id=$userid");
        $user_wallet = $wallet[0]->wallet;
        $first_recharge = $wallet[0]->first_recharge;

		if ($type == 1) {
        $usdtAmount = $amount*92;
    } else{
		$usdtAmount = $amount;
		}
		
        if ($user_wallet >= $amount) {
			//dd("hii");
            // Check if the user has done the first recharge
            if ($first_recharge == 0) {
                // Proceed with withdrawal
                $data = DB::table('withdraws')->insert([
                    'user_id' => $userid,
                    'amount' => $usdtAmount,
					'actual_amount'=>$amount,
                    'account_id' => $accountid,
                    'type' => $type,
                    'order_id' => $orderid,
                    'status' => 1,
                    'typeimage' => "https://root.globalbet24.live/uploads/fastpay_image.png",
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                // Update user's wallet balance
                DB::select("UPDATE users SET wallet = wallet - $amount WHERE id = $userid");
				
 $deduct_jili = jilli::deduct_from_wallet($userid,$amount);

                if ($data) {
                    $response = [
                        'status' => 200,
                        'message' => 'Withdraw request successful!'
                    ];
                    return response()->json($response, 200);
                } else {
                    $response = [
                        'status' => 400,
                        'message' => 'Internal error!'
                    ];
                    return response()->json($response, 400);
                }
            } else {
                $response = [
                    'status' => 400,
                    'message' => 'First recharge is mandatory!'
                ];
                return response()->json($response, 400);
            }
        } else {
            $response = [
                'status' => 400,
                'message' => 'Insufficient balance!'
            ];
            return response()->json($response, 200);
        }
    } else {
        $response = [
            'status' => 400,
            'message' => 'Invalid amount! Please check the minimum and maximum withdrawal limits.'
        ];
        return response()->json($response, 200);
    }
}
    public function withdrawHistory(Request $request)
    {
    // Validation rules
    $validator = Validator::make($request->all(), [
        'user_id' => 'required|integer',
        'type' => 'required|integer',  // Ensure type is integer
        'status' => 'sometimes|string',
        'created_at' => 'sometimes|date',
    ]);

    // Check for validation failures
    if ($validator->fails()) {
        return response()->json([
            'status' => 400,
            'message' => $validator->errors()->first(),
        ], 200);
    }

    // Fetching request parameters
    $query = Withdraw::query();

    // Adding conditions based on provided parameters
    $query->where('user_id', $request->user_id);
    
    if ($request->has('type')) {
        // Ensure type is an integer
        $query->where('type', $request->type);
    }

    if ($request->has('status')) {
        $query->where('status', $request->status);
    }

    if ($request->has('created_at')) {
        $query->whereDate('created_at', $request->created_at);
    }

    // Fetching the results
    $withdrawHistories = $query->orderBy('id', 'desc')->get();

    // Returning the response
    if ($withdrawHistories->isNotEmpty()) {
        return response()->json([
            'message' => 'Successfully retrieved',
            'status' => 200,
            'data' => $withdrawHistories,
        ], 200);
    } else {
        return response()->json([
            'message' => 'No record found',
            'status' => 400,
            'data' => [],
        ], 200);
    }
}
    public function deposit_history(Request $request)
    {
        // Validation rules
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|integer',  // Ensure 'user_id' is an integer
            'type' => 'required|integer',     // Ensure 'type' is an integer
        ]);
    
        // Check if validation fails
        if ($validator->fails()) {
            return response()->json([
                'status' => 400,
                'message' => $validator->errors()->first()
            ], 200);
        }
    
        // Fetch request parameters
        $user_id = $request->user_id;
        $type = $request->type;
    
        // Query using Eloquent
        $payinQuery = Payin::query();
    
        // Apply 'user_id' filter
        $payinQuery->where('user_id', $user_id);
    
        // Apply 'type' filter
        if (isset($type)) {
            $payinQuery->where('type', $type);  // Make sure 'type' is passed correctly as an integer
        }
    
        // Fetch the results, ordering by id descending
        $payin = $payinQuery->orderBy('id', 'desc')->get(['cash','usdt_amount', 'type', 'status', 'order_id', 'created_at']);
    
        // Return the response
        if ($payin->isNotEmpty()) {
            return response()->json([
                'message' => 'Successfully retrieved',
                'status' => 200,
                'data' => $payin
            ], 200);
        } else {
            return response()->json([
                'message' => 'No record found',
                'status' => 200,
                'data' => []
            ], 200);
        }
}
    public function giftCartApply(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'userid' => 'required',
            'code' => 'required',
        ]);
    
        $validator->stopOnFirstFailure();
    
        if ($validator->fails()) {
            return response()->json([
                'status' => 400,
                'message' => $validator->errors()->first(),
            ], 400);
        }
    
        $userid = $request->input('userid');
        $code = $request->input('code');
    
        // Find the gift card with the provided code and active status
        $giftCart = GiftCard::where('code', $code)->where('status', 1)->first();
    
        if ($giftCart) {
            if ($giftCart->availed_num < $giftCart->number_people) {
                // Check if the user has already claimed this gift card
                $claimUser = GiftClaim::where('gift_code', $code)->where('userid', $userid)->first();
    
                if (!$claimUser) {
                    date_default_timezone_set('Asia/Kolkata');
                    $datetime = now();  // Using Laravel's now() helper for current timestamp
    
                    $giftCartAmount = $giftCart->amount;
    
                    if (!empty($giftCartAmount)) {
                        // Insert into gift_claim table
                        GiftClaim::create([
                            'userid' => $userid,
                            'gift_code' => $code,
                            'amount' => $giftCartAmount,
                        ]);
    
                        // Update user's wallet, bonus, and recharge amounts
                        User::where('id', $userid)->increment('third_party_wallet', $giftCartAmount);
                        User::where('id', $userid)->increment('bonus', $giftCartAmount);
                        User::where('id', $userid)->increment('recharge', $giftCartAmount);
    
                        // Update availed_num in gift_cart table
                        $giftCart->increment('availed_num');
    
                        // Insert into wallet_history table
                        WalletHistory::create([
                            'user_id' => $userid,
                            'amount' => $giftCartAmount,
                            'type_id' => 5,
                            'created_at' => $datetime,
                            'updated_at' => $datetime,
                        ]);
    
                        return response()->json([
                            'status' => 200,
                            'message' => "Added $giftCartAmount Rs. Successfully",
                        ], 200);
                    } else {
                        return response()->json([
                            'status' => 400,
                            'message' => "No record found",
                        ]);
                    }
                } else {
                    return response()->json([
                        'status' => 400,
                        'message' => "You have already availed this offer!",
                    ]);
                }
            } else {
                return response()->json([
                    'status' => 400,
                    'message' => "No longer available for this offer.",
                ]);
            }
        } else {
            return response()->json([
                'status' => 400,
                'message' => "Invalid Gift Code!",
            ] );
        }
}
    public function claim_list(Request $request)
    {
        // Validate the request data
        $validator = Validator::make($request->all(), [
            'userid' => 'required',
        ]);
    
        $validator->stopOnFirstFailure();
    
        // Handle validation failure
        if ($validator->fails()) {
            $response = [
                'status' => 400,
                'message' => $validator->errors()->first()
            ];
    
            return response()->json($response, 400);
        }
    
        // Get the validated user ID
        $userid = $request->userid;
    
        // Fetch the account details using Eloquent Model
        $accountDetails = GiftClaim::where('userid', $userid)
                                    ->orderBy('id', 'DESC')
                                    ->get();
    //dd($accountDetails);
        // Check if account details were found
        if ($accountDetails->isNotEmpty()) {
            $response = [
                'message' => 'Successfully',
                'status' => 200,
                'data' => $accountDetails
            ];
    
            return response()->json($response, 200);
        } else {
            return response()->json([
                'message' => 'No record found',
                'status' => 200,
                'data' => []
            ], 200);
        }
}
    public function customer_service()
    {
        $customerService = CustomerService::where('status', 1)
            ->select('name', 'Image', 'link')
            ->get();
    
        if ($customerService->isNotEmpty()) {
            $response = [
                'message' => 'Successfully',
                'status' => 200,
                'data' => $customerService
            ];
            
            return response()->json($response);
        } else {
            return response()->json([
                'message' => 'No record found',
                'status' => 400,
                'data' => []
            ], 400);
        }
    }
    public function versionApkLink(Request $request)
    {
        $data = DB::select("SELECT * FROM `versions` WHERE `id`=1");
        if (count($data) > 0) {
            $row = $data[0];
            $response = [
    			'data'=>$row,
                'msg' => 'Success',
                'status' => 200
                
            ];
            return response()->json($response, 200);
        } else {
            return response()->json([
                'msg' => 'No record found',
                'status' => 400
            ], 400);
        }
    }
	public function salary_list(Request $request)
    {
    $validator = Validator::make($request->all(), [
        'userid' => 'required',
		'salary_type'=> 'required'
    ]);

    if ($validator->fails()) {
        return response()->json([
            'status' => 400,
            'message' => $validator->errors()->first()
        ],200);
    }
     $userid=$request->userid;
		$salary_type=$request->salary_type;
		
   
   $salary = DB::table('salary')
            ->where('user_id', $userid)
            ->where('salary_type', $salary_type)
            ->get();


    if ($salary->isNotEmpty()) {  
        return response()->json([
            'status' => "200",
            'message' => 'Success',
            'data' => $salary,
        ], 200);
    } else {
        return response()->json([
            'status' => "400",
            'message' => 'No data found.',
        ], 200);
    }
}
	public function betting_rebate()
	{
    
    $currentDate = date('Y-m-d');
		 
		 $a = DB::select("SELECT sum(amount) as betamount, userid FROM bets WHERE created_at like '$currentDate %' AND status= '2' GROUP BY userid;");

	   //dd($a);
		//$a = DB::select("SELECT `today_turnover` FROM `users` WHERE `id`=$userid ");
		
		foreach($a as $item){
		
		   $betamount = $item->betamount;
		   $userid = $item->userid;
			
			DB::select("UPDATE users SET wallet = wallet + $betamount * 0.01 WHERE id = $userid");
		$rebate_rate=0.01;
		  $insert= DB::table('wallet_histories')->insert([
        'user_id' => $userid,
        'amount' => $betamount*$rebate_rate,
        'description'=>$betamount,
        'description_2'=>$rebate_rate,
        'type_id' => 7,
		'created_at'=> now(),
        'updated_at' => now()
		
        ]);
		
	   }
		
	}		
	public function betting_rebate_history(Request $request)
    {
         
         $validator = Validator::make($request->all(), [
        'userid' => 'required|numeric',
        'type_id' => 'required'
    ]);

    $validator->stopOnFirstFailure();

    if ($validator->fails()) {
        $response = [
            'status' => 400,
            'message' => $validator->errors()->first()
        ];
        return response()->json($response, 400);
    }

    $userid = $request->userid;
    $subtypeid = $request->type_id;
    
    $value=DB::select("SELECT 
    COALESCE(SUM(amount), 0) as total_rebet,
    COALESCE(SUM(description), 0) as total_amount,
    COALESCE(SUM(CASE WHEN DATE(CURDATE()) = CURDATE() THEN amount ELSE 0 END), 0) as today_rebet 
FROM 
    wallet_histories 
WHERE 
    user_id = $userid && type_id =$subtypeid");
    
    $records=DB::select("SELECT 
    `amount` as rebate_amount,description_2 as rebate_rate,created_at as datetime,
    COALESCE((SELECT SUM(description) FROM wallet_histories WHERE `user_id` = $userid AND type_id = $subtypeid), 0) as betting_rebate 
FROM 
    `wallet_histories` 
WHERE 
    `user_id` = $userid AND type_id = $subtypeid;");


       
 
        if (!empty($records)) {
            $response = [
                'message' => 'Betting Rebet List',
                'status' => 200,
                'data1' =>$records,
                'data' =>$value,
            ];
            return response()->json($response,200);
        } else {
            return response()->json(['message' => 'Not found..!','status' => 400,
                'data' => []], 400);
        }
 

    }	
	public function invitation_bonus_claim(Request $request)
    {
     $validator = Validator::make($request->all(), [
        'userid' => 'required|numeric',
        'amount' => 'required'
    ]);

    $validator->stopOnFirstFailure();

    if ($validator->fails()) {
        $response = [
            'status' => 400,
            'message' => $validator->errors()->first()
        ];
        return response()->json($response, 400);
    }

    $userid = $request->userid;
    $amount = $request->amount;
$user = DB::table('users')->where('id', $userid)->first();

    // Update the user's wallet
   $usser= DB::table('users')->where('id', $userid)->update([
        'wallet' => $user->wallet + $amount, // Add amount to wallet
    ]);
 if (!empty($usser)) {
    // Insert into wallet_histories
    $bonuss=DB::table('wallet_histories')->insert([
        'user_id'     => $userid,
        'amount'      => $amount,
        'description' => 'Invitation Bonus',
        'type_id'     => 8, // Define type_id as 1 for bonus claim
        'created_at'  => now(),
        'updated_at'  => now(),
    ]);
 }else{
 
 }
     if (!empty($bonuss)) {
            $response = [
                'message' => 'invitation bonus claimed successfully!',
                'status' => 200,
            ];
            return response()->json($response,200);
        } else {
            return response()->json([
				'message' => 'invitation bonus claimed ..!',
				'status' => 400,
                ], 400);
        }
	}
    function paymentLimits()
    {
        try {
            $payLimits = DB::table("payment_limits")
                ->where("status", 1)
                ->select("id", "name", "amount")
                ->get();
    
            // Initialize structured response format
            $formattedData = [
                "indian_pay" => [
                    "deposit" => ["min" => null, "max" => null],
                    "withdraw" => ["min" => null, "max" => null]
                ],
                "kuber_pay" => [
                    "deposit" => ["min" => null, "max" => null],
                    "withdraw" => ["min" => null, "max" => null]
                ],
                "USDT" => [
                    "deposit" => ["min" => null, "max" => null],
                    "withdraw" => ["min" => null, "max" => null]
                ],
                "conversion_rate" => [
                    "deposit" => null,
                    "withdraw" => null
                ]
            ];
    
            foreach ($payLimits as $limit) {
                $name = strtolower($limit->name);
                $data = ["id" => $limit->id, "amount" => $limit->amount];
    
                // Indian Pay
                if (strpos($name, "indin pay minimum deposit") !== false) {
                    $formattedData["indian_pay"]["deposit"]["min"] = $data;
                } elseif (strpos($name, "indin pay maximum deposit") !== false) {
                    $formattedData["indian_pay"]["deposit"]["max"] = $data;
                } elseif (strpos($name, "indin pay minimum withdraw") !== false) {
                    $formattedData["indian_pay"]["withdraw"]["min"] = $data;
                } elseif (strpos($name, "indin pay maximum withdraw") !== false) {
                    $formattedData["indian_pay"]["withdraw"]["max"] = $data;
                }
    
                // Kuber Pay
                elseif (strpos($name, "kuber pay minimum deposit") !== false) {
                    $formattedData["kuber_pay"]["deposit"]["min"] = $data;
                } elseif (strpos($name, "kuber pay maximum deposit") !== false) {
                    $formattedData["kuber_pay"]["deposit"]["max"] = $data;
                } elseif (strpos($name, "kuber pay minimum withdraw") !== false) {
                    $formattedData["kuber_pay"]["withdraw"]["min"] = $data;
                } elseif (strpos($name, "kuber pay maximum withdraw") !== false) {
                    $formattedData["kuber_pay"]["withdraw"]["max"] = $data;
                }
    
                // USDT
                elseif (strpos($name, "usdt minimum deposit") !== false) {
                    $formattedData["USDT"]["deposit"]["min"] = $data;
                } elseif (strpos($name, "usdt maximum deposit") !== false) {
                    $formattedData["USDT"]["deposit"]["max"] = $data;
                } elseif (strpos($name, "usdt minimum withdraw") !== false) {
                    $formattedData["USDT"]["withdraw"]["min"] = $data;
                } elseif (strpos($name, "usdt maximum withdraw") !== false) {
                    $formattedData["USDT"]["withdraw"]["max"] = $data;
                }
    
                // Conversion Rate
                elseif (strpos($name, "deposit conversion rate") !== false) {
                    $formattedData["conversion_rate"]["deposit"] = $data;
                } elseif (strpos($name, "withdraw conversion rate") !== false) {
                    $formattedData["conversion_rate"]["withdraw"] = $data;
                }
            }
    
            return response()->json([
                'success' => true,
                'message' => 'Payment limits fetched successfully',
                'data' => $formattedData
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching payment limits: ' . $e->getMessage()
            ]);
        }
}
    public function updateSpribeIdForUsers()
    {
        // Check the actual value of 'spribe_id' in the database
        // Retrieve users where spribe_id is the string 'NULL'
        $usersToUpdate = DB::select("SELECT * FROM users WHERE spribe_id = 'NULL'");
    
        // Check if users are found
        if (count($usersToUpdate) === 0) {
            return response()->json([
                'status' => 200,
                'message' => 'No users found with spribe_id as "NULL" string.',
            ], 200);
        }
    
        // Loop through each user and update spribe_id
        foreach ($usersToUpdate as $user) {
            // Generate a unique spribe_id (you can customize this)
            $uniqueId = substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789'), 0, 16);
    
            // External API setup for Spribe
            $manager_key = 'FEGISo8cR74cf';
            $apiUrl = 'https://spribe.gamebridge.co.in/seller/v1/new-spribe-registration';
            $headers = [
                'Authorization' => 'Bearer ' . $manager_key,
                'Content-Type'  => 'application/json'
            ];
            $requestData = json_encode(['userId' => $uniqueId]);
            $payload = ['payload' => base64_encode($requestData)];
    
            try {
                // Make API request for Spribe
                $response = Http::withHeaders($headers)->post($apiUrl, $payload);
                $apiResponse = json_decode($response->body());
    
                // Log the API response
                Log::info('Spribe API Response for updating user:', ['response' => $response->body()]);
    
                if ($response->successful() && isset($apiResponse->error) && $apiResponse->error == false) {
                    // Update the spribe_id for the user (you'll need to update the database manually since this is not an Eloquent model)
                    DB::table('users')
                        ->where('id', $user->id)
                        ->update(['spribe_id' => $uniqueId]);
    
                    Log::info("Updated spribe_id for user ID: " . $user->id);
                }
            } catch (\Exception $e) {
                Log::error('API Error while updating spribe_id:', ['error' => $e->getMessage()]);
            }
        }
        return response()->json([
            'status' => 200,
            'message' => 'spribe_id updated for users with string "NULL" as spribe_id.',
        ], 200);
    }
    
    public function all_game(){
       $games = DB::table('game_list')->get();
    
        return response()->json([
            'status' => true,
            'message' => 'Game list',
            'data' => $games
        ]); 
    }
    public function  level_getuserbyrefid(Request $request)
    {
    
        
        $validator = Validator::make($request->all(), [
            'id' => 'required|integer'
        ]);
    
        $validator->stopOnFirstFailure();
    
        if ($validator->fails()) {
            $response = [
                'status' => 400,
                'message' => $validator->errors()->first()
            ];
            return response()->json($response, 400);
        }
    
        date_default_timezone_set('Asia/Kolkata');
        $datetime = date('Y-m-d H:i:s');
    
        $userId = $request->input('id');
        // dd($userId);
        $refer_code = User::where('id', $userId)->value('referral_code');
        $user_data = User::select('id','name', 'today_turnover', 'total_payin', 'no_of_payin', 'referrer_id', 'yesterday_payin','yesterday_register','referral_code','yesterday_first_deposit','yesterday_no_of_payin','deposit_amount','yesterday_total_commission','u_id','totalbet','first_recharge','turnover')->get()->toArray();
        $mlm_level_data = DB::table('mlm_levels')->get()->toArray();
    
        $alldata = [];
        $lastlevelname = 'Tier 6';
        foreach ($mlm_level_data as $mlm_level) {
            $name = $mlm_level->name;
            $commission = $mlm_level->commission;
            $usermlm = [];
    
            if ($name == 'Tier 1') {
                $usermlm[] = $userId;
            } else {
                $data = $mlm_level_data[array_search($mlm_level, $mlm_level_data) - 1]->name;
                foreach ($alldata[$data] as $itemss) {
                    $usermlm[] = $itemss['user_id'];
                }
            }
    
            $filtered_users = array_filter($user_data, function($item) use ($usermlm) {
                return in_array($item['referrer_id'], $usermlm);
            });
    
            $level = [];
            foreach ($filtered_users as $item) {
                $todays = $item['today_turnover'] * $commission * 0.01 ;
                $level[] = [
                    "user_id" => $item['id'],
                     "u_id" => $item['u_id'],
                     'totalbet'=> $item['totalbet'],
                    "username" => $item['username'],
                    "first_recharge"=>$item['first_recharge'],
                     "deposit_amount" => $item['deposit_balance'],
                    "turnover" => $item['turnover'],
                    'today_turnover'=>$item['today_turnover'],
                    "commission" => number_format((float)$todays, 2, '.', ''),
                    'total_payin'=> $item['total_payin'],
                    'no_of_payin'=>$item['no_of_payin'],
                    'yesterday_payin'=>$item['yesterday_payin'],
                    'yesterday_register'=>$item['yesterday_register'],
                    'yesterday_no_of_payin'=>$item['yesterday_no_of_payin'],
                    'yesterday_first_deposit'=>$item['yesterday_first_deposit']
                ];
            }
    
            $alldata[$name] = $level;
            $lastlevelname = $name;
        }
    
        $totalcommission = 0;
        $totaluser = 0;
        $datalevelcome = [];
        $indirectTeam = 0;
        $numofpayindirect = 0;
        $numofpayteam = 0;
        $payinAmountDirect = 0;
        $payinAmountTeam = 0;
        $noUserDirect = 0;
        $noUserTeam = 0;
        $noOfFristPayinDirect = 0;
        $noOfFristPayinTeam = 0;
        
        $yesterday_total_commission = 0;
        
        $yesterday_payin_direct = 0;
        $yesterday_register_direct = 0;
        $yesterday_no_of_payin_direct = 0;
        $yesterday_first_deposit_direct = 0;
    
        $yesterday_payin_team = 0;
        $yesterday_register_team = 0;
        $yesterday_no_of_payin_team = 0;
        $yesterday_first_deposit_team = 0;
    
       
            $deposit_number_all=0;
            $deposit_amount_all=0;
            $first_recharge_all=0;
            $no_of_firstrechage_all=0;
            $total_bet_all=0;
            $total_bet_amount_all=0;   
       
       
    
        foreach ($mlm_level_data as $mlm_level) {
            $name = $mlm_level->name;
            $levelcom = 0;
            $deposit_number=0;
            $deposit_amount=0;
            $first_recharge=0;
            $no_of_firstrechage=0;
            $total_bet=0;
            $total_bet_amount=0;
    
            foreach ($alldata[$name] as $obj) {
                $totalcommission += $obj['commission'];
                $deposit_number_all+=$obj['total_payin'];
            $deposit_amount_all+=$obj['no_of_payin'];
            $first_recharge_all+=$obj['first_recharge'];
            $no_of_firstrechage_all+=$no_of_firstrechage;
            $total_bet_all+=$total_bet;
            $total_bet_amount_all+=$total_bet_amount; 
            
            
            
                $totaluser++;
                $levelcom += $obj['commission'];
                if ($name == 'Tier 1') {
                    $payinAmountDirect += $obj['total_payin'];
                    $noUserDirect++;
                    if ($obj['yesterday_payin'] != '0') {
                         $numofpayindirect++;
                        $noOfFristPayinDirect++;
                    }
                    if ($obj['no_of_payin'] != '0') {
                      //  $numofpayindirect++;
                    }
                    
                    $yesterday_payin_direct += $obj['yesterday_payin'];
                    $yesterday_register_direct = $obj['yesterday_register'];
                   // $yesterday_no_of_payin_direct += $obj['yesterday_no_of_payin'];
                    $yesterday_first_deposit_direct += $obj['yesterday_first_deposit'];
    
                } else {
                    $payinAmountTeam += $obj['total_payin'];
                    $noUserTeam++;
                    $indirectTeam++;
                    if ($obj['total_payin'] != '0') {
                        $noOfFristPayinTeam++;
                    }
                    if ($obj['no_of_payin'] != '0') {
                        $numofpayteam++;
                    }
                    if ($name != $lastlevelname) {
                        if($obj['first_recharge'] > 0){
                            
                       $first_recharge += $obj['first_recharge'];
    
                           $no_of_firstrechage++;
                        }
                        $total_bet_amount += $obj['today_turnover']+$obj['turnover'];
                        $total_bet += $obj['totalbet'];
                        
                        
                        
                        $deposit_number += $obj['no_of_payin'];
                        $deposit_amount +=$obj['total_payin'];
                        $yesterday_payin_team += $obj['yesterday_payin'];
                        $yesterday_register_team += $obj['yesterday_register'];
                        $yesterday_no_of_payin_team += $obj['yesterday_no_of_payin'];
                        $yesterday_first_deposit_team += $obj['yesterday_first_deposit'];
                    }
                }
            }
    
            $datalevelcome[] = [
                'count' => count($alldata[$name]),
                'name' => $name,
                'commission' => number_format($levelcom, 2, '.', ''),
                'total_payin'=>$deposit_amount,
                'no_of_payin' =>$deposit_number,
                'first_recharge' =>$first_recharge,
                'no_of_people'=>$no_of_firstrechage,
                'totalbet'=>$total_bet,
                'total_bet_amount'=>$total_bet_amount
                
            ];
          
        }
      $datalevelcome[]=[
            'count' => $totaluser,
            'name' => "all",
            'commission' => number_format($totalcommission, 2, '.', ''),
            'total_payin'=>$deposit_number_all,
            'no_of_payin' =>$deposit_amount_all,
            'first_recharge' =>$first_recharge_all,
            'no_of_people'=>$no_of_firstrechage_all,
            'totalbet'=>$total_bet_all,
            'total_bet_amount'=>$total_bet_amount_all
                ];
        return response()->json([
            'direct_user_count' => $yesterday_register_direct ?? 0,
            'numofpayindirect' => $yesterday_no_of_payin_direct ?? 0,
            'noUserDirect' => $yesterday_register_direct ?? 0,
            'noOfFristPayinDirect' => $numofpayindirect ?? 0,
            'payinAmountDirect' => $yesterday_payin_direct ?? 0,
            'indirect_user_count' => $yesterday_register_team ?? 0,
            'numofpayteam' => $yesterday_no_of_payin_team ?? 0,
            'payinAmountTeam' => $yesterday_payin_team ?? 0,
            'noUserTeam' => $yesterday_register_team ?? 0,
            'noOfFristPayinTeam' => $yesterday_first_deposit_team ?? 0,
            'total_payin_direct'=> $payinAmountDirect ?? 0,
            'total_register_direct'=>$noUserDirect ?? 0,
            'total_no_of_payin_direct'=>$numofpayindirect ?? 0,
            'total_first_deposit_direct'=>$noOfFristPayinDirect ?? 0,
            'total_payin_team'=>$payinAmountTeam ?? 0,
            'total_register_team'=>$noUserTeam ?? 0,
            'total_no_of_payin_team'=>$numofpayteam ?? 0,
            'total_first_deposit_team'=>$noOfFristPayinTeam ?? 0,      
            'totaluser' => "$totaluser" ?? 0,
            'totalcommission' => number_format($totalcommission, 2, '.', ''),
            'yesterday_totalcommission' => number_format($yesterday_total_commission, 2, '.', ''),
            'user_refer_code' => $refer_code,
            'levelwisecommission' => $datalevelcome ?? 0,
            'user_id' => $userId ?? 0,
            'userdata' => $alldata ?? 0,
            ///
            // 'all_total_payin'=>$deposit_number_all,
            // 'all_no_of_payin' =>$deposit_amount_all,
            // 'all_first_recharge' =>$first_recharge_all,
            // 'all_no_of_people'=>$no_of_firstrechage_all,
            // 'all_totalbet'=>$total_bet_all,
            // 'all_total_bet_amount'=>$total_bet_amount_all
        ]);
    }
    public function attendance_List(Request $request)
    {
           $validator = Validator::make($request->all(), [
         'userid' => 'required|numeric'
    ]);

	
	$validator->stopOnFirstFailure();
	
    if($validator->fails()){
		
		        		     $response = [
                        'status' => 400,
                       'message' => $validator->errors()->first()
                      ]; 
		
		return response()->json($response,400);
		
    }
     $userid = $request->userid;  
       // $userid = $request->input('userid');
      $list = DB::select("SELECT COALESCE(COUNT(at_claim.`userid`),0) AS attendances_consecutively , COALESCE(SUM(attendances.attendanceBonus),0) AS accumulated FROM `at_claim` LEFT JOIN attendances ON at_claim.attendance_id =attendances.id WHERE at_claim.userid=$userid");

    $day = $list[0]->attendances_consecutively;
    $bonus_amt = $list[0]->accumulated;


        $attendanceList = DB::select("
        SELECT a.`id` AS `id`, a.`accumulatedAmount` as accumulatedAmount ,a.`attendanceBonus` as attendanceBonus, COALESCE(c.`status`, '1') AS `status`, COALESCE(a.`created_at`, 'Not Found') AS `created_at` FROM `attendances` a LEFT JOIN `at_claim` c ON a.`id` = c.`attendance_id` AND c.`userid` =$userid  ORDER BY a.`id` ASC LIMIT 7 ");

        if (!empty($attendanceList)) {
            $response = [
                'message' => 'Attendance List',
                'status' => 200,
                'attendances_consecutively' => $day,
                'accumulated' =>$bonus_amt,
                'data' => $attendanceList,
            ];
            return response()->json($response);
        } else {
            return response()->json(['message' => 'Not found..!','status' => 400,
                'data' => []], 400);
        }
    }
    public function attendance_history(Request $request)
    {
        $validator = Validator::make($request->all(), [
        'userid' => 'required|numeric'
        ]);
	    $validator->stopOnFirstFailure();
        if($validator->fails()){
		    $response = [
            'status' => 400,
            'message' => $validator->errors()->first()
            ]; 
		    return response()->json($response,400);
        }
        $userid = $request->userid;  
        $list1 = DB::select("SELECT at_claim.id AS id,attendances.attendanceBonus AS attendanceBonus,at_claim.created_at FROM attendances LEFT JOIN at_claim 
        ON at_claim.attendance_id=attendances.id WHERE at_claim.userid=$userid");
        if (!empty($list1)) {
            $response = [
                'message' => 'Attendance History',
                'status' => 200,
                'data' => $list1,
            ];
            return response()->json($response);
        } else {
            return response()->json(['message' => 'Not found..!','status' => 400,
                'data' => []], 400);
        }
    }
    public function attendance_claim(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'userid' => 'required|numeric'
        ]);
        if ($validator->fails()) {
            $response = [
                'status' => 400,
                'message' => $validator->errors()->first()
            ];
            return response()->json($response, 400);
        }
        $userid = $request->userid;
        $results = DB::select("SELECT a.`id` AS `id`, a.`accumulatedAmount` AS accumulatedAmount, a.`attendanceBonus` AS attendanceBonus, COALESCE(c.`status`, '1') AS `status`, COALESCE(a.`created_at`, 'Not Found') AS `created_at`, u.`wallet` FROM `attendances` a LEFT JOIN `at_claim` c ON a.`id` = c.`attendance_id` AND c.`userid` = $userid JOIN `users` u ON u.id = $userid WHERE COALESCE(c.`status`, '1') = '1' AND u.wallet >= a.accumulatedAmount ORDER BY a.`id` ASC LIMIT 7");
        if (count($results) > 0) {
            $bonus = $results[0]->attendanceBonus;
            $id = $results[0]->id;
            $accumulated_amount =$results[0]->accumulatedAmount;
            $wallet = $results[0]->wallet;
        if($wallet >= $accumulated_amount){
            $count = DB::select("SELECT COALESCE(COUNT(userid), 0) AS userid FROM `at_claim` WHERE userid = $userid AND DATE(created_at) = CURDATE()");
        $datetime = now();
        if ($count[0]->userid == 0) {
            DB::table('at_claim')->insert([      
                'userid' => $userid,
                'attendance_id' => $id,   
                'status' => '0',
                'created_at' => $datetime,
                'updated_at' => $datetime    
            ]);
            DB::table('users')->where('id', $userid)->increment('wallet', $bonus);
            $hii =DB::table('wallet_history')->insert([
                'userid' => $userid,
                'amount' => $bonus,
                'subtypeid' => 11,
                'created_at' => $datetime,
                'updated_at' => $datetime
            ]);
            $response = [
                'message' => 'Today Claimed Successfully ...!',
                'status' => 200,
            ];
            echo $response;die;
            return response()->json($response, 200);
        } else {
            return response()->json(['message' => 'Today You Have Already Claimed', 'status' => 200], 200); 
        }
    }else{
      return response()->json(['message' => 'You can not claim due to insufficient Balance...!', 'status' => 400], 400);  
    }
            
        } else {
            return response()->json(['message' => 'Today You Have Already Claimed..!', 'status' => 400], 400);
        }
    }
    public function contact_us()
      {
        $contact = DB::table('settings')->where('id', 4)
            ->where('status', 1)
            ->select('name', 'description', 'link')
            ->first();
        if ($contact) {
            $response = [
                'message' => 'Successfully',
                'status' => 200,
                'data' => $contact
            ];
            return response()->json($response);
        } else {
            return response()->json(['message' => 'No record found','status' => 400,
            'data' => []], 400);
        }
    }
	public function add_fav_game(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'userid' => 'required|exists:users,id',
            'game_id' => 'required|array',
        ]);
        $validator->stopOnFirstFailure();
        if ($validator->fails()) {
            return response()->json(['status' => 400, 'message' => $validator->errors()->first()]);
        }
        $userid = $request->userid;
        $gameInput = $request->game_id;
        $gameIds = array_map(function($item) {
            return $item['game_id'];
        }, $gameInput);
        $existingGames = DB::table('game_list')->whereIn('id', $gameIds)->pluck('id')->toArray();
        $invalidGames = array_diff($gameIds, $existingGames);
        if (!empty($invalidGames)) {
            return response()->json([
                'status' => 400,
                'message' => 'Invalid game_id(s): ' . implode(', ', $invalidGames),
            ]);
        }
        DB::table('user_fav_game')->updateOrInsert(
            ['userid' => $userid],
            ['game_id' => json_encode($gameIds)]
        );
        return response()->json([
            'status' => 200,
            'message' => 'Favorite games added successfully',
        ]);
    }
    public function forget(Request $request)
    {
		$validator = Validator::make($request->all(), [
        'mobile' => 'required', 'regex:/^\d{10}$/','exists:users,mobile',
	    'password' => 'required|string|min:6'
        ]);
	        $validator->stopOnFirstFailure();
        if($validator->fails()){
            return response()->json([
            'status' => 400,
            'message' => $validator->errors()->first()
            ]);
        }
    	$user = DB::table('users')->where('mobile',$request->mobile)->update(['password'=>$request->password]);
    	return response()->json([
    	'status'=>200,
    	'message'=>'Password reset successfully.',
    	]);
	}
    public function fav_game(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'userid' => 'required|numeric|exists:user_fav_game,userid'
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => 400,
                'message' => $validator->errors()->first()
            ], 400);
        }
        $userid = $request->userid;
        $fav = DB::table('user_fav_game')->where('userid', $userid)->first();
        if (!$fav) {
            return response()->json([
                'status' => 404,
                'message' => 'No favorite games found for this user',
            ], 404);
        }
        $gameIds = json_decode($fav->game_id, true);
        $games = DB::table('game_list')->whereIn('id', $gameIds)->get();
        return response()->json([
            'status' => 200,
            'message' => 'Favorite games fetched successfully',
            'data' => $games
        ]);
    }
    public function delete_fav_game(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'userid' => 'required|numeric|exists:user_fav_game,userid'
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => 400,
                'message' => $validator->errors()->first()
            ], 400);
        }
        $userid = $request->userid;
        DB::table('user_fav_game')->where('userid', $userid)->delete();
        return response()->json([
            'status' => 200,
            'message' => 'Favorite games deleted successfully'
        ]);
    }
    public function notification()
    {
        $notification = DB::select("SELECT disc AS discription FROM notifications WHERE status = 1");
        if ($notification && isset($notification[0])) {
            return response()->json([
                'message' => 'Successfully',
                'status' => 200,
                'discription' => $notification[0]->discription
            ], 200);
        } else {
            return response()->json([
                'message' => 'No record found',
                'status' => 400,
                'discription' => ''
            ], 400);
        }
    }
}