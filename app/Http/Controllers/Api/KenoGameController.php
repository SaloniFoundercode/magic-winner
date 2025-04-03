<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Validator;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class KenoGameController extends Controller
{
    public function bets(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'userid' => 'required|exists:users,id',
            'game_id' => 'required',
            'risk_level' => 'required|in:1,2,3',
            'selected_numbers' => 'required|array|min:1|max:10',
            'bet_amount' => 'required|numeric|min:10'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 400, 'message' => $validator->errors()->first()], 400);
        }

        $userid = $request->userid;
        $gameid = $request->game_id;
        $riskLevel = $request->risk_level;
        $selectedNumbers = $request->selected_numbers;
        $betAmount = (float) $request->bet_amount;
        $gamesrno = DB::table('betlogs')->where('game_id', $gameid)->value('games_no');
        $datetime = now();
        $orderid = date('YmdHis') . rand(11111, 99999);

        $riskLevelString = $this->mapRiskLevel($riskLevel);

        $userWallet = (float) DB::table('users')->where('id', $userid)->value('wallet');
        if ($userWallet < $betAmount) {
            return response()->json(['status' => 400, 'message' => 'Insufficient balance'], 400);
        }

        $multipliers = DB::table('keno_multipliers')
            ->where('risk_level', $riskLevelString)
            ->where('selections', count($selectedNumbers))
            ->value('multipliers');

        DB::table('users')->where('id', $userid)->update([
            'wallet' => DB::raw("GREATEST(wallet - {$betAmount}, 0)")
        ]);

        DB::table('keno_bet')->insert([
            'userid' => $userid,
            'game_id' => $gameid,
            'games_no' => $gamesrno,
            'amount' => $betAmount,
            'selected_numbers' => json_encode($selectedNumbers),
            'risk_level' => $riskLevel,
            'win_amount' => 0,
            'status' => 0,
            'created_at' => $datetime,
            'updated_at' => $datetime
        ]);

        return response()->json(['status' => 200, 'message' => 'Bet successfully placed']);
    }
    private function mapRiskLevel($riskLevel)
{
    $riskLevel = (int) $riskLevel; // Ensure it's an integer

    return match ($riskLevel) {
        1 => 'low',
        2 => 'medium',
        3 => 'high',
        default => 'low'
    };
}
    public function keno_cron($game_id)
{
    $per = DB::select("SELECT winning_percentage FROM game_settings WHERE id = ?", [$game_id]);
    if (empty($per)) {
        return response()->json(['error' => 'Invalid game ID'], 400);
    }
    $percentage = $per[0]->winning_percentage;

    $gameno = DB::select("SELECT games_no FROM betlogs WHERE game_id = ? LIMIT 1", [$game_id]);
    if (empty($gameno)) {
        return response()->json(['error' => 'No game found in betlogs'], 400);
    }
    $game_no = $gameno[0]->games_no;
    $period = $game_no;

    $sumamt = DB::select("SELECT SUM(amount) AS amount FROM keno_bet WHERE game_id = ? AND games_no = ?", [$game_id, $period]);
    $totalamount = $sumamt[0]->amount ?? 0;
    $percentageamount = $totalamount * ($percentage / 100);

    $lessamount = DB::select("SELECT number FROM betlogs WHERE game_id = ? AND games_no = ? AND amount <= ? ORDER BY amount ASC LIMIT 1", [$game_id, $period, $percentageamount]);
    if (empty($lessamount)) {
        $lessamount = DB::select("SELECT number FROM betlogs WHERE game_id = ? AND games_no = ? AND amount >= ? ORDER BY amount ASC LIMIT 1", [$game_id, $game_no, $percentageamount]);
    }

    $admin_winner = DB::select("SELECT number FROM admin_winner_results WHERE games_no = ? AND gameId = ? ORDER BY id DESC LIMIT 1", [$game_no, $game_id]);

    // Generate 10 random unique numbers between 1-40
    $numbers = range(1, 40);
    shuffle($numbers);
    $selected_numbers = array_slice($numbers, 0, 10); // Select only 10 numbers

    if (!empty($admin_winner)) {
        $res = json_decode($admin_winner[0]->number, true);
    } else {
        $res = $selected_numbers; // Store as array
    }

    // Insert result in keno_bet_result table
    DB::insert("INSERT INTO keno_bet_result (number, games_no, game_id, status) VALUES (?, ?, ?, ?)", [
        json_encode($res), $period, $game_id, 1
    ]);

    $this->amountdistributioncolors($game_id, $period, $res);

    DB::update("UPDATE betlogs SET amount = 0, games_no = games_no + 1 WHERE game_id = ?", [$game_id]);

    return true;
}

    private function amountdistributioncolors($game_id, $period, $winningNumbers)
{
    // Ensure $winningNumbers is an array
    if (!is_array($winningNumbers)) {
        $winningNumbers = json_decode($winningNumbers, true);
    }

    // Fetch all bets for the game and round
    $bets = DB::table('keno_bet')
        ->where('game_id', $game_id)
        ->where('games_no', $period)
        ->where('status', 0)
        ->get();

    foreach ($bets as $bet) {
        $userId = $bet->userid;
        $betAmount = $bet->amount;
        $riskLevel = $bet->risk_level;
        $selectedNumbers = json_decode($bet->selected_numbers, true);

        // Find matched numbers
        $matchedNumbers = array_intersect($selectedNumbers, $winningNumbers);
        $matchedCount = count($matchedNumbers);

        // Determine which multiplier index to use
        $multiplierIndex = $matchedCount > 0 ? $matchedCount - 1 : 0; // If no match, use index 0

        // Get the multipliers based on the risk level and selections
        $multiplierData = DB::table('keno_multipliers')
            ->where('risk_level', $this->mapRiskLevel($riskLevel))
            ->where('selections', count($selectedNumbers))
            ->value('multipliers');

        if ($multiplierData) {
            $multiplierList = json_decode($multiplierData, true);

            if (is_array($multiplierList)) {
                // If no matches, use multiplier at index 0 for a loss
                if ($matchedCount == 0) {
                    $appliedMultiplier = (float) str_replace('x', '', $multiplierList[0]);
                    $winAmount = $betAmount * $appliedMultiplier;

                    // Update user's wallet (even if they lost, they get multiplier at index 0)
                    DB::table('users')->where('id', $userId)->update([
                        'winning_wallet' => DB::raw("wallet + $winAmount")
                    ]);

                    // Update bet status as lost (0 matched numbers)
                    DB::table('keno_bet')->where('id', $bet->id)->update([
                        'win_amount' => $winAmount,
                        'status' => 2, // Mark as lost
                        'updated_at' => now()
                    ]);
                } else {
                    // If matched count is greater than 0, use multiplier based on matched count
                        if (isset($multiplierList[$multiplierIndex])) {
                        $appliedMultiplier = (float) str_replace('x', '', $multiplierList[$multiplierIndex]);
                        $winAmount = $betAmount * $appliedMultiplier;

                        // Update user's wallet
                        DB::table('users')->where('id', $userId)->update([
                            'winning_wallet' => DB::raw("wallet + $winAmount")
                        ]);

                        // Update bet status as won
                        DB::table('keno_bet')->where('id', $bet->id)->update([
                            'win_amount' => $winAmount,
                            'status' => 1, // Mark as won
                            'updated_at' => now()
                        ]);
                         }
                    }
                }
        }
    }
}


    public function keno_result(Request $request)
    {
    $validator = Validator::make($request->all(), [
        'game_id' => 'required',
        'limit' => 'required'
    ]);

    $validator->stopOnFirstFailure();

    if ($validator->fails()) {
        return response()->json(['status' => 400, 'message' => $validator->errors()->first()]);
    }
    
    $game_id = $request->game_id;
    $limit = $request->limit;
     $offset = $request->offset ?? 0;
    $from_date = $request->created_at;
    $to_date = $request->created_at;
    $status = $request->status;

    $where = [];

    if (!empty($game_id)) {
        $where[] = "keno_bet_result.game_id = '$game_id'";
    }

    if (!empty($from_date) && !empty($to_date)) {
        $where[] = "keno_bet_result.created_at BETWEEN '$from_date' AND '$to_date'";
        }
        $query = "SELECT keno_bet_result.*, virtual_games.name AS game_name,virtual_games.number AS game_number, virtual_games.game_id AS game_gameid,game_settings.name AS game_setting_name FROM keno_bet_result
    LEFT JOIN virtual_games ON keno_bet_result.game_id = virtual_games.game_id && keno_bet_result.number=virtual_games.number JOIN game_settings ON keno_bet_result.game_id = game_settings.id ";
    
        if (!empty($where)) {
            $query .= " WHERE " . implode(" AND ", $where);
        }
    
        $query .= " ORDER BY keno_bet_result.id DESC LIMIT $offset,$limit";
    
        $results = DB::select($query);
         
       // $daata=json_encode($results);
    
        return response()->json([
            'status' => 200,
            'message' => 'Data found',
            'data' => $results
        ]);
    }
    public function bet_history(Request $request)
    {
    	$validator = Validator::make($request->all(), [
            'userid'=>'required',
    		'game_id' => 'required',
           //'limit' => 'required'
           	]);
		
    $validator->stopOnFirstFailure();

    if ($validator->fails()) {
        return response()->json(['status' => 400, 'message' => $validator->errors()->first()]);
    }
	
	$userid = $request->userid;
    $game_id = $request->game_id;
    $limit = $request->limit ?? 10000;
    $offset = $request->offset ?? 0;
	$from_date = $request->created_at;
	$to_date = $request->created_at;
	
	if (!empty($game_id)) {
    $where['keno_bet.game_id'] = "$game_id";
    $where['keno_bet.userid'] = "$userid";
    }
    
    
    if (!empty($from_date)) {
        
           $where['keno_bet.created_at']="$from_date%";
      $where['keno_bet.created_at']="$to_date%";
    }
    
    $query = " SELECT DISTINCT keno_bet.*, game_settings.name AS game_name, virtual_games.name AS name 
    FROM keno_bet
    LEFT JOIN game_settings ON game_settings.id = keno_bet.game_id 
    LEFT JOIN virtual_games ON virtual_games.game_id = keno_bet.game_id AND virtual_games.number = keno_bet.number" ;
    
    if (!empty($where)) {
        $query .= " WHERE " . implode(" AND ", array_map(function ($key, $value) {
            return "$key = '$value'";
        }, array_keys($where), $where));
    }
    
     $query .= " ORDER BY  keno_bet.id DESC  LIMIT $offset , $limit";
    
    $results = DB::select($query);
    $bets=DB::select("SELECT userid, COUNT(*) AS total_bets FROM keno_bet WHERE `userid`=$userid GROUP BY userid
    ");
    		if (isset($bets[0])) {
        $total_bet = $bets[0]->total_bets;
    } else {
        $total_bet = 0; 
    }
    
    if(!empty($results)){
    		 return response()->json([
                'status' => 200,
                'message' => 'Data found',
                'total_bets' => $total_bet,
                'data' => $results
                
            ]);
             return response()->json($response,200);
    }else{
        
        $response = [
        'status' => 400,
        'message' => 'No Data found',
        'data' => $results
    ];
    
        return response()
        ->json($response, $response['status']);
                 
        }

    }
    public function keno_multipliers(Request $request)
    {
        $risk_level_id = $request->risk_level;
        $selected_numbers = $request->selections;
    
        if (empty($selected_numbers) || !is_array($selected_numbers)) {
            return response()->json([
                'status' => false,
                'message' => 'Please select at least one valid number.'
            ]);
        }
    
        // Ensure risk level is mapped correctly
        $risk_level = $this->mapRiskLevel($risk_level_id);
        if (!$risk_level) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid risk level.'
            ]);
        }
    
        $multiplier_data = DB::table('keno_multipliers')
            ->where('risk_level', $risk_level)
            ->where('selections', count($selected_numbers))
            ->value('multipliers');
    
        if (!$multiplier_data) {
            return response()->json([
                'status' => false,
                'message' => "No multiplier found for {$risk_level} risk level and " . count($selected_numbers) . " selections."
            ]);
        }
    
        // Decode JSON multipliers
        $decoded_multiplier = json_decode($multiplier_data, true);
    
        // Validate JSON format
        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::error("Invalid JSON format in database for selection count " . count($selected_numbers) . ": " . $multiplier_data);
            return response()->json([
                'status' => false,
                'message' => 'Invalid JSON format in database.'
            ]);
        }
    
        return response()->json([
            'status' => true,
            'message' => "Multiplier fetched successfully for {$risk_level} risk level.",
            'multipliers' => $decoded_multiplier
        ]);
    }

    public function keno_win_amount(Request $request)
{
    $validator = Validator::make($request->all(), [ 
        'userid' => 'required',
        'game_id' => 'required',
        'games_no' => 'required'
    ]);

    $validator->stopOnFirstFailure();

    if ($validator->fails()) {
        return response()->json(['status' => 400, 'message' => $validator->errors()->first()]);
    }

    // Get the input data
    $game_id = $request->game_id;
    $userid = $request->userid;
    $game_no = $request->games_no;

    $winningNumbers = DB::table('keno_bet_result')
        ->where('game_id', $game_id)
        ->where('games_no', $game_no)
        ->value('number'); 

    if (!$winningNumbers) {
        return response()->json(['status' => 400, 'message' => 'Winning numbers not found.'], 400);
    }

    $winningNumbers = json_decode($winningNumbers, true);

    $win_amount = DB::Select("
    SELECT SUM(`win_amount`) AS total_amount, 
           `amount`, 
           `games_no`, 
           `game_id` AS gameid, 
           `selected_numbers`
    FROM `keno_bet` 
    WHERE `games_no` = $game_no 
      AND `game_id` = $game_id 
      AND `userid` = $userid 
    GROUP BY `games_no`, `game_id`, `selected_numbers`, `amount`
");

    if ($win_amount) {
        $totalWinAmount = $win_amount[0]->total_amount;
        $selectedNumbers = json_decode($win_amount[0]->selected_numbers, true);

        $matchedNumbers = array_intersect($selectedNumbers, $winningNumbers);

        // Prepare the result message
        $response = [
            'message' => 'Successfully fetched win details',
            'status' => 200,
            'win' => $totalWinAmount,
            'amount' => $win_amount[0]->amount,
            'games_no' => $win_amount[0]->games_no,
            'result' => $totalWinAmount > 0 ? 'win' : 'lose', 
            'gameid' => $win_amount[0]->gameid,
            'number' => $matchedNumbers, 
        ];

        return response()->json($response, 200);
    } else {
        return response()->json(['msg' => 'No record found', 'status' => 400], 400);
    }
}

}
