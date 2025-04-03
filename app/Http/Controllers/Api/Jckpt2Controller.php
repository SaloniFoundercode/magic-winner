<?php


namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Validator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class Jckpt2Controller extends Controller
{
    public function jack_bet(Request $request)
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
            if ($userwallet < $amount) {
                return response()->json([
                    'status' => 400,
                    'message' => 'Insufficient balance'
                ]);
            }
            if ($amount >= 1) {
                $now = now();
                DB::insert("INSERT INTO `bets` (`amount`, `number`, `games_no`, `game_id`, `userid`, `status`, `created_at`, `updated_at`) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?)", [
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
    public function jack_cron($game_id)
{
    $per = DB::select("SELECT game_settings.winning_percentage as winning_percentage FROM game_settings WHERE game_settings.id=$game_id");
    $percentage = $per[0]->winning_percentage;  

    $gameno = DB::select("SELECT * FROM betlogs WHERE game_id=$game_id LIMIT 1");
    $game_no = $gameno[0]->games_no;
    $period = $game_no;

    $sumamt = DB::select("SELECT SUM(amount) AS amount FROM bets WHERE game_id = '$game_id' && games_no='$game_no'");
    $totalamount = $sumamt[0]->amount;

    $percentageamount = $totalamount * $percentage * 0.01; 

    $lessamount = DB::select("SELECT * FROM betlogs WHERE game_id = '$game_id' && games_no='$game_no' && amount <= $percentageamount ORDER BY amount ASC LIMIT 1");
    if (count($lessamount) == 0) {
        $lessamount = DB::select("SELECT * FROM betlogs WHERE game_id = '$game_id' && games_no='$game_no' && amount >= '$percentageamount' ORDER BY amount ASC LIMIT 1 ");
    }

    $zeroamount = DB::select("SELECT * FROM betlogs WHERE game_id = '$game_id' && games_no='$game_no' && amount=0 ORDER BY RAND() LIMIT 1");
    $admin_winner = DB::select("SELECT * FROM admin_winner_results WHERE games_no = '$game_no' AND gameId = '$game_id' ORDER BY id DESC LIMIT 1");
    $min_max = DB::select("SELECT min(number) as mins, max(number) as maxs FROM betlogs WHERE game_id=$game_id;");

    if (!empty($admin_winner)) {
        $number = $admin_winner[0]->number;
    }

    if (!empty($admin_winner)) {
        $res = $number;
    } elseif ($totalamount < 450) {
        $res = rand($min_max[0]->mins, $min_max[0]->maxs);
    } elseif ($totalamount > 450) {
        $res = $lessamount[0]->number;
    }

    $result = $res;
    $random_card = $this->generateRandomCard(); 

    // Fetch virtual game details
    $actualNumberData = DB::table('virtual_games')
        ->where('game_id', $game_id)
        ->select('name', 'number', 'actual_number', 'multiplier', 'type')
        ->get();

    $category = 'Unknown';
    $multiplier = 1; // Default multiplier

    foreach ($actualNumberData as $data) {
        if ($data->actual_number == $result) {
            $category = $data->name; 
            $multiplier = $data->multiplier; // Get multiplier from DB
            break;
        }
    }

    $matchedCards = $this->generateCardsByCategory($category);

    if ($game_id == 24) {
        $this->handRank($game_id, $period, $result);
    } elseif ($game_id == 21) {
        $this->red_black($game_id, $period, $result);
    }

    // Insert result into `bet_results`
    DB::table('bet_results')->insert([
        'number'       => $result,
        'games_no'     => $game_no,
        'game_id'      => $game_id,
        'json'         => json_encode($matchedCards, JSON_UNESCAPED_UNICODE), 
        'random_card'  => $category, 
        'token'        => Str::random(10),
        'block'        => 0,
        'status'       => 1,
        'created_at'   => now(),
        'updated_at'   => now()
    ]);

    // Find winning bets
    $winningBets = DB::table('bets')
        ->where('game_id', $game_id)
        ->where('games_no', $game_no)
        ->where('number', $result) 
        ->get();

    foreach ($winningBets as $bet) {
        $winAmount = $bet->amount * $multiplier; // Apply multiplier

        // Update user wallet
        DB::table('users')->where('id', $bet->userid)->update([
            'wallet' => DB::raw("wallet + $winAmount")
        ]);

        // Update bet status
        DB::table('bets')->where('id', $bet->id)->update([
            'status' => 1 
        ]);
    }
}

    private function generateCardsByCategory($category)
    {
        $suits = ['♠', '♥', '♣', '♦'];
        $values = ['2', '3', '4', '5', '6', '7', '8', '9', '10', 'J', 'Q', 'K', 'A'];
        switch ($category) {
            case 'set':
                $value = $values[array_rand($values)];
                $cards = [$value . '♠', $value . '♥', $value . '♣'];
                break;
            case 'pure seq':
                $index = rand(0, count($values) - 3);
                $suit = $suits[array_rand($suits)];
                $cards = [$values[$index] . $suit, $values[$index + 1] . $suit, $values[$index + 2] . $suit];
                break;
            case 'seq':
                $index = rand(0, count($values) - 3);
                $cards = [
                    $values[$index] . $suits[array_rand($suits)],
                    $values[$index + 1] . $suits[array_rand($suits)],
                    $values[$index + 2] . $suits[array_rand($suits)]
                ];
                break;
            case 'colour':
                $colorSuit = ['♠', '♣']; 
                if (rand(0, 1)) {
                    $colorSuit = ['♥', '♦'];
                }
                $cards = [
                    $values[array_rand($values)] . $colorSuit[0],
                    $values[array_rand($values)] . $colorSuit[1],
                    $values[array_rand($values)] . $colorSuit[0]
                ];
                break;
            case 'pair':
                $value = $values[array_rand($values)];
                $cards = [
                    $value . $suits[array_rand($suits)],
                    $value . $suits[array_rand($suits)],
                    $values[array_rand($values)] . $suits[array_rand($suits)]
                ];
                break;
            case 'highcard':
            default:
                $cards = [
                    $values[array_rand($values)] . $suits[array_rand($suits)],
                    $values[array_rand($values)] . $suits[array_rand($suits)],
                    $values[array_rand($values)] . $suits[array_rand($suits)]
                ];
                break;
        }
        return $cards;
    }
    private function handRank($hand)
    {
        $color_id = ['♠'=> 4, '♥'=> 3, '♣'=> 2, '♦'=>1];
        $ranks = [
            '2' => 2, '3' => 3, '4' => 4, '5' => 5, '6' => 6, '7' => 7, '8' => 8,
            '9' => 9, '10' => 10, 'J' => 11, 'Q' => 12, 'K' => 13, 'A' => 14,
        ];
        $parsedRanks = $color_id;
        sort($parsedRanks);
        $suits = $this->parseSuits($hand);
        if ($parsedRanks[0] === $parsedRanks[1] && $parsedRanks[1] === $parsedRanks[2]) {
            return 6;
        }
        if (count(array_unique($suits)) === 1 &&
            $parsedRanks[1] - $parsedRanks[0] === 1 &&
            $parsedRanks[2] - $parsedRanks[1] === 1) {
            return 5;
        }
        if ($parsedRanks[1] - $parsedRanks[0] === 1 &&
            $parsedRanks[2] - $parsedRanks[1] === 1) {
            return 4;
        }
        if (count(array_unique($suits)) === 1) {
            return 3;
        }
        if ($parsedRanks[0] === $parsedRanks[1] ||
            $parsedRanks[2] === $parsedRanks[2] ||
            $parsedRanks[0] === $parsedRanks[2]) {
            return 2;
        }
        return 1;
    }
    private function parseSuits($hand)
    {
        if (!is_array($hand)) {
            $hand = explode(' ', trim($hand)); 
        }
    
        return array_map(function ($card) {
            return preg_replace('/[0-9AJQK]+/', '', $card);
    }, $hand);
    }
    private function generateRandomCard()
    {
        $suits = ['♠', '♥', '♣', '♦'];
        $values = ['2', '3', '4', '5', '6', '7', '8', '9', '10', 'J', 'Q', 'K', 'A'];
    
        $cards = [];
        for ($i = 0; $i < 3; $i++) { 
            $suit = $suits[array_rand($suits)];
            $value = $values[array_rand($values)];
            $cards[] = $value . $suit;
        }
        
        return $cards;
    }
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
        elseif($game_id==24){
        $this->jackpot($game_id,$period,$result);
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
            ->where('game_id', 24)
            ->where('actual_number', $random_card)
            ->first();
        if (!$virtualGame) {
            \Log::error("No virtual game found for Game ID: $game_id and Random Card: $random_card");
            return;
        }
        $multiplier = $virtualGame->multiplier;
        DB::table('bets')
            ->where('game_id', 24)
            ->where('games_no', $period)
            ->update([
                'trade_amount' => $multiplier
            ]);
        $result_number = (int)$result->number;
        $bets = DB::table('bets')
            ->where('game_id', 24)
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
}