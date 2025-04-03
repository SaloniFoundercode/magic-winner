<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DB;

class HeadTailController extends Controller
{
    public function head_tail_result()
    {
        $gameid = DB::table('betlogs')->where('game_id', 20)->get();
        $latestGame = DB::table('bet_results')->orderBy('games_no', 'desc')->first();
        // dd($latestGame);
        $nextGameNo = $latestGame ? $latestGame->games_no + 1 : 1;
        $multiplierData = DB::table('keno_multipliers')->select('selections', 'id')->get(); 
        // dd($multiplierData);
        $betsAmount = [];
        if ($latestGame) {
            $nextGameNo = $latestGame->games_no + 1;
        
            $allBets = DB::table('bets')
                ->where('games_no', $nextGameNo)
                ->get(); 
        
            foreach ($multiplierData as $data) {
                $totalAmount = 0; 
        
                foreach ($allBets as $bet) {
                    $betsArray = json_decode($bet->bets, true); 
        
                    foreach ($betsArray as $betItem) {
                        if ($betItem['number'] == $data->id) {
                            $totalAmount += $betItem['amount'];
                        }
                    }
                }
                $betsAmount[$data->id] = $totalAmount;
            }
        }
        return view('head_tail.index', compact('latestGame', 'nextGameNo', 'multiplierData', 'betsAmount'));
    }
    public function add_winner(Request $request)
    {
        $request->validate([
            'selections' => 'required|array',
            'games_no'  => 'required|integer',
        ]);
    
        DB::table('admin_winner_results')->insert([
            'games_no' => $request->games_no,
            'gameId' => 20,
            'number'   => json_encode($request->selections),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    
        return redirect()->back()->with('success', 'Winner added successfully!');
    }
}
