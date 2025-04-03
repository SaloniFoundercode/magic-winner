<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class KinoGameController extends Controller
{
    // This method fetches game details and computes bet amounts
    public function admin_result()
    {
        // Get bet logs for game_id 16
        $gameid = DB::table('betlogs')->where('game_id', 16)->get();
        
        // Get the latest game result record
        $latestGame = DB::table('keno_bet_result')->orderBy('games_no', 'desc')->first();
        $nextGameNo = $latestGame ? $latestGame->games_no + 1 : 1;
        
        // Get multiplier data
        $multiplierData = DB::table('keno_multipliers')->select('selections', 'id')->get(); 
        $betsAmount = [];
        
        if ($latestGame) {
            // Retrieve all bets for the next game
            $allBets = DB::table('bets')
                ->where('games_no', $nextGameNo)
                ->get(); 
        
            foreach ($multiplierData as $data) {
                $totalAmount = 0; 
        
                foreach ($allBets as $bet) {
                    $betsArray = json_decode($bet->bets, true); 
                    
                    // Ensure we have a valid array before iterating
                    if (!is_array($betsArray)) {
                        continue;
                    }
                    
                    foreach ($betsArray as $betItem) {
                        // Check if the bet item contains 'number' and if it matches the multiplier id
                        if (isset($betItem['number']) && $betItem['number'] == $data->id) {
                            $totalAmount += $betItem['amount'];
                        }
                    }
                }
                $betsAmount[$data->id] = $totalAmount;
            }
        }
        
        // Return the view with all required data
        return view('kino.index', compact('latestGame', 'nextGameNo', 'multiplierData', 'betsAmount'));
    }

    // This method handles admin winner submission
    public function admin_winner(Request $request)
    {
        $request->validate([
            'selections' => 'required|array',
            'games_no'   => 'required|integer',
        ]);
    
        DB::table('admin_winner_results')->insert([
            'games_no'   => $request->games_no,
            'gameId'     => 16,
            'number'     => json_encode($request->selections),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    
        return redirect()->back()->with('success', 'Winner added successfully!');
    }
}
