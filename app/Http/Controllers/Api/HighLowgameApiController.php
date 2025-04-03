<?php


namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\User;
use Validator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

use Illuminate\Support\Facades\DB;



class HighLowgameApiController extends Controller
{
    public function high_low_bet(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'userid' => 'required',
            'game_id' => 'required',
            'card_number' => 'required',
            'number' => 'required',
            'amount' => 'required|numeric|min:1',
        ]);
    
        if ($validator->fails()) {
            return response()->json(['status' => 400, 'message' => $validator->errors()->first()]);
        }
    
        $userid = $request->userid;
        $gameid = $request->game_id;
        $number = $request->number;
        $card_number = $request->card_number;
        $amount = $request->amount;
    	date_default_timezone_set('Asia/Kolkata');
       $datetime=date('Y-m-d H:i:s');
        $orderid = date('YmdHis') . rand(11111, 99999);
        
        $gamesno = DB::table('betlogs')->where('game_id', $gameid)->value('games_no');
        
    
        $userWallet = DB::table('users')->where('id', $userid)->value('wallet');

        if ($userWallet < $amount) {
            return response()->json(['status' => 400, 'message' => 'Insufficient balance']);
        }
    
        $commission = $amount * 0.00; 
        $betAmount = $amount - $commission;

        $data1 = DB::table('virtual_games')
            ->where('number', $number)
            ->where('game_id', $gameid)
            ->get(['multiplier', 'actual_number']);
    // dd($data1);
        $totalAmount = 0;
        foreach ($data1 as $row) {
            $totalAmount += $betAmount * $row->multiplier;
        }
    
        DB::beginTransaction();
    try {
    $hii = DB::table('high_low_bets')->insert([
        'amount' => $amount,
        'trade_amount' => $betAmount, 
        'commission' => $commission, 
        'number' => $number,
        'card_number' => $card_number,
        'games_no' => $gamesno,
        'game_id' => $gameid,
        'userid' => $userid,
        'order_id' => $orderid,
        'created_at' => $datetime,
        'updated_at' => $datetime,
        'status' => 0
    ]);
    if (!$hii) {
        DB::rollBack();
    }
    if (empty($data1)) {
        DB::rollBack();
    }

    foreach ($data1 as $row) {
        try {
            DB::table('betlogs')
                ->where('game_id', $gameid)
                ->where('number', $row->actual_number)
                ->increment('amount', $betAmount * $row->multiplier);
        } catch (\Exception $e) {
            DB::rollBack();
            // dd('Error in betlogs update: ' . $e->getMessage());
        }
    }

    // Updating user balances in one query
    DB::table('users')
        ->where('id', $userid)
        ->update([
            'wallet' => DB::raw('wallet - ' . $amount),
            'recharge' => DB::raw('CASE WHEN recharge >= ' . $amount . ' THEN recharge - ' . $amount . ' ELSE 0 END'),
            'today_turnover' => DB::raw('today_turnover + ' . $amount),
        ]);

    DB::commit();

    return response()->json(['status' => 200, 'message' => 'Bet Successfully']);

} catch (\Exception $e) {
    DB::rollBack();
    // dd('Transaction failed: ' . $e->getMessage());
    return response()->json(['status' => 500, 'message' => 'Something went wrong']);
}

    }
    public function high_low_win_amount(Request $request)
    {
    	    
    	    	$validator = Validator::make($request->all(), [ 
    				'userid' => 'required',
    		       'game_id' => 'required',
    		       'games_no'=>'required'
    		
    			]);
    
        $validator->stopOnFirstFailure();
    
        if ($validator->fails()) {
            return response()->json(['status' => 400, 'message' => $validator->errors()->first()]);
        }
    	
    	
        $game_id = $request->game_id;
        $userid = $request->userid;
    	$game_no = $request->games_no;
     
    	   
    // 	     $win_amount = DB::Select("SELECT 
    //     SUM(`win_amount`) AS total_amount,
    //     `gamesno`,
    //     `status`,
    //     `game_id` AS gameid,
    //     `win_number` AS number,
    //     CASE WHEN SUM(`win_amount`) = 0 THEN 'lose' ELSE 'win' END AS result 
    // FROM 
    //     `high_low_bets` 
    // WHERE 
    //     `gamesno` =  $game_no
    //     AND `game_id` = $game_id 
    //     AND `userid` = $userid 
    // GROUP BY 
    //     `gamesno`,
    //     `game_id`,
    //     `win_number`
    // ");
    $win_amount = DB::Select("SELECT 
        SUM(win_amount) AS total_amount,
        games_no,
        game_id AS gameid,
        win_number AS number,
        CASE WHEN SUM(win_amount) = 0 THEN 'lose' ELSE 'win' END AS result,
        CASE WHEN SUM(win_amount) = 0 THEN 2 ELSE 1 END AS win_loss_status 
    FROM 
        high_low_bets 
    WHERE 
        games_no = $game_no 
        AND game_id = $game_id 
        AND userid = $userid 
    GROUP BY 
        games_no, 
        game_id, 
        win_number");
     if ($win_amount) {
                $response = [
                    'message' => 'Successfully',
                    'status' => 200,
                    'win' => $win_amount[0]->total_amount,
                    'games_no' => $win_amount[0]->games_no,
                    'result' => $win_amount[0]->result,
                    'gameid' => $win_amount[0]->gameid,
                    'number' => $win_amount[0]->number,
                    'win_loss_status' => $win_amount[0]->win_loss_status,
                ];
                return response()->json($response,200);
            } else {
                return response()->json(['msg' => 'No record found','status' => 400,], 400);
            }
    	    
    	}
    public function high_low_results(Request $request)
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
// dd($where);
    if (!empty($game_id)) {
        $where[] = "bet_results.game_id = '$game_id'";
    }
    // dd($game_id);
    if (!empty($from_date) && !empty($to_date)) {
        $where[] = "bet_results.created_at BETWEEN '$from_date' AND '$to_date'";
    }
    $query = "
        SELECT 
    bet_results.*, 
    virtual_games.name AS game_name,
    virtual_games.number AS game_number, 
    virtual_games.game_id AS game_gameid,
    game_settings.name AS game_setting_name 
FROM 
    bet_results
LEFT JOIN 
    virtual_games ON bet_results.game_id = virtual_games.game_id && bet_results.number=virtual_games.number
JOIN 
    game_settings ON bet_results.game_id = game_settings.id 
    ";
// dd($query);
    if (!empty($where)) {
        $query .= " WHERE " . implode(" AND ", $where);
    }

    $query .= " ORDER BY bet_results.id DESC LIMIT $offset,$limit";
    // dd($query);
    $results = DB::select($query);
    return response()->json([
        'status' => 200,
        'message' => 'Data found',
        'data' => $results
    ]);
}

  	//// Bet History ////

    public function high_low_bet_history(Request $request)
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
	//////
	

if (!empty($game_id)) {
    $where['high_low_bets.game_id'] = "$game_id";
    $where['high_low_bets.userid'] = "$userid";
}


if (!empty($from_date)) {
    
       $where['high_low_bets.created_at']="$from_date%";
  $where['high_low_bets.created_at']="$to_date%";
}

$query = " SELECT DISTINCT high_low_bets.*, game_settings.name AS game_name, virtual_games.name AS name 
FROM high_low_bets
LEFT JOIN game_settings ON game_settings.id = high_low_bets.game_id 
LEFT JOIN virtual_games ON virtual_games.game_id = high_low_bets.game_id AND virtual_games.number = high_low_bets.number" ;

if (!empty($where)) {
    $query .= " WHERE " . implode(" AND ", array_map(function ($key, $value) {
        return "$key = '$value'";
    }, array_keys($where), $where));
}

 $query .= " ORDER BY  high_low_bets.id DESC  LIMIT $offset , $limit";
//////
$results = DB::select($query);
$bets=DB::select("SELECT userid, COUNT(*) AS total_bets FROM high_low_bets WHERE `userid`=$userid GROUP BY userid
");
		if (isset($bets[0])) {
    $total_bet = $bets[0]->total_bets;
} else {
    // Handle the case where the array is empty or the key doesn't exist
    $total_bet = 0; // or any default value
}

//$total_bet=$bets[0]->total_bets;
if(!empty($results)){
    ///
		//
		 return response()->json([
            'status' => 200,
            'message' => 'Data found',
            'total_bets' => $total_bet,
            'data' => $results
            
        ]);
         return response()->json($response,200);
}else{
    
     //return response()->json(['msg' => 'No Data found'], 400);
    $response = [
    'status' => 400,
    'message' => 'No Data found',
    'data' => $results
];

//
return response()->json($response, $response['status']);
         
    
}
		
	}
    public function high_low_cron($game_id)
    {
            $per=DB::select("SELECT game_settings.winning_percentage as winning_percentage FROM game_settings WHERE game_settings.id=$game_id");
            $percentage = $per[0]->winning_percentage;  
        
            $gameno=DB::select("SELECT * FROM betlogs WHERE game_id=$game_id LIMIT 1");
          
            $game_no=$gameno[0]->games_no;
             $period=$game_no;
            
            $sumamt=DB::select("SELECT SUM(amount) AS amount FROM high_low_bets WHERE game_id = '$game_id' && games_no='$period'");
             
            $totalamount=$sumamt[0]->amount;
		   
            $percentageamount = $totalamount*$percentage*0.01; 
            // dd($game_id,$period,$percentageamount);
            
            $lessamount=DB::select(" SELECT * FROM betlogs WHERE game_id = '$game_id'  && games_no='$period' && amount <= $percentageamount ORDER BY amount asc LIMIT 1 ");
            // dd($lessamount);
				if(count($lessamount)==0){
				$lessamount=DB::select(" SELECT * FROM betlogs WHERE game_id = '$game_id'  && games_no='$game_no' && amount >= '$percentageamount' ORDER BY amount asc LIMIT 1 ");
		
				}
            $zeroamount=DB::select(" SELECT * FROM betlogs WHERE game_id =  '$game_id'  && games_no='$game_no' && amount=0 ORDER BY RAND() LIMIT 1 ");
            // dd($zeroamount);
            $admin_winner=DB::select("SELECT * FROM admin_winner_results WHERE games_no = '$game_no' AND gameId = '$game_id' ORDER BY id DESC LIMIT 1");
            // dd($admin_winner);
            $min_max=DB::select("SELECT min(number) as mins,max(number) as maxs FROM betlogs WHERE game_id=$game_id;");
            // dd($min_max);
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
        // dd($result);
       $cards=DB::select("SELECT `id` FROM `cards` ORDER BY RAND() LIMIT 1;");
       $card=$cards[0]->id;
      // dd($card);
       DB::select("INSERT INTO `bet_results`( `number`, `games_no`, `game_id`, `status`,`json`,`random_card`) VALUES ('$result','$period','$game_id','1','$card','$card')");
        $this->amountdistributioncolors($game_id,$period,$result,$card);
        // dd($this);
      DB::select("UPDATE `betlogs` SET amount=0,games_no=games_no+1 where game_id =  '$game_id'"); 
     return true;
         
    }
    
     private function amountdistributioncolors($game_id,$period,$result,$card)
    {
       
        $virtual=DB::select("SELECT name, number, actual_number, game_id, multiplier FROM virtual_games WHERE actual_number='$result' && game_id= '$game_id' AND ((type != 1 AND multiplier != '1.5') OR (type = 1 AND multiplier = '1.5'));");
     

        foreach ($virtual as $winamount) {
            
            $multiple = $winamount->multiplier;

            $number=$winamount->number;
            if(!empty($number)){
				
				if($result == '0'){
					DB::select("UPDATE high_low_bets SET win_amount =(trade_amount*9),win_number= '0',status=1,result_card='$card' WHERE games_no='$period' && game_id=  '$game_id' && number =$result");
				}
            
          DB::select("UPDATE high_low_bets SET win_amount =(trade_amount*$multiple),win_number= '$result',status=1,result_card='$card' WHERE games_no='$period' && game_id=  '$game_id' && number =$number");
        
            }
            
		}
                $uid = DB::select("SELECT  win_amount,  userid FROM high_low_bets where win_number>=0 && games_no='$period' && game_id=  '$game_id' ");
        foreach ($uid as $row) {
             $amount = $row->win_amount;
            $userid = $row->userid;
      DB::update("UPDATE users SET wallet = wallet + $amount, winning_wallet = winning_wallet + $amount WHERE id = $userid");
        
        }
 
          DB::select("UPDATE high_low_bets SET status=2 ,win_number= '$result',result_card='$card' WHERE games_no='$period' && game_id=  '$game_id' &&  status=0 && win_amount=0");

    }
    
}
