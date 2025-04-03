<?php


namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Validator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class HeadTailController extends Controller
{
    public function headtail_bet(Request $request)
    {
    $validator = Validator::make($request->all(), [
        'userid' => 'required',
        'game_id' => 'required',
        'json' => 'required|array'
    ]);
    $validator->stopOnFirstFailure();
    if ($validator->fails()) {
        return response()->json(['status' => 400, 'message' => $validator->errors()->first()]);
    }
    $testData = $request->json;
    $userid = $request->userid;
    $gameid = $request->game_id;
    $gamesrno = DB::select("SELECT games_no FROM `betlogs` WHERE `game_id` = ? LIMIT 1", [$gameid]);
    if (empty($gamesrno)) {
        return response()->json(['status' => 400, 'message' => 'Invalid game ID']);
    }
    $gamesno = $gamesrno[0]->games_no;
    $user_wallet = DB::table('users')->select('wallet')->where('id', $userid)->first();
    if (!$user_wallet) {
        return response()->json(['status' => 400, 'message' => 'Invalid User ID']);
    }
    $userwallet = $user_wallet->wallet;
    foreach ($testData as $item) {
        $number = $item['number'];
        $amount = $item['amount'];

        // Check balance
        if ($userwallet < $amount) {
            return response()->json([
                'status' => 400,
                'message' => 'Insufficient balance'
            ]);
        }
        if ($amount >= 1) {
            $now = now();
            DB::insert("INSERT INTO `bets` (`amount`, `number`, `games_no`, `game_id`, `userid`, `status`, `created_at`, `updated_at`) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)", [$amount,$number,$gamesno,$gameid,$userid,0,$now,$now]);
            $data1 = DB::select("SELECT * FROM virtual_games WHERE virtual_games.number = ?", [$number]);
            foreach ($data1 as $row) {
                $multiplier = $row->multiplier;
                $num = $row->actual_number;
                $multiply_amt = $amount * $multiplier;
                DB::update("UPDATE `betlogs` SET `amount` = amount + ? WHERE game_id = ? AND number = ?", [
                    $multiply_amt,
                    $gameid,
                    $num
                ]);
            }
            DB::table('users')->where('id', $userid)->update([
                'wallet' => DB::raw("wallet - $amount")
            ]);
            $userwallet -= $amount;
            }
        }
        return response()->json([
            'status' => 200,
            'message' => 'Bet Successfully',
        ]);
    }
    public function headtail_cron($game_id)
    {
        $per=DB::select("SELECT game_settings.winning_percentage as winning_percentage FROM game_settings WHERE game_settings.id=$game_id");
        $percentage = $per[0]->winning_percentage;  
        $gameno=DB::select("SELECT * FROM betlogs WHERE game_id=$game_id LIMIT 1");
        $game_no=$gameno[0]->games_no;
        $period=$game_no;
        $sumamt=DB::select("SELECT SUM(amount) AS amount FROM bets WHERE game_id = '$game_id' && games_no='$game_no'");
        $totalamount=$sumamt[0]->amount;
        $percentageamount = $totalamount*$percentage*0.01; 
        $lessamount=DB::select(" SELECT * FROM betlogs WHERE game_id = '$game_id'  && games_no='$game_no' && amount <= $percentageamount ORDER BY amount asc LIMIT 1 ");
    		if(count($lessamount)==0){
    		$lessamount=DB::select(" SELECT * FROM betlogs WHERE game_id = '$game_id'  && games_no='$game_no' && amount >= '$percentageamount' ORDER BY amount asc LIMIT 1 ");
    		}
        $zeroamount=DB::select(" SELECT * FROM betlogs WHERE game_id =  '$game_id'  && games_no='$game_no' && amount=0 ORDER BY RAND() LIMIT 1 ");
        $admin_winner=DB::select("SELECT * FROM admin_winner_results WHERE games_no = '$game_no' AND gameId = '$game_id' ORDER BY id DESC LIMIT 1");
        $min_max=DB::select("SELECT min(number) as mins,max(number) as maxs FROM betlogs WHERE game_id=$game_id;");
            if(!empty($admin_winner)){
                echo 'a ';
                $number=$admin_winner[0]->number;
            }
            if (!empty($admin_winner)) {
                echo 'b ';
                $res=$number;
            } 
             elseif ( $totalamount< 450) {
                 echo 'c ';
                $res= rand($min_max[0]->mins, $min_max[0]->maxs);
            }elseif($totalamount > 450){
                echo 'd ';
                $res=$lessamount[0]->number;
            }
            $result=$res;
    		if ($game_id == 20) {
        $this->resultannounce($game_id, $period, $result);
        } elseif($game_id==18){
            $this->red_black($game_id,$period,$result);
        }
    }
    private function resultannounce($game_id,$period,$result)
    {
        $data=[];
        if($game_id==1){
            if($result==1){
            $rand=rand(2,13);
            $cards1=DB::select("SELECT `card`, `colour`, `image`  FROM `cards` where card >$rand order by rand(id) LIMIT 1")[0]->image;
            $rand2=rand(2,$rand-2);
            $cards2=DB::select("SELECT `card`, `colour`, `image`  FROM `cards` where card >$rand2 order by rand(id) LIMIT 1")[0]->image;
            $data=[$cards1,$cards2];
            // dd($data);
            }elseif($game_id==2){
                 $rand=rand(2,13);
            $cards2=DB::select("SELECT `card`, `colour`, `image`  FROM `cards` where card >$rand order by rand(id) LIMIT 1")[0]->image;
            $rand2=    rand(2,$rand-2);
            $cards1=DB::select("SELECT `card`, `colour`, `image`  FROM `cards` where card >$rand2 order by rand(id) LIMIT 1")[0]->image;
                        $data=[$cards1,$cards2];
            }else{
                  $rand=rand(2,13);
            $cards2=DB::select("SELECT `card`, `colour`, `image`  FROM `cards` where card =$rand order by id asc LIMIT 1")[0]->image;
            $cards1=DB::select("SELECT `card`, `colour`, `image`  FROM `cards` where card =$rand order by id desc LIMIT 1")[0]->image;
                        $data=[$cards1,$cards2];  
            }
            $resjson=json_encode($data);
        DB::select("INSERT INTO `bet_results`( `number`, `games_no`, `game_id`, `status`,`json`) VALUES ('$result','$period','$game_id','1','$resjson')"); 
        DB::select("UPDATE `betlogs` SET amount=0,games_no=games_no+1 where game_id =  '$game_id'"); 
        }
        elseif($game_id==20){
        $this->headtail($game_id,$period,$result);
      }else{
         $this->amountdistribution($game_id, $period, $result); 
      }
    }
    private function headtail($game_id, $period, $result)
    {
    if($result==20){
      $card="https://magicwinner.motug.com/public/image/heads.png" ; 
      }else{
      $card="https://magicwinner.motug.com/public/image/tails.png"  ;
      }
      DB::select("INSERT INTO `bet_results`( `number`, `games_no`, `game_id`, `status`,`json`,`random_card`) VALUES ('$result','$period','$game_id','1','$card','$card')"); 
      DB::select("UPDATE `betlogs` SET amount=0,games_no=games_no+1 where game_id =  '$game_id'"); 
      
      $this->distributeHeadTailWinnings($game_id, $period, $result);
      
     return true;
    }
    private function distributeHeadTailWinnings($game_id, $period, $result)
    {
        DB::update("UPDATE `bets` SET `status` = 2 WHERE `games_no` = '$period' AND `game_id` = '$game_id'");
        $virtual = DB::select("SELECT `name`, `number`, `actual_number`, `game_id`, `multiplier` FROM `virtual_games` WHERE `actual_number` = '$result' AND `game_id` = '$game_id'");
        if (!empty($virtual)) {
            foreach ($virtual as $winamount) {
            $multiple = $winamount->multiplier;
            $number = $winamount->number;
            DB::update("UPDATE `bets` SET `win_amount` = `amount` * '$multiple', `win_number` = '$result', `status` = 1 WHERE `games_no` = '$period' AND `game_id` = '$game_id' AND `number` = '$number'");
            }
            $uids = DB::select("SELECT `win_amount`, `userid` FROM `bets` WHERE `status` = 1 AND `games_no` = '$period' AND `game_id` = '$game_id'");
            foreach ($uids as $row) {
                $amount = $row->win_amount;
                $userid = $row->userid;
                DB::update("UPDATE `users` SET `wallet` = `wallet` + $amount, `winning_wallet` = `winning_wallet` + $amount WHERE id = $userid");
            }
        }
    }
    public function headtail_results(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'game_id' => 'required',
        ]);
        $validator->stopOnFirstFailure();
    
        if ($validator->fails()) {
            return response()->json(['status' => 400, 'message' => $validator->errors()->first()]);
        }
        $game_id = $request->game_id;
        $offset = $request->offset ?? 0;
        $from_date = $request->from_date;
        $to_date = $request->to_date;
        $status = $request->status;
        $query = DB::table('bet_results')->where('game_id', $game_id);
        if (!empty($from_date) && !empty($to_date)) {
            $query->whereBetween('created_at', [$from_date, $to_date]);
        }
        if (!empty($status)) {
            $query->where('status', $status);
        }
        $results = $query->orderBy('id', 'desc')
             ->get();
        $total_result = DB::table('bet_results')->where('game_id', $game_id)->count();
        return response()->json([
            'status' => 200,
            'message' => 'Data found',
            'total_result' => $total_result,
            'data' => $results,
        ]);
    }
    public function headtail_five_result(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'game_id' => 'required',
            'limit' => 'required|integer'
        ]);
        $validator->stopOnFirstFailure();
        if ($validator->fails()) {
            return response()->json(['status' => 400, 'message' => $validator->errors()->first()]);
        }
        $game_id = $request->game_id;
        $limit = (int) $request->limit;
        $offset = (int) ($request->offset ?? 0);
        $from_date = $request->from_date;
        $to_date = $request->to_date;
        $query = DB::table('bet_results')->where('game_id', $game_id);
        if ($from_date && $to_date) {
            $query->whereBetween('created_at', [$from_date, $to_date]);
        }
        $results = $query
            ->orderBy('id', 'desc')
            ->offset($offset)
            ->limit($limit)
            ->get();
        return response()->json([
            'status' => 200,
            'message' => 'Data found',
            'data' => $results
        ]);
    }
    public function headtail_history(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'userid' => 'required|integer',
            'game_id' => 'required|integer',
        ]);
        $validator->stopOnFirstFailure();
        if ($validator->fails()) {
            return response()->json(['status' => 400, 'message' => $validator->errors()->first()]);
        }
        $userid = $request->userid;
        $game_id = $request->game_id;
        $query = DB::table('bets')
            ->select('bets.*', 'game_settings.name AS game_name', 'virtual_games.name AS virtual_game_name')
            ->leftJoin('game_settings', 'game_settings.id', '=', 'bets.game_id')
            ->leftJoin('virtual_games', function ($join) {
                $join->on('virtual_games.game_id', '=', 'bets.game_id')
                ->on('virtual_games.number', '=', 'bets.number');
            })
            ->where('bets.userid', $userid)
            ->where('bets.game_id', $game_id);
        if ($request->from_date) {
            $query->where('bets.created_at', '>=', $request->from_date);
        }
        if ($request->to_date) {
            $query->where('bets.created_at', '<=', $request->to_date);
        }
        $results = $query->orderBy('bets.id', 'DESC')
            ->get();
        $total_bet = DB::table('bets')
        ->where('userid', $userid)
        ->where('game_id', $game_id)
        ->count(); 
        if ($results->isNotEmpty()) {
            return response()->json([
                'status' => 200,
                'message' => 'Data found',
                'total_bets' => $total_bet,
                'data' => $results
            ]);
        } else {
            return response()->json([
                'status' => 200,
                'message' => 'No Data found',
                'data' => []
            ]);
        }
    }
    public function headtail_win_amount(Request $request)
    {
        $validator = Validator::make($request->all(), [ 
        'userid' => 'required|integer',
        'game_id' => 'required|integer',
        'games_no' => 'required|integer',
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => 400, 'message' => $validator->errors()->first()], 200);
        }
        $game_id = $request->game_id;
        $userid = $request->userid;
        $game_no = $request->games_no;
        $win_amount = DB::table('bets')->selectRaw('SUM(win_amount) AS total_amount, games_no, game_id AS game_id, win_number AS number, 
        CASE WHEN SUM(win_amount) = 0 THEN "lose" ELSE "win" END AS result')
        ->where('games_no', $game_no)
        ->where('game_id', $game_id)
        ->where('userid', $userid)
        ->groupBy('games_no', 'game_id', 'win_number')
        ->first();
        try {
        if ($win_amount) {
            return response()->json([
            'status' => 200,
            'win' => $win_amount->total_amount,
            'games_no' => $win_amount->games_no,
            'result' => $win_amount->result,
            'game_id' => $win_amount->game_id,
            'number' => $win_amount->number
            ]);
        }
        return response()->json(['success' => 400, 'message' => 'User not found..!'], 200);
        } catch (Exception $e) {
            return response()->json(['error' => 'API request failed: ' . $e->getMessage()], 500);
        }
    }
}
