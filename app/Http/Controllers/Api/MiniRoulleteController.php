<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class MiniRoulleteController extends Controller
{
    public function mini_bets(Request $request)
    {
    $validator = Validator::make($request->all(), [
        'userid' => 'required|exists:users,id',
        'game_id' => 'required|exists:betlogs,game_id',
        'bet' => 'required|array|min:1',
    ]);

    if ($validator->fails()) {
        return response()->json(['status' => 400, 'message' => $validator->errors()->first()]);
    }

    $userid = $request->userid;
    $gameid = $request->game_id;
    $cardNumberAmount = $request->bet;

    date_default_timezone_set('Asia/Kolkata');
    $datetime = date('Y-m-d H:i:s');
    $orderid = date('YmdHis') . rand(11111, 99999);
    $gamesno = DB::table('betlogs')->where('game_id', $gameid)->value('games_no');
    $userWallet = DB::table('users')->where('id', $userid)->value('wallet');
    $totalBetAmount = array_sum(array_column($cardNumberAmount, 'amount'));

    if ($userWallet < $totalBetAmount) {
        return response()->json(['status' => 400, 'message' => 'Insufficient balance']);
    }
    $validCardNumbers = DB::table('mini_roulette_multiplier')->pluck('id')->toArray();
    foreach ($cardNumberAmount as $bet) {
        if (!isset($bet['number']) || !is_numeric($bet['number']) || !in_array($bet['number'], $validCardNumbers)) {
            return response()->json(['status' => 400, 'message' => 'Invalid number. It must match an ID from mini_roulette_multiplier table.']);
        }
    }
    $multiplierData = DB::table('mini_roulette_multiplier')
        ->whereIn('id', array_column($cardNumberAmount, 'number')) 
        ->pluck('multiplier', 'id');
    DB::beginTransaction();
    try {
        DB::table('mini_roulette_bets')->insert([
            'bets' => json_encode($cardNumberAmount),
            'total_amount' => $totalBetAmount,
            'number' => json_encode(array_column($cardNumberAmount, 'number')),
            'games_no' => $gamesno,
            'game_id' => $gameid,
            'userid' => $userid,
            'order_id' => $orderid,
            'created_at' => $datetime,
            'updated_at' => $datetime,
            'status' => 0
        ]);

        foreach ($cardNumberAmount as $bet) {
            $card = $bet['number'];
            $betAmount = $bet['amount'];

            if (isset($multiplierData[$card])) {
                $multiplier = $multiplierData[$card];
                DB::table('betlogs')
                    ->where('game_id', $gameid)
                    ->increment('amount', $betAmount * $multiplier);
            }
        }

        DB::table('users')->where('id', $userid)->decrement('wallet', $totalBetAmount);

        DB::commit();
        return response()->json(['status' => 200, 'message' => 'Bet Successfully']);
    } catch (\Exception $e) {
        DB::rollback();
        return response()->json(['status' => 500, 'message' => 'Something went wrong', 'error' => $e->getMessage()]);
    }
}
    public function mini_cron($game_id)
    {
        $per = DB::table('game_settings')->where('id', $game_id)->value('winning_percentage');
        if (!$per) {
            return response()->json(['error' => 'Invalid game ID'], 400);
        }
        $game_no = DB::table('betlogs')->where('game_id', $game_id)->value('games_no');
        if (!$game_no) {
            return response()->json(['error' => 'No game found in betlogs'], 400);
        }
        $random_card = DB::table('mini_roulette_multiplier')->inRandomOrder()->first();
        if (!$random_card) {
            return response()->json(['error' => 'No card found in mini_roullete_multiplier'], 400);
        }
    
        $result_card_type = $random_card->number;
        $multiplier = $random_card->multiplier;
        $result_card = $random_card->id;

        DB::table('mini_roulette_result')->insert([
            'games_no' => $game_no,
            'game_id' => $game_id,
            'result_number' => $result_card_type,
            'type' => $result_card,
            'multiplier' => $multiplier,
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now()
        ]);
        $this->amountDistributor($game_id,$game_no);
        
        DB::table('betlogs')->where('game_id', $game_id)->update([
            'amount' => 0,
            'games_no' => DB::raw('games_no + 1'),
        ]);
    
        return true;
    }
    private function amountDistributor($game_id, $game_no)
    {
    $result = DB::table('mini_roulette_result')
        ->where('game_id', $game_id)
        ->latest('id')
        ->first();

    if (!$result) {
        return;
    }

    $result_card = $result->result_number; 
    $multiplier = $result->multiplier;

    $bets = DB::table('mini_roulette_bets')
        ->where('game_id', $game_id)
        ->where('games_no', $game_no)
        ->get();

    foreach ($bets as $bet) {
        $betData = json_decode($bet->bets, true);
        $win_amount = 0;
        $is_winner = false;

        if (!is_array($betData)) {
            continue;
        }

        foreach ($betData as $singleBet) {
            if (isset($singleBet['win_number']) && $singleBet['win_number'] == $result_card) {
                $win_amount += $singleBet['amount'] * $multiplier;
                $is_winner = true;
            }
        }

        $status = $is_winner ? 1 : 3;
        DB::table('mini_roulette_bets')
            ->where('id', $bet->id)
            ->update([
                'status' => $status,
                'win_amount' => $win_amount,
                'win_number' => $result_card, 
                'updated_at' => now()
            ]);

        if ($is_winner) {
            DB::table('users')
                ->where('id', $bet->user_id)
                ->increment('winning_wallet', $win_amount);
        }
    }
}


    public function mini_results(Request $request)
    {
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
        $where[] = "mini_roulette_result.game_id = '$game_id'";
    }
    if (!empty($from_date) && !empty($to_date)) {
        $where[] = "mini_roulette_result.created_at BETWEEN '$from_date' AND '$to_date'";
        }
        $query = "SELECT mini_roulette_result.*, virtual_games.name AS game_name,virtual_games.number AS game_number, virtual_games.game_id AS game_gameid,game_settings.name AS game_setting_name FROM mini_roulette_result
        LEFT JOIN virtual_games ON mini_roulette_result.game_id = virtual_games.game_id JOIN game_settings ON mini_roulette_result.game_id = game_settings.id ";
        if (!empty($where)) {
            $query .= " WHERE " . implode(" AND ", $where);
        }
        $query .= " ORDER BY mini_roulette_result.id DESC LIMIT $offset,$limit";
        $results = DB::select($query);
        return response()->json([
            'status' => 200,
            'message' => 'Data found',
            'data' => $results
        ]);
    }
    }
    private function type($type){
        $type = (int) $type;
        return match($type){
            1 => 'Spades',
            2 => 'Suit',
            3 => 'Color',
            4 => 'Andar_bahar',
            5 => '3_card',
        };
    }
    public function mini_multiplier()
    {
        
        $andarBaharCards = DB::table('cards')
            ->whereBetween('id', [1, 52])
            ->get();
    
        
        $redCards = DB::table('cards')
            ->whereIn('color', ['hearts', 'diamonds']) 
            ->get();
    
        $blackCards = DB::table('cards')
            ->whereIn('color', ['spades', 'clubs']) 
            ->get();
    
        return response()->json([
            'andar_bahar' => $andarBaharCards,
            'red_black' => [
                'red' => $redCards,
                'black' => $blackCards
            ]
        ]);
    }
    public function bet_history(Request $request)
    {
    $validator = Validator::make($request->all(), [
        'userid' => 'required',
        'game_id' => 'required',
    ]);

    $validator->stopOnFirstFailure();

    if ($validator->fails()) {
        return response()->json(['status' => 400, 'message' => $validator->errors()->first()]);
    }

    $userid = $request->userid;  
    $game_id = $request->game_id;
    $limit = $request->limit ?? 10000;
    $offset = $request->offset ?? 0;
    $from_date = $request->from_date;
    $to_date = $request->to_date;

    $query = "SELECT DISTINCT mini_roulette_bets.*, game_settings.name AS game_name
              FROM mini_roulette_bets
              LEFT JOIN game_settings ON game_settings.id = mini_roulette_bets.game_id 
              LEFT JOIN virtual_games ON virtual_games.game_id = mini_roulette_bets.game_id 
              AND virtual_games.number = mini_roulette_bets.number 
              WHERE mini_roulette_bets.userid = ? AND mini_roulette_bets.game_id = ?";

    $bindings = [$userid, $game_id];

    if (!empty($from_date) && !empty($to_date)) {
        $query .= " AND mini_roulette_bets.created_at BETWEEN ? AND ?";
        array_push($bindings, $from_date, $to_date);
    }

    $query .= " ORDER BY mini_roulette_bets.id DESC LIMIT ? OFFSET ?";
    array_push($bindings, $limit, $offset);
// dd($query);
    $results = DB::select($query, $bindings);
// dd($results);
    $bets = DB::select("SELECT userid, COUNT(*) AS total_bets FROM mini_roulette_bets WHERE userid = ? GROUP BY userid", [$userid]);

    $total_bet = isset($bets[0]) ? $bets[0]->total_bets : 0;

        if (!empty($results)) {
            return response()->json([
                'status' => 200,
                'message' => 'Data found',
                'total_bets' => $total_bet,
                'data' => $results
            ]);
        } else {
            return response()->json([
                'status' => 400,
                'message' => 'No Data found',
                'data' => []
            ]);
        }

    }
    public function win_amount(Request $request)
{
    $validator = Validator::make($request->all(), [ 
        'userid' => 'required|integer',
        'game_id' => 'required|integer',
        'games_no' => 'required|integer'
    ]);

    $validator->stopOnFirstFailure();

    if ($validator->fails()) {
        return response()->json(['status' => 400, 'message' => $validator->errors()->first()]);
    }

    $game_id = $request->game_id;
    $userid = $request->userid;
    $game_no = $request->games_no;

    // Get the winning result for the given game_id and gamesno
    $resultData = DB::table('mini_roulette_result')
        ->where('game_id', $game_id)
        ->where('games_no', $game_no)
        ->first();

    if (!$resultData || empty($resultData->result_number)) {
        return response()->json(['status' => 400, 'message' => 'Winning numbers not found.'], 400);
    }

    $winningNumbers = $resultData->result_number;

    // Convert to an array if needed
    if (strpos($winningNumbers, ',') !== false) {
        $winningNumbers = array_map('trim', explode(',', $winningNumbers));
    } else {
        $winningNumbers = [$winningNumbers];
    }

    // Fetch user's betting details
    $win_amount = DB::table('mini_roulette_bets')
        ->where('games_no', $game_no)
        ->where('game_id', $game_id)
        ->where('userid', $userid)
        ->selectRaw('SUM(win_amount) as total_amount, games_no, game_id as gameid, win_number')
        ->groupBy('games_no', 'game_id', 'win_number')
        ->first();

    if ($win_amount) {
        $totalWinAmount = $win_amount->win_amount ?? 0;
        $winNumber = $win_amount->win_number;

        return response()->json([
            'message' => 'Successfully fetched win details',
            'status' => 200,
            'win_number' => $winNumber, 
            'win_amount' => $totalWinAmount, 
            'games_no' => $win_amount->games_no,
            'result' => $totalWinAmount > 0 ? 'win' : 'lose', 
            'gameid' => $win_amount->gameid,
        ], 200);
    }

    return response()->json(['message' => 'No record found', 'status' => 400], 400);
}


}