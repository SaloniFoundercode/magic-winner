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
use App\Models\BankDetail;
use Carbon\Carbon;
use DateTime;
use DateTimeZone;
use App\Models\Payin;
use App\Models\WalletHistory;
use App\Models\withdraw;
use App\Models\GiftCard;
use App\Models\{GiftClaim,Version};
use App\Models\CustomerService;
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
      
         $user = DB::select("SELECT `image` FROM `all_images`");
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

    // Consistent variable names
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
        // Validate input
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
            // Insert new user into the database
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
    	//$characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ'; // Only uppercase letters
        $characters = '0123456789'; // You can expand this to include more characters if needed.
        $randomString = '';
    
        // Loop to generate the random string
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
        // Create an instance of the jilli class
        //$jilliInstance = new jilli();
        
        // Call the method on the instance
        //$wallet_update = $jilliInstance->update_user_wallet($id);
    
        $ldate = new DateTime('now', new DateTimeZone('Asia/Kolkata'));
        // echo $ldate->format('Y-m-d H:i:s');
    
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
        'id' => 'required'
    ]);

    $validator->stopOnFirstFailure();

    if ($validator->fails()) {
        $response = [
            'status' => 400,
            'message' => $validator->errors()->first()
        ];
        return response()->json($response, 400);
    }

        
        $id = $request->id;
        
        $value = User::findOrFail($id);
        $status=$value->status;
        
        	if($status == 1)
        {
        if (!empty($request->name)) {
            $value->name = $request->name;
        }
        
        if (!empty($request->image) && $request->image != "null") {
            $value->image = $request->image;
        }
    
        // Save the changes
        $value->save();
    
        $response = [
            'status' => 200,
            'message' => "Successfully updated"
        ];
    
        return response()->json($response, 200);
        }else{
             $response['message'] = "User block by admin..!";
                    $response['status'] = "401";
                    return response()->json($response,401);
        }
    }
	public function update_profile11(Request $request)
    {
    $request->validate([
        'id' => 'required',
        'name' => 'required|string',
        'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048' // Image validation
    ]);

    $id = $request->id;

    // Fetch user
    $user = User::findOrFail($id);

    if ($user->status == 1) {
        // Update name
        $user->name = $request->name;

        // Handle image update if provided
        if ($request->hasFile('image')) {
            // Delete old image if it exists
            if ($user->image) {
                $oldImagePath = public_path('uploads/profile_images/' . $user->image);
                if (file_exists($oldImagePath)) {
                    unlink($oldImagePath);
                }
            }

            // Save new image
            $image = $request->file('image');
            $imageName = uniqid() . '.' . $image->getClientOriginalExtension();
            $image->move(public_path('uploads/profile_images'), $imageName);

            $user->image = $imageName;
        }

        // Save changes
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

    // Fetch user
    $user = User::findOrFail($id);

    if ($user->status == 1) {
        $user->name = $request->name;
        $user->mobile = $request->mobile;

        // Save the changes
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
        //dd($user);
        // Verify the current password
        if ($user->password !== $request->password) {
            return response()->json([
                'status' => "400",
                'message' => 'Current password is incorrect'
            ], 200);
        }
    
        // Update the password
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
    
        $datetime = Carbon::now();
    
        // Check if the account exists
        // $existingAccount = BankDetail::where('userid', $userid)->first();
        // if ($existingAccount) {
        //     $existingAccount->update([
        //         'name' => $name,
        //         'account_num' => $account_number,
        //         'bank_name' => $bank_name,
        //         'ifsc_code' => $ifsc_code,
        //     ]);
    
        //     return response()->json([
        //         'status' => "200",
        //         'message' => 'Account Updated Successfully.',
        //     ]);
        // }
    
        // Create a new account
        $account = BankDetail::create([
            'userid' => $userid,
            'name' => $name,
            'account_num' => $account_number,
            'bank_name' => $bank_name,
            'ifsc_code' => $ifsc_code,
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
        // Using the CustomerService model to fetch the data
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
        // Retrieve the version data using raw query
        $data = DB::select("SELECT * FROM `versions` WHERE `id`=1");
    
        if (count($data) > 0) {
            // Accessing the first row
            $row = $data[0];
    
            $response = [
    			'data'=>$row,
                'msg' => 'Success',
                'status' => 200
                
            ];
            return response()->json($response, 200);
        } else {
            // If no data is found, return a 400 response
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
    // 	public function registers(Request $request)
//     {
//     $validator = Validator::make($request->all(), [
//         'email' => 'required|email|unique:users,email',
//         'mobile' => 'required|numeric|digits:10|unique:users,mobile',
//         'password' => 'required|min:8',
//         'referral_code' => 'nullable|string|exists:users,referral_code'
//     ]);

//     if ($validator->fails()) {
//         return response()->json([
//             'status' => 400,
//             'message' => $validator->errors()->first()
//         ], 200);
//     }
//     // dd($validator);
//     $randomName = 'User_' . strtoupper(Str::random(5));
//     // dd($randomName);
//     $email = $request->email;
//     $mobile = $request->mobile;
//     $baseUrl = URL::to('/');
//     // dd($baseUrl);
//     $uid = $this->generateSecureRandomString(6);
//     $uniqueId = substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789'), 0, 16);
// // dd($uniqueId);
//     $data = [
//         'name' => $randomName,
//         'u_id' => $uid,
//         'mobile' => $mobile,
//         'password' => $request->password,
//         'image' => $baseUrl . "/image/download.png",
//         'status' => 1,
//         'referral_code' => $uid,
//         'wallet' => 28,
//         'email' => $email,
//         'spribe_id' => $uniqueId
//     ];
// // dd($data);
//     if ($request->has('referral_code')) {
//         $referrer = User::where('referral_code', $request->referral_code)->first();
//         if ($referrer) {
//             $data['referrer_id'] = $referrer->id;
//         }
//     }

//     // External API setup Spribe
//     $manager_key = 'FEGISo8cR74cf';
//     dd($manager_key);
// 		$authorizationtoken='1740116434200';
		
//     $apiUrl = 'https://spribe.gamebridge.co.in/seller/v1/new-spribe-registration';
//     $headers = [
//         'Authorization' => 'Bearer ' . $manager_key,
//         'Content-Type'  => 'application/json',
// 		'authorizationtoken' => 'Bearer '.$authorizationtoken
//     ];
//     $requestData = json_encode(['userId' => $uniqueId]);
//     $payload = ['payload' => base64_encode($requestData)];

//     // External API setup Jilli
//     $manager_keys = 'FEGISo8cR74cf';
// 		$authorizationtoken='1740116434200';
//     $apiUrls = 'https://api.gamebridge.co.in/seller/v1/get-newjilli-game-registration';
//     $headers1 = [
//         'Authorization' => 'Bearer ' . $manager_keys,
//         'Content-Type'  => 'application/json',
// 		'authorizationtoken' => 'Bearer '.$authorizationtoken,
//     ];
//     $requestData1 = json_encode(['mobile' => $mobile]);
//     $payload1 = ['payload' => base64_encode($requestData1)];

//     try {
//         // Make API request Spribe
//         $response = Http::withHeaders($headers)->post($apiUrl, $payload);
//         $apiResponse = json_decode($response->body());
//         //dd($apiResponse);
//         // Log the full response
//         Log::info('Spribe API Response:', ['response' => $response->body()]);

//         // Make API request Jilli
//         $response1 = Http::withHeaders($headers1)->post($apiUrls, $payload1);
//         $apiResponse1 = json_decode($response1->body());
//         // dd($apiResponse,$apiResponse1);
//         // Log the full response
//         Log::info('Jilli API Response:', ['response' => $response1->body()]);

//         if ($response->successful() && isset($apiResponse->error) && $apiResponse->error == false) {
//             if (isset($apiResponse1->accountNo)) {
//                 $data['accountNo'] = $apiResponse1->accountNo;
//             }

//             $user = User::create($data);

//             if ($user) {
//                 return response()->json([
//                     'status' => 200,
//                     'message' => 'Registration successful',
//                     'data' => [
//                         'userId' => $user->id,
//                         'token' => $user->createToken('UserApp')->plainTextToken
//                     ],
//                     'api_response' => $apiResponse
//                 ], 200);
//             }
//         }

//         return response()->json([
//             'status' => 400,
//             'message' => 'Failed to register.',
//             'api_response' => $response->body()
//         ], 400);

//     } catch (\Exception $e) {
//         Log::error('API Error:', ['error' => $e->getMessage()]);
//         return response()->json([
//             'status' => 400,
//             'message' => 'Internal Server Error',
//             'error' => $e->getMessage()
//         ], 400);
//     }
// }
 // public function login(Request $request)
    // {
    //     // dd($request);
    //     $identity = $request->input('identity'); 
    //     $password = $request->input('password');

    //     if (!empty($identity) && !empty($password)) {
    //         $user = $this->getUserByCredentials($identity, $password);

    //         if ($user) {
    //             $response['message'] = "Login successful";
    //             $response['status'] = "200";
    //             $response['id'] = $user->id;
    //             return response()->json($response,200);
    //         } else {
    //             $response['message'] = "Invalid credentials, Contact admin..!";
    //             $response['status'] = "401";
    //             return response()->json($response,200);
    //         }
    //     } else {
    //         $response['message'] = "Email or mobile and password are required";
    //         $response['status'] = "400";
    //         return response()->json($response,200);
    //     }
    // }
}
