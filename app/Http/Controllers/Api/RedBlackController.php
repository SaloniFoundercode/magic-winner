<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use App\Models\{Bet,Card,AdminWinnerResult,User,Betlog,GameSetting,VirtualGame,BetResult,MineGameBet,PlinkoBet,PlinkoIndexList};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use App\Helper\jilli;

class RedBlackController extends Controller
{
    
public function rb_bets(Request $request)
{
    $validator = Validator::make($request->all(), [
        'userid' => 'required',
        
        //'game_no' => 'required',
        'game_id' => 'required',
        // 'test.*.number' => 'required',
        // 'test.*.amount' => 'required',
        'json'=>'required'
    ]);

    $validator->stopOnFirstFailure();

    if ($validator->fails()) {
        return response()->json(['status' => 400, 'message' => $validator->errors()->first()]);
    }
    
     $testData = $request->json;
    $userid = $request->userid;
    $gameid = $request->game_id;
  // $gameno = $request->game_no;
  //
  //
    
    $gamesrno=DB::select("SELECT games_no FROM `betlogs` WHERE `game_id`=$gameid  LIMIT 1");
    $gamesno=$gamesrno[0]->games_no;
 
   
    $user_wallet = DB::table('users')->select('wallet')->where('id', $userid)->first();
            $userwallet = $user_wallet->wallet;
   
    foreach ($testData as $item) {
        $number = $item['number'];
        $amount = $item['amount'];
        if($userwallet > $amount){
      if ($amount>=1) {
        DB::insert("INSERT INTO `bets`(`amount`, `number`, `games_no`, `game_id`, `userid`, `status`) 
            VALUES ('$amount', '$number', '$gamesno', '$gameid', '$userid', '0')");

 $data1 = DB::select("SELECT * FROM virtual_games WHERE virtual_games.number=$number");
             foreach($data1 as $row){
             $multiplier = $row->multiplier;
             $num=$row->actual_number;
            $multiply_amt = $amount * $multiplier;
            
            
          $bet_amt= DB::update("UPDATE `betlogs` SET `amount`=amount+'$multiply_amt' where game_id= $gameid && number=$num");
             }
            DB::table('users')->where('id', $userid)->update(['wallet' => DB::raw('wallet - ' . $amount)]);
      
        
      }
       
      }
      
      else {
                $response['msg'] = "Insufficient balance";
                $response['status'] = "400";
                return response()->json($response);
            }

    }

     return response()->json([
        'status' => 200,
        'message' => 'Bet Successfully',
    ]);   
    
}
    
    
    public function cron($game_id)
    {
        // dd($game_id);
                  $per=DB::select("SELECT game_settings.winning_percentage as winning_percentage FROM game_settings WHERE game_settings.id=$game_id");
            $percentage = $per[0]->winning_percentage;  
    
                $gameno=DB::select("SELECT * FROM betlogs WHERE game_id=$game_id LIMIT 1");
                //
            //   dd($gameno);
                $game_no=$gameno[0]->games_no;
                // dd($game_no);
                 $period=$game_no;
                
    				
                $sumamt=DB::select("SELECT SUM(amount) AS amount FROM bets WHERE game_id = '$game_id' && games_no='$game_no'");
    
    // dd($sumamt);
    				
                $totalamount=$sumamt[0]->amount;
    		
                $percentageamount = $totalamount*$percentage*0.01; 
    // 			dd($percentageamount);
                $lessamount=DB::select(" SELECT * FROM betlogs WHERE game_id = '$game_id'  && games_no='$game_no' && amount <= $percentageamount ORDER BY amount asc LIMIT 1 ");
    				if(count($lessamount)==0){
    				$lessamount=DB::select(" SELECT * FROM betlogs WHERE game_id = '$game_id'  && games_no='$game_no' && amount >= '$percentageamount' ORDER BY amount asc LIMIT 1 ");
    				}
                $zeroamount=DB::select(" SELECT * FROM betlogs WHERE game_id =  '$game_id'  && games_no='$game_no' && amount=0 ORDER BY RAND() LIMIT 1 ");
                $admin_winner=DB::select("SELECT * FROM admin_winner_results WHERE games_no = '$game_no' AND gameId = '$game_id' ORDER BY id DESC LIMIT 1");
    // 		 dd($admin_winner);
                //  dd($admin_winner);
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
            //$result=$number;
            $result=$res;
    if ($game_id == 1 || $game_id == 2 || $game_id == 3 || $game_id == 4) {
        $this->colour_prediction_and_bingo($game_id, $period, $result);
    					
    } elseif ($game_id == 10 ) {
        $this->dragon_tiger($game_id, $period, $result);
    } elseif ($game_id == 6 || $game_id == 7 || $game_id == 8 || $game_id == 9) {
        $this->trx($game_id, $period, $result);
    }elseif ($game_id == 13 ) {
        $this->andarbaharpatta($game_id, $period, $result);
    }
    elseif ($game_id == 15 ) {
        $this-> head_tail($game_id, $period, $result);
    } elseif($game_id==18){
        $this->red_black($game_id,$period,$result);
    }
    
    }

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


    
     DB::select("INSERT INTO `results`( `number`, `gamesno`, `gameid`, `status`,`json`,`random_card`) VALUES ('$result','$period','$game_id','1','$ddta','$ddta')"); 
      DB::select("UPDATE `betlogs` SET amount=0,games_no=games_no+1 where game_id =  '$game_id'"); 
     return true;
  }
}