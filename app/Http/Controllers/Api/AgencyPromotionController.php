<?php

namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Validator;
use App\Models\{User,MlmLevel,Payin};
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

use Illuminate\Support\Facades\Http;


 
class AgencyPromotionController extends Controller
{
	
	public function promotion_data_old($id) 
{
    try {
        $user = User::findOrFail($id);
        $currentDate = Carbon::now()->subDay()->format('Y-m-d');
        //dd($currentDate);
        $directSubordinateCount = $user->referrals()->count();
        $totalCommission = $user->commission;
        $referralCode = $user->referral_code;
        $yesterdayTotalCommission = $user->yesterday_total_commission;
		//dd($yesterdayTotalCommission);

        $teamSubordinateCount = $user->getAllSubordinatesCount();
		//dd($teamSubordinateCount);
		
		

        $register = User::where('referrer_id', $user->id)
                        ->whereDate('created_at', $currentDate)
                        ->count();
		//dd($register);

        // Fetch deposit statistics
        $depositStats = $user->payins()
            ->whereDate('created_at', $currentDate)
            ->selectRaw('COUNT(id) as deposit_number, SUM(cash) as deposit_amount')->first();
		//dd($depositStats);
            //->toRawSql();
            
            //dd($depositStats);
  // Adjusted this query to remove 'salary_first_recharge' condition
        $firstDepositCount = $user->payins()
            ->whereDate('created_at', $currentDate)
            ->count();  // Removed the 'salary_first_recharge' condition

        $subordinatesRegister = User::where('referrer_id', $user->id)
            ->whereDate('created_at', $currentDate)
            ->count();

        // Subordinate deposit data
        $subordinatesDeposit = $user->payins()
            ->whereDate('created_at', $currentDate)
            ->selectRaw('COUNT(id) as deposit_number, SUM(cash) as deposit_amount')
            ->first();

        // Adjusted this query to remove 'salary_first_recharge' condition
        $subordinatesFirstDepositCount = $user->payins()
            ->whereDate('created_at', $currentDate)
            ->count();  // Removed the 'salary_first_recharge' condition

        // Result array to return
        $result = [
            'yesterday_total_commission' => $yesterdayTotalCommission ?? 0,
            'register' => $register,
            'deposit_number' => $depositStats->deposit_number ?? 0,
            'deposit_amount' => $depositStats->deposit_amount ?? 0,
            'first_deposit' => $firstDepositCount,
            'subordinates_register' => $subordinatesRegister,
            'subordinates_deposit_number' => $subordinatesDeposit->deposit_number ?? 0,
            'subordinates_deposit_amount' => $subordinatesDeposit->deposit_amount ?? 0,
            'subordinates_first_deposit' => $subordinatesFirstDepositCount,
            'direct_subordinate' => $directSubordinateCount,
            'total_commission' => $totalCommission,
			'weekly_commission'=>0,
            'team_subordinate' => $teamSubordinateCount,
            'referral_code' => $referralCode,
        ];

        return response()->json(['status' => 200,'message' => 'data fetch successfully','data' =>$result], 200);
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
}
public function promotion_data_old_new($id) 
{
    try {
        $user = User::findOrFail($id);
        $currentDate = Carbon::now()->subDay()->format('Y-m-d');
        
        // Direct subordinate count
        $directSubordinateCount = $user->referrals()->count();
		//return $directSubordinateCount;

        // Total commission and referral code
        $totalCommission = $user->commission;
        $referralCode = $user->referral_code;
        $yesterdayTotalCommission = $user->yesterday_total_commission;

        // Team subordinate count
        $teamSubordinateCount = $user->getAllSubordinatesCount();

        // Number of users registered by the current user on the current day
        $register = User::where('referrer_id', $user->id)
                        ->whereDate('created_at', $currentDate)
                        ->count();

        // Fetch deposit statistics for the user on the current day
        $depositStats = $user->payins()
            ->whereDate('created_at', $currentDate)
            ->selectRaw('COUNT(id) as deposit_number, SUM(cash) as deposit_amount')
            ->first();
      
        // First deposit count (user's first deposit on the current day)
        $firstDepositCount = $user->payins()
            ->whereDate('created_at', $currentDate)
            ->count();

        // Subordinate register count on the current day
        $subordinatesRegister = User::where('referrer_id', $user->id)
            ->whereDate('created_at', $currentDate)
            ->count();

        // Fetch referred users (subordinates)
        $referUserIds = DB::table('users')
            ->where('referrer_id', $user->id)
            ->pluck('id');  // Get the ids of all referred users

        // Initialize variables for aggregated deposit data for all subordinates
        $totalDepositNumber = 0;
        $totalDepositAmount = 0;
        $totalFirstDepositCount = 0;

        // Aggregate deposit data for all referred users (subordinates)
        if ($referUserIds->isNotEmpty()) {
            $subordinatesDepositStats = DB::table('payins')
                ->whereIn('user_id', $referUserIds)
                ->whereDate('created_at', $currentDate)
                ->selectRaw('user_id, COUNT(id) as deposit_number, SUM(cash) as deposit_amount')
                ->groupBy('user_id')
                ->get();

            // Sum the results across all referred users
            foreach ($subordinatesDepositStats as $stats) {
                $totalDepositNumber += $stats->deposit_number;
                $totalDepositAmount += $stats->deposit_amount;
            }

            // Get first deposit count for subordinates
            $totalFirstDepositCount = DB::table('payins')
                ->whereIn('user_id', $referUserIds)
                ->whereDate('created_at', $currentDate)
                ->count();
        }

        // Prepare the result
        $result = [
            'yesterday_total_commission' => $yesterdayTotalCommission ?? 0,
            'register' => $register,
            'deposit_number' => $depositStats->deposit_number ?? 0,
            'deposit_amount' => $depositStats->deposit_amount ?? 0,
            'first_deposit' => $firstDepositCount,
            'subordinates_register' => $subordinatesRegister,
            'subordinates_deposit_number' => $totalDepositNumber,
            'subordinates_deposit_amount' => $totalDepositAmount,
            'subordinates_first_deposit' => $totalFirstDepositCount,
            'direct_subordinate' => $directSubordinateCount,
            'total_commission' => $totalCommission,
            'weekly_commission' => 0, // This value seems to be placeholder, modify as needed
            'team_subordinate' => $teamSubordinateCount,
            'referral_code' => $referralCode,
        ];

        return response()->json([
            'status' => 200,
            'message' => 'Data fetched successfully',
            'data' => $result
        ], 200);
        
    } catch (\Exception $e) {
        return response()->json([
            'error' => $e->getMessage()
        ], 500);
    }
}
	
	public function promotion_data($id) 
{
    try {
        $user = User::findOrFail($id);
        $currentDate = Carbon::now()->subDay()->format('Y-m-d');
        
        // Direct subordinate count
        $directSubordinateCount = $user->referrals()->count();
        
        // Total commission and referral code
        $totalCommission = $user->commission;
        $referralCode = $user->referral_code;
        $yesterdayTotalCommission = $user->yesterday_total_commission;

        // Team subordinate count
        // Assuming getAllSubordinatesCount includes both direct and indirect subordinates
        $teamSubordinateCount = $user->getAllSubordinatesCount();

        // Number of users registered by the current user on the current day
        $register = User::where('referrer_id', $user->id)
                        ->whereDate('created_at', $currentDate)
                        ->count();

        // Fetch deposit statistics for the user on the current day
        $depositStats = $user->payins()
            ->whereDate('created_at', $currentDate)
            ->selectRaw('COUNT(id) as deposit_number, SUM(cash) as deposit_amount')
            ->first();
         //dd($depositStats);
        // First deposit count (user's first deposit on the current day)
        $firstDepositCount = $user->payins()
            ->whereDate('created_at', $currentDate)
            ->count();

        // Subordinate register count on the current day
       $subordinatesRegisters = DB::select("SELECT COUNT(*) as count FROM users WHERE referrer_id IN ( SELECT id FROM users WHERE referrer_id = $user->id ) AND DATE(created_at) = CURDATE() - INTERVAL 1 DAY;");

// Extract the count value
$subordinatesRegister = $subordinatesRegisters[0]->count;

		//dd($count);

        // Fetch referred users (subordinates)
        $referUserIds = DB::table('users')
            ->where('referrer_id', $user->id)
            ->pluck('id');  // Get the ids of all referred users

        // Initialize variables for aggregated deposit data for all subordinates
        $totalDepositNumber = 0;
        $totalDepositAmount = 0;
        $totalFirstDepositCount = 0;

        // Aggregate deposit data for all referred users (subordinates)
        if ($referUserIds->isNotEmpty()) {
            $subordinatesDepositStats = DB::select("WITH RECURSIVE team_members AS (
    SELECT id
    FROM users
    WHERE referrer_id = $user->id  -- starting from the user whose team we're analyzing
    UNION
    SELECT u.id
    FROM users u
    JOIN team_members tm ON tm.id = u.referrer_id  -- recursively include all team members' downlines
)
SELECT COUNT(*) AS deposit_number, SUM(cash) AS deposit_amount
FROM payins
WHERE user_id IN (SELECT id FROM team_members)  -- consider deposits made by the entire team
AND DATE(created_at) = CURDATE() - INTERVAL 1 DAY
AND status = 2;
");

            // Sum the results across all referred users
            foreach ($subordinatesDepositStats as $stats) {
                $totalDepositNumber += $stats->deposit_number;
                $totalDepositAmount += $stats->deposit_amount;
            }

            // Get first deposit count for subordinates
            $totalFirstDepositCount = DB::table('payins')
                ->whereIn('user_id', $referUserIds)
                ->whereDate('created_at', $currentDate)
                ->count();
        }

        // Prepare the result
        $result = [
            'yesterday_total_commission' => $yesterdayTotalCommission ?? 0,
            'register' => $register,
            'deposit_number' => $depositStats->deposit_number ?? 0,
            'deposit_amount' => $depositStats->deposit_amount ?? 0,
            'first_deposit' => $firstDepositCount,
            'subordinates_register' => $subordinatesRegister,
            'subordinates_deposit_number' => $totalDepositNumber,
            'subordinates_deposit_amount' => $totalDepositAmount,
            'subordinates_first_deposit' => $totalFirstDepositCount,
            'direct_subordinate' => $directSubordinateCount,
            'total_commission' => $totalCommission,
            'weekly_commission' => 0, // Placeholder, modify as needed
            'team_subordinate' => $teamSubordinateCount,
            'referral_code' => $referralCode,
        ];

        return response()->json([
            'status' => 200,
            'message' => 'Data fetched successfully',
            'data' => $result
        ], 200);
        
    } catch (\Exception $e) {
        return response()->json([
            'error' => $e->getMessage()
        ], 500);
    }
}


	 public function new_subordinate(Request $request)
{
    try {
        // Validation
        $validator = Validator::make($request->all(), [
            'id' => 'required',
            'type' => 'required',
        ]);

        $validator->stopOnFirstFailure();

        if ($validator->fails()) {
            return response()->json([
                'status' => 400,
                'message' => $validator->errors()->first()
            ], 200);
        }

        // Find the user using Eloquent Model
        $user = User::findOrFail($request->id);

        // Get the current, yesterday's, start and end of month dates
        $currentDate = Carbon::now()->format('Y-m-d');
        $yesterdayDate = Carbon::yesterday()->format('Y-m-d');
        $startOfMonth = Carbon::now()->startOfMonth()->format('Y-m-d');
        $endOfMonth = Carbon::now()->endOfMonth()->format('Y-m-d');

        // Initialize the query for subordinates
        $query = User::select('mobile','u_id','commission', 'name', 'created_at')
            ->where('referrer_id', $user->id);
            
        switch ($request->type) {
            case 1:
                // Today's subordinates
                $query->whereDate('created_at', $currentDate);
                break;
            case 2:
                // Yesterday's subordinates
                $query->whereDate('created_at', $yesterdayDate);
                break;
            case 3:
                // Subordinates for this month
                $query->whereBetween('created_at', [$startOfMonth, $endOfMonth]);
                break;
            default:
                return response()->json(['status' => 400, 'message' => 'Invalid type provided'], 200);
        }
        $subordinate_data = $query->get();

        // Return success or error based on whether data exists
        if ($subordinate_data->isNotEmpty()) {
            return response()->json([
                'status' => 200,
                'message' => 'Successfully retrieved subordinates!',
                'data' => $subordinate_data
            ], 200);
        } else {
            return response()->json([
                'status' => 400,
                'message' => 'Data not found'
            ], 200);
        }
    } catch (\Exception $e) {
        // Handle any exceptions
        return response()->json(['error' => $e->getMessage()], 500);
    }
}
 
 public function tier(){
    try {
        // Fetch all levels using the MlmLevel model
        $tier = MlmLevel::select('id', 'name')->get();

        if ($tier->isNotEmpty()) {
            $response = [
                'status' => 200,
                'message' => 'Successfully..!', 
                'data' => $tier
            ];
            return response()->json($response, 200);
        } else {
            $response = [
                'status' => 400, 
                'message' => 'Data not found'
            ];
            return response()->json($response, 400);
        }

    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
}

public function subordinate_data(Request $request)
{
    try {
        // Validate the request
        $validator = Validator::make($request->all(), [
            'id' => 'required|integer',
            'tier' => 'required|integer|min:0',
        ]);

        // Stop on first failure
        $validator->stopOnFirstFailure();

        if ($validator->fails()) {
            return response()->json(['status' => 400, 'message' => $validator->errors()->first()], 400);
        }

        // Get input parameters
        $userId = $request->id;
        $tier = $request->tier;
        $searchUid = $request->u_id;
        $currentDate = $request->created_at ?: Carbon::now()->subDay()->format('Y-m-d');

        // Step 1: Initialize a collection to store subordinates
        $subordinates = collect();

        // Step 2: Get the initial users at level 1 (direct referrals)
        $currentLevelUsers = User::where('referrer_id', $userId)->get();
        $currentLevel = 1; // Start at level 1

        // Step 3: Iterate through each level to get subordinates up to the given tier
        while ($currentLevelUsers->isNotEmpty() && $currentLevel <= $tier) {
            // Merge current level users into the subordinates collection
            $subordinates = $subordinates->merge($currentLevelUsers);

            // Get the next level users (users referred by the current level users)
            $currentLevelUsers = User::whereIn('referrer_id', $currentLevelUsers->pluck('id'))->get();
            
            $currentLevel++; // Increment the level
        }

        // Get all subordinate user IDs
        $subordinateIds = $subordinates->pluck('id');

        // Step 4: If there is a search_uid, filter the subordinates by UID
        if (!empty($searchUid)) {
            $subordinateIds = User::whereIn('id', $subordinateIds)
                                  ->where('u_id', 'like', $searchUid . '%')
                                  ->pluck('id');
        }

        // Step 5: Fetch data for the filtered subordinates
        $subordinatesData = User::whereIn('id', $subordinateIds)
            ->with(['bets' => function ($query) use ($currentDate) {
                $query->whereDate('created_at', $currentDate);
            }, 'payins' => function ($query) use ($currentDate) {
                $query->whereDate('created_at', $currentDate)
                      ->where('status', 2);
            }, 'mlmLevel'])
            ->get();

        // Step 6: Initialize the result array
        $result = [
            'number_of_deposit' => 0,
            'payin_amount' => 0,
            'number_of_bettor' => 0,
            'bet_amount' => 0,
            'first_deposit' => 0,
            'first_deposit_amount' => 0,
            'subordinates_data' => [],
        ];

        // Step 7: Calculate data for each subordinate
        foreach ($subordinatesData as $user) {
            // Calculate bet amount and payin amount
            $betAmount = $user->bets->sum('amount');
            $payinAmount = $user->payins->sum('cash');
            $numberOfBettors = $user->bets->count();
            $commission = ($betAmount * optional($user->mlmLevel)->commission) / 100;

            // Add to result totals
            $result['bet_amount'] += $betAmount;
            $result['payin_amount'] += $payinAmount;
            $result['number_of_bettor'] += $numberOfBettors;

            // Add individual subordinate data to the result
            $result['subordinates_data'][] = [
                'id' => $user->id,
                'u_id' => $user->u_id,
                'bet_amount' => $betAmount,
                'payin_amount' => $payinAmount,
                'commission' => $commission,
            ];
        }

        // Step 8: Return the result as JSON
        return response()->json(['status' => 200,'message' => 'data fetch successfully','data' =>$result], 200);

    } catch (\Exception $e) {
        // Return error message in case of exception
        return response()->json(['error' => $e->getMessage()], 500);
    }
}

 
	
	public function turnover_new_old()
{
    // Get current datetime and the previous day (yesterday's date)
    //$datetime = Carbon::now();
    $currentDate = Carbon::now('Asia/Kolkata')->subDay()->format('Y-m-d');

    // Reset yesterday's total commission to 0 for all users
    DB::table('users')->update(['yesterday_total_commission' => 0]);

    // Get all users who have a referrer_id
    $referralUsers = DB::table('users')->whereNotNull('referrer_id')->get();
		
    $referralUsersCount = $referralUsers->count();
		//dd($referralUsersCount);

    if ($referralUsersCount > 0) {
        // Loop through each referral user
        foreach ($referralUsers as $referralUser) {
			//print_r($referralUser);
            $user_id = $referralUser->id;
			dd($user_id);
			//echo $user_id; 
            $maxTier = 7;
			

            // Get subordinate data with the recursive CTE query
            $subordinatesData = \DB::select("
                WITH RECURSIVE subordinates AS (
                    SELECT id, referrer_id, 1 AS level
                    FROM users
                    WHERE referrer_id = ?
                    UNION ALL
                    SELECT u.id, u.referrer_id, s.level + 1
                    FROM users u
                    INNER JOIN subordinates s ON s.id = u.referrer_id
                    WHERE s.level + 1 <= ?
                )
                SELECT 
                    users.id, 
                    subordinates.level,
                    COALESCE(SUM(bet_summary.total_bet_amount), 0) AS bet_amount,
                    COALESCE(SUM(bet_summary.total_bet_amount), 0) * COALESCE(level_commissions.commission, 0) / 100 AS commission
                FROM users
                LEFT JOIN (
                    SELECT userid, SUM(amount) AS total_bet_amount 
                    FROM bets 
                    WHERE created_at LIKE ?
                    GROUP BY userid
                ) AS bet_summary ON users.id = bet_summary.userid 
                LEFT JOIN subordinates ON users.id = subordinates.id
                LEFT JOIN (
                    SELECT id, commission
                    FROM mlm_levels
                ) AS level_commissions ON subordinates.level = level_commissions.id
                WHERE subordinates.level <= ?
                GROUP BY users.id, subordinates.level, level_commissions.commission;
            ", [ 8, $maxTier, $currentDate . '%', $maxTier]);
            return $subordinatesData;
            // Calculate total commission
            $totalCommission = 0;
            foreach ($subordinatesData as $data) {
                $totalCommission += $data->commission;
            }

            // Update the user's wallet, recharge, commission, yesterday_total_commission fields
            DB::table('users')->where('id', $user_id)->update([
                'wallet' => DB::raw('wallet + ' . $totalCommission),  
                'recharge' => DB::raw('recharge + ' . $totalCommission),  
                'commission' => DB::raw('commission + ' . $totalCommission),  
                'yesterday_total_commission' => $totalCommission,  
                'updated_at' => $datetime,  
            ]);  

            // Insert into wallet_histories to log the commission
            DB::table('wallet_histories')->insert([
                'user_id' => $user_id,
                'amount' => $totalCommission,
                'type_id' => 23,  // Assuming type 23 is for commission-related transactions
                'created_at' => $datetime,
                'updated_at' => $datetime,
            ]);
        }

        // Once done with all referral users, return success message
        return response()->json(['message' => 'Turnover commission calculated successfully.'], 200);
    } else {
        // No referral users found
        return response()->json(['message' => 'No referral users found.'], 400);
    }
}
	
	public function turnover_new()
{
    $currentDate = Carbon::now('Asia/Kolkata')->subDay()->format('Y-m-d');
    $datetime = Carbon::now('Asia/Kolkata')->toDateTimeString();

    // Reset yesterday's total commission to 0 for all users
    DB::table('users')->update(['yesterday_total_commission' => 0]);

    // Get all users who have a referrer_id
    $referralUsers = DB::table('users')->whereNotNull('referrer_id')->get();

    if ($referralUsers->isEmpty()) {
        return response()->json(['message' => 'No referral users found.'], 400);
    }

    $maxTier = 7;

    foreach ($referralUsers as $referralUser) {
        $user_id = $referralUser->id;

        // Get subordinate data with the recursive CTE query
        $subordinatesData = DB::select("
            WITH RECURSIVE subordinates AS (
                SELECT id, referrer_id, 1 AS level
                FROM users
                WHERE referrer_id = ?
                UNION ALL
                SELECT u.id, u.referrer_id, s.level + 1
                FROM users u
                INNER JOIN subordinates s ON s.id = u.referrer_id
                WHERE s.level + 1 <= ?
            )
            SELECT 
                users.id, 
                subordinates.level,
                COALESCE(SUM(bet_summary.total_bet_amount), 0) AS bet_amount,
                COALESCE(SUM(bet_summary.total_bet_amount), 0) * COALESCE(level_commissions.commission, 0) / 100 AS commission
            FROM users
            LEFT JOIN (
                SELECT userid, SUM(amount) AS total_bet_amount 
                FROM bets 
                WHERE created_at LIKE ?
                GROUP BY userid
            ) AS bet_summary ON users.id = bet_summary.userid 
            LEFT JOIN subordinates ON users.id = subordinates.id
            LEFT JOIN (
                SELECT id, commission
                FROM mlm_levels
            ) AS level_commissions ON subordinates.level = level_commissions.id
            WHERE subordinates.level <= ?
            GROUP BY users.id, subordinates.level, level_commissions.commission;
        ", [$user_id, $maxTier, $currentDate . '%', $maxTier]);

        // Calculate total commission
        $totalCommission = array_sum(array_column($subordinatesData, 'commission'));

        if ($totalCommission > 0) {
            // Update the user's wallet, recharge, commission, and yesterday's total commission
            DB::table('users')->where('id', $user_id)->update([
                'wallet' => DB::raw("wallet + $totalCommission"),  
                'recharge' => DB::raw("recharge + $totalCommission"),  
                'commission' => DB::raw("commission + $totalCommission"),  
                'yesterday_total_commission' => $totalCommission,  
                'updated_at' => $datetime,  
            ]);

            // Insert into wallet_histories to log the commission
            DB::table('wallet_histories')->insert([
                'user_id' => $user_id,
                'amount' => $totalCommission,
                'type_id' => 23,
                'created_at' => $datetime,
                'updated_at' => $datetime,
            ]);
        }
    }

    return response()->json(['message' => 'Turnover commission calculated successfully.'], 200);
}

	

}