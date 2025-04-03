<?php


namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Validator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class JackpotController extends Controller
{
    public function jackpot_bet(Request $request)
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
    
                DB::insert("INSERT INTO `bets` (`amount`, `number`, `games_no`, `game_id`, `userid`, `status`, `created_at`, `updated_at`) VALUES (?, ?, ?, ?, ?, ?, ?, ?)", [
                    $amount,
                    $number,
                    $gamesno,
                    $gameid,
                    $userid,
                    0,
                    $now,
                    $now
                ]);
    
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
    public function jackpot_cron($game_id)
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
    		if ($game_id == 19) {
        $this->resultannounce($game_id, $period, $result);
        }elseif($game_id==21){
            $this->red_black($game_id,$period,$result);
        }elseif($game_id==20){
            $this->resultheadtail($game_id, $period, $result);
        }elseif($game_id==22){
            $this->up7down($game_id,$period,$result);
    }elseif($game_id==23){
            $this->resultannouncejhandimunda($game_id,$period);
    }
    }
    //head tail
    // private function resultheadtail($game_id,$period,$result)
    // {
    //     $data=[];
    //     if($game_id==1){
    //         if($result==1){
    //         $rand=rand(2,13);
    //         $cards1=DB::select("SELECT `card`, `colour`, `image`  FROM `cards` where card >$rand order by rand(id) LIMIT 1")[0]->image;
    //         $rand2=rand(2,$rand-2);
    //         $cards2=DB::select("SELECT `card`, `colour`, `image`  FROM `cards` where card >$rand2 order by rand(id) LIMIT 1")[0]->image;
    //         $data=[$cards1,$cards2];
    //         }elseif($game_id==2){
    //              $rand=rand(2,13);
    //         $cards2=DB::select("SELECT `card`, `colour`, `image`  FROM `cards` where card >$rand order by rand(id) LIMIT 1")[0]->image;
    //         $rand2=    rand(2,$rand-2);
    //         $cards1=DB::select("SELECT `card`, `colour`, `image`  FROM `cards` where card >$rand2 order by rand(id) LIMIT 1")[0]->image;
    //                     $data=[$cards1,$cards2];
    //         }else{
    //               $rand=rand(2,13);
    //         $cards2=DB::select("SELECT `card`, `colour`, `image`  FROM `cards` where card =$rand order by id asc LIMIT 1")[0]->image;
    //         $cards1=DB::select("SELECT `card`, `colour`, `image`  FROM `cards` where card =$rand order by id desc LIMIT 1")[0]->image;
    //                     $data=[$cards1,$cards2];  
    //         }
    //         $resjson=json_encode($data);
    //     DB::select("INSERT INTO `bet_results`( `number`, `games_no`, `game_id`, `status`,`json`) VALUES ('$result','$period','$game_id','1','$resjson')"); 
    //     DB::select("UPDATE `betlogs` SET amount=0,games_no=games_no+1 where game_id =  '$game_id'"); 
    //     }
    //     elseif($game_id==20){
    //     $this->headtail($game_id,$period,$result);
    //   }else{
    //      $this->amountdistribution($game_id, $period, $result); 
    //   }
    // }
    // private function headtail($game_id, $period, $result)
    // {
    // if($result==20){
    //   $card="https://magicwinner.motug.com/public/image/heads.png" ; 
    //   }else{
    //   $card="https://magicwinner.motug.com/public/image/tails.png"  ;
    //   }
    //   DB::select("INSERT INTO `bet_results`( `number`, `games_no`, `game_id`, `status`,`json`,`random_card`) VALUES ('$result','$period','$game_id','1','$card','$card')"); 
    //   DB::select("UPDATE `betlogs` SET amount=0,games_no=games_no+1 where game_id =  '$game_id'"); 
      
    //   $this->distributeHeadTailWinnings($game_id, $period, $result);
      
    //  return true;
    // }
    // private function distributeHeadTailWinnings($game_id, $period, $result)
    // {
    //     $virtual=DB::select("SELECT `name`, `number`, `actual_number`, `game_id`, `multiplier` FROM `virtual_games` WHERE `actual_number`='$result' && `game_id`= '$game_id';");
    //     if(!empty($virtual)){
    //     foreach ($virtual as $winamount) {
    //         $multiple = $winamount->multiplier;
    //         $number=$winamount->number;
    //       DB::select("UPDATE `bets` SET `win_amount` =`amount`*'$multiple',`win_number`= '$result',`status`=1 WHERE `games_no`='$period' && `game_id`=  '$game_id' && number ='$number'");
    //     }
    //             $uid = DB::select("SELECT  `win_amount`,  `userid` FROM `bets` where `win_number`>0 && `games_no`='$period' && `game_id`=  '$game_id' ");
    //     foreach ($uid as $row) {
    //          $amount = $row->win_amount;
    //         $userid = $row->userid;
    //     DB::update("UPDATE `users` SET `wallet` = `wallet` + $amount, `winning_wallet` = `winning_wallet` + $amount WHERE id = $userid");
    //     }
    //  }else{

    //     DB::select("UPDATE `bets` SET `status`=2 WHERE `games_no`='$period' && `game_id`=  '$game_id' && number ='$number' &&  `status`=0");
    //  }
    // }
    //7updown
    private function up7down($game_id,$period,$result)
    {
      $card=array();
      if($result==1){
          $image1=rand(1,5);
          $im2=rand($image1,6);
          $image2=$im2-$image1;
     $card=["https://magicwinner.motug.com/public/uploads/7up/$image1.png",$card="https://magicwinner.motug.com/public/uploads/7up/$image2.png"]; 
      }elseif($result==2){
        $image1=rand(1,5);
        $image2=7-$image1;
        $card=["https://magicwinner.motug.com/public/uploads/7up/$image1.png" ,$card="https://magicwinner.motug.com/public/uploads/7up/$image2.png"];
        }else{
         $image1=6;
        $im2=rand($image1,12);
        $image2=$im2-$image1;
         $card=["https://magicwinner.motug.com/public/uploads/7up/$image1.png" ,$card="https://magicwinner.motug.com/public/uploads/7up/$image2.png"];       
      }
      $cccc=$image1+$image2;
        $dtat=json_encode($card);
        DB::select("INSERT INTO `bet_results`( `number`, `games_no`, `game_id`, `status`,`json`,`random_card`) VALUES ('$result','$period','$game_id','1','$dtat','$cccc')"); 
        $this->distributeUp7DownWinnings($game_id, $period);
        DB::select("UPDATE `betlogs` SET amount=0,games_no=games_no+1 where game_id =  '$game_id'"); 
     return true;
    }
    private function distributeUp7DownWinnings($game_id, $period)
    {
        // Bet Result Fetch karo
        $result = DB::table('bet_results')
            ->where('game_id', $game_id)
            ->where('games_no', $period)
            ->latest('id')
            ->first();
    
        if (!$result) {
            \Log::error("No result found for Game ID: $game_id, Period: $period");
            return;
        }
    
        $result_number = (int)$result->number; 
        $random_card_sum = (int)$result->random_card; 
        $bets = DB::table('bets')
            ->where('game_id', $game_id)
            ->where('games_no', $period)
            ->get();
        $userWinningAmounts = [];
        foreach ($bets as $bet) {
            $tradeAmount = (float)$bet->amount;
            $betNumber = (int)$bet->number; 
    
            $win_amount = 0;
            $is_winner = false;
    
            if ($betNumber === $result_number) {
                $multiplier = 2; 
                $win_amount = $tradeAmount * $multiplier;
                $is_winner = true;
            }
            $status = $is_winner ? 1 : 2;
            DB::table('bets')
            ->where('id', $bet->id)
            ->update([
                'status' => $status,
                'win_amount' => $win_amount,
                'win_number' => $random_card_sum,
                'updated_at' => now()
            ]);
            if ($is_winner && $win_amount > 0) {
                if (!isset($userWinningAmounts[$bet->userid])) {
                    $userWinningAmounts[$bet->userid] = 0;
                }
                $userWinningAmounts[$bet->userid] += $win_amount;
            }
        }
        foreach ($userWinningAmounts as $user_id => $total_win) {
            DB::table('users')
                ->where('id', $user_id)
                ->increment('wallet', $total_win);
        }
        \Log::info("7UpDown winnings distributed for Game ID: $game_id, Period: $period");
    }
    //red black
    private function red_black($game_id,$period,$result)
    {
     if($result==1) {
         $colour='d';
     }elseif($result==2) {
         $colour='c';
     }elseif($result==3) {
         $colour='k';
     }elseif($result==4) {
         $colour='e';
     }
        $ddta=DB::select("SELECT `image` FROM `cards` WHERE `colour`='$colour' ORDER BY RAND() limit 1")[0]->image;
        DB::select("INSERT INTO `bet_results`( `number`, `games_no`, `game_id`, `status`,`json`,`random_card`) VALUES ('$result','$period','$game_id','1','$ddta','$ddta')"); 
        DB::select("UPDATE `betlogs` SET amount=0,games_no=games_no+1 where game_id =  '$game_id'"); 
     return true;
    }
    //jackpot
    private function resultannounce($game_id,$period,$result)
    {
        $data=[];
        // dd($data);
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
            // echo "hii";
            $resjson=json_encode($data);
        DB::select("INSERT INTO `bet_results`( `number`, `games_no`, `game_id`, `status`,`json`) VALUES ('$result','$period','$game_id','1','$resjson')"); 
        DB::select("UPDATE `betlogs` SET amount=0,games_no=games_no+1 where game_id =  '$game_id'"); 
        }
        elseif($game_id==19){
        $this->jackpot($game_id,$period,$result);
        }elseif($game_id==23){
        $this->resultannouncejhandimunda($game_id,$period,$result);
      }else{
         $this->amountdistribution($game_id, $period, $result); 
      }
    }
    private function jackpot($game_id, $period, $result)
    {
        $card = array();
        $value = [];
        if ($result == 1) {
            $caed = rand(2, 14);
            $value = DB::select("SELECT `image` FROM `cards` WHERE `card` = $caed LIMIT 3");
        } elseif ($result == 2) {
            $characters = ['c', 'e', 'k', 'd'];
            $caed = rand(2, 11);
            $colour = $characters[rand(0, 3)];
            $value = DB::select("SELECT `image` FROM `cards` WHERE `card` >= $caed AND `colour` = '$colour' ORDER BY `card` ASC LIMIT 3");
        } elseif ($result == 3) {
            $caed = rand(2, 11);
            $colorand = rand(0, 2);
            $characters = ['c', 'e', 'k', 'd'];
            $colour = $characters[$colorand];
            $colours = $characters[$colorand + 1];
            $value = DB::select("SELECT `image` FROM `cards` WHERE (`card` >= $caed AND `colour` = '$colour') OR (`card` >= $caed AND `colour` = '$colours') ORDER BY `card` ASC, RAND() LIMIT 3");
        } elseif ($result == 4) {
            $characters = ['c', 'e', 'k', 'd'];
            $colour = $characters[rand(0, 3)];
            $value = DB::select("SELECT `image` FROM `cards` WHERE `colour` = '$colour' ORDER BY `card` ASC LIMIT 3");
        } elseif ($result == 5) {
            $caed = rand(2, 11);
            $caeds = rand(1, 3);
            $value1 = DB::select("SELECT `image` FROM `cards` WHERE `card` = $caed LIMIT 2");
            $value2 = DB::select("SELECT `image` FROM `cards` WHERE `card` = ($caed + $caeds) LIMIT 1");
            $value = array_merge($value1, $value2);
        } elseif ($result == 6) {
            $value = DB::select("SELECT `image` FROM `cards` ORDER BY `card`, `colour` LIMIT 3");
        }
        $dataS = [];
        foreach ($value as $data) {
            $dataS[] = $data->image;
        }
        $jack = json_encode($dataS);
        // dd($jack);
        DB::insert("INSERT INTO `bet_results` (`number`, `games_no`, `game_id`, `status`, `json`, `random_card`) VALUES (?, ?, ?, 1, ?, ?)", [
            $result,
            $period,
            $game_id,
            $jack,
            $result
        ]);
        $this->distributeJackpotWinnings($game_id, $period);
        DB::update("UPDATE `betlogs` SET amount = 0, games_no = games_no + 1 WHERE game_id = $game_id");
        return true;
    }
    private function distributeJackpotWinnings($game_id, $period)
    {
        $result = DB::table('bet_results')
            ->where('game_id', $game_id)
            ->where('games_no', $period)
            ->latest('id')
            ->first();
        if (!$result) {
            \Log::error("No result found for Game ID: $game_id, Period: $period");
            return;
        }
        $random_card = (int)$result->random_card; 
    
        $virtualGame = DB::table('virtual_games')
            ->where('game_id', 19)
            ->where('actual_number', $random_card)
            ->first();
        if (!$virtualGame) {
            \Log::error("No virtual game found for Game ID: $game_id and Random Card: $random_card");
            return;
        }
        $multiplier = $virtualGame->multiplier;
        DB::table('bets')
            ->where('game_id', 19)
            ->where('games_no', $period)
            ->update([
                'trade_amount' => $multiplier
            ]);
        $result_number = (int)$result->number;
        $bets = DB::table('bets')
            ->where('game_id', 19)
            ->where('games_no', $period)
            ->get();
        $userWinningAmounts = [];
        foreach ($bets as $bet) {
            $tradeAmount = (float)$bet->amount;
            $betNumber = (int)$bet->number;
            $win_amount = 0;
            $is_winner = false;
            if ($betNumber === $result_number) {
                $win_amount = $tradeAmount * $multiplier;
                $is_winner = true;
            }
            $status = $is_winner ? 1 : 2;
            DB::table('bets')
                ->where('id', $bet->id)
                ->update([
                    'status' => $status,
                    'win_amount' => $win_amount,
                    'trade_amount' => $multiplier, 
                    'win_number' => $random_card ,
                    'updated_at' => now()
                ]);
    
            if ($is_winner && $win_amount > 0) {
                if (!isset($userWinningAmounts[$bet->userid])) {
                    $userWinningAmounts[$bet->userid] = 0;
                }
                $userWinningAmounts[$bet->userid] += $win_amount;
            }
        }
        foreach ($userWinningAmounts as $user_id => $total_win) {
            DB::table('users')
            ->where('id', $user_id)
            ->increment('wallet', $total_win);
        }
        \Log::info("Jackpot winnings distributed for Game ID: $game_id, Period: $period");
    }
    public function jackpot_results(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'game_id' => 'required',
        ]);
        $validator->stopOnFirstFailure();
    
        if ($validator->fails()) {
            return response()->json(['status' => 400, 'message' => $validator->errors()->first()]);
        }
        $game_id = $request->game_id;
        // $limit = $request->limit;
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
    public function jack_five_result(Request $request)
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
    public function jackpot_history(Request $request)
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
    public function win_amount(Request $request)
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
        // echo "$game_id,$userid,$game_no";
        // die;
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
                'gameid' => $win_amount->game_id,
                'number' => $win_amount->number
            ]);
        }
        return response()->json(['success' => 400, 'message' => 'User not found..!'], 200);
        } catch (Exception $e) {
            return response()->json(['error' => 'API request failed: ' . $e->getMessage()], 500);
        }
    }
    //jhandi munda game
    private function resultannouncejhandimunda($game_id, $period)
{
    $winningBet = DB::select("SELECT number FROM virtual_games WHERE game_id = 23 AND number BETWEEN 1 AND 6 ORDER BY RAND() LIMIT 1");

    if (!empty($winningBet)) {
        $winningNumber = $winningBet[0]->number;

        $res = $winningNumber;
        $datas = $winningNumber;

        $resultjson = $res;
        $randencode = $datas;

        // Insert result into bet_results table
        DB::insert("INSERT INTO `bet_results` (`games_no`, `game_id`, `status`, `json`, `random_card`) 
                    VALUES ('$period', '23', '1', '$resultjson', '$randencode')");

        // Update winning bets with a multiplier of 3
        DB::update("UPDATE `bets` SET `win_amount` = `amount` * 3, `status`=1 
                    WHERE `games_no`='$period' AND `game_id`=23 AND `number`='$winningNumber'");

        // Update losing bets
        DB::update("UPDATE `bets` SET `status`=2 WHERE `games_no`='$period' AND `game_id`=23 AND `number` != '$winningNumber'");

        // Reset bet logs
        DB::update("UPDATE `betlogs` SET amount=0, games_no=games_no+1 WHERE game_id = 23");

        $this->jhandi_munda($game_id, $period);
    }
}
private function jhandi_munda($game_id, $period)
{
    // Fetch bet result
    $result = DB::table('bet_results')
        ->where('game_id', $game_id)
        ->where('games_no', $period)
        ->latest('id')
        ->first();

    if (!$result) {
        \Log::error("No result found for Game ID: $game_id, Period: $period");
        return;
    }
    $winning_number = (int) $result->random_card;
    if (!$winning_number) {
        \Log::error("Invalid winning number in bet_results for Game ID: $game_id, Period: $period");
        return;
    }
    $bets = DB::table('bets')
        ->where('game_id', $game_id)
        ->where('games_no', $period)
        ->get();
    $userWinningAmounts = [];
    foreach ($bets as $bet) {
        $tradeAmount = (float) $bet->amount;
        $betNumber = (int) $bet->number;
        $win_amount = 0;
        $is_winner = false;
        $virtualGame = DB::table('virtual_games')
            ->where('game_id', 23)
            ->where('actual_number', $winning_number)
            ->first();

        if (!$virtualGame) {
            \Log::error("No virtual game found for actual_number: $winning_number");
            continue;
        }
        $multiplier = $virtualGame->multiplier ?? 1; 
        if ($betNumber === $winning_number) {
            $win_amount = $tradeAmount * $multiplier;
            $is_winner = true;
        }
        DB::table('bets')
            ->where('id', $bet->id)
            ->update([
                'status' => $is_winner ? 1 : 2,
                'win_amount' => $win_amount,
                'trade_amount' => $multiplier,
                'win_number' => $winning_number,
                
                'updated_at' => now()
            ]);
        if ($is_winner && $win_amount > 0) {
            $userWinningAmounts[$bet->userid] = ($userWinningAmounts[$bet->userid] ?? 0) + $win_amount;
        }
    }
    foreach ($userWinningAmounts as $user_id => $total_win) {
        DB::table('users')
            ->where('id', $user_id)
            ->increment('wallet', $total_win);
    }

    \Log::info("Jhandi Munda winnings distributed for Game ID: $game_id, Period: $period");
}

}
