<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\{User,Bet,AviatorBet,PlinkoBet};
use Illuminate\Support\Facades\Storage;
use Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class AdminWinnerController extends Controller
{
    public function ab_winner()
    {
        $latestGame = DB::table('bet_results')->orderByDesc('games_no')->where('game_id', 13)->first();
        $nextGameNo = ($latestGame->games_no ?? 0) + 1;
    
        return view('adminWinner.ab1', [
            'latestGame'    => $latestGame,
            'nextGameNo'    => $nextGameNo,
        ]);
    }
    public function addWinner(Request $request)
    {
        $request->validate([
        'game_type' => 'required|in:Andar,Bahar',
        'games_no'  => 'required|integer',
        ]);
        $number = $request->game_type === 'Andar' ? 1 : 2;
        DB::table('admin_winner_results')->insert([
            'games_no'   => $request->games_no,
            'gameId'     => 13,
            'number'     => $number, 
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        return back()->with('success', 'Winner added successfully!');
    }
    
    // wingo
    public function wingo_winner()
    {
        $latestGame = DB::table('bet_results')->orderByDesc('games_no')->where('game_id', [1,2,3,4])->first();
        $nextGameNo = ($latestGame->games_no ?? 0) + 1;
    
        return view('adminWinner.wingo1', [
            'latestGame'    => $latestGame,
            'nextGameNo'    => $nextGameNo,
        ]);
    }
    public function allWingo(Request $request)
    {
        $request->validate([
            'game_type' => 'required|integer',
            'games_no'   => 'required|integer',
            'game_id'   => 'required|integer',
        ]);
        DB::table('admin_winner_results')->insert([
            'games_no'   => $request->games_no,
            'gameId'     => $request->game_id,
            'number'     => $request->game_type,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    
        return back()->with('success', 'Winner added successfully!');
    }

    //trx
    public function trx_winner()
    {
        $latestGame = DB::table('bet_results')->orderByDesc('games_no')->where('game_id', [6,7,8,9])->first();
        $nextGameNo = ($latestGame->games_no ?? 0) + 1;
    
        return view('adminWinner.trx1', [
            'latestGame'    => $latestGame,
            'nextGameNo'    => $nextGameNo,
        ]);
    }
    public function trxAdd(Request $request)
    {
        $request->validate([
            'game_type' => 'required|integer',
            'games_no'   => 'required|integer',
            'game_id'   => 'required|integer',
        ]);
        DB::table('admin_winner_results')->insert([
            'games_no'   => $request->games_no,
            'gameId'     => $request->game_id,
            'number'     => $request->game_type,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        return back()->with('success', 'Winner added successfully!');
    }
    //mines
    public function mines_winner()
    {
        $bets = DB::table('mine_game_bets')->where('game_id', 12)->orderBy('created_at', 'desc')->paginate(10);
        return view('adminWinner.mines1', compact('bets'));
    }
    //dragon tiger
    public function dt_winner()
    {
       $latestGame = DB::table('bet_results')->orderByDesc('games_no')->where('game_id', 10)->first();
        $nextGameNo = ($latestGame->games_no ?? 0) + 1;
        // $nextGameNo = $latestGame->games_no;
    
        return view('adminWinner.dt1', [
            'latestGame'    => $latestGame,
            'nextGameNo'    => $nextGameNo,
        ]);
    }
    public function dtWinner(Request $request)
    {
        $request->validate([
    'game_type' => 'required|in:Dragon,Tiger,Tie',
    'games_no'  => 'required|integer',
]);

$number = $request->game_type === 'Dragon' 
    ? 1 
    : ($request->game_type === 'Tiger' 
        ? 2 
        : 3);

DB::table('admin_winner_results')->insert([
    'games_no'   => $request->games_no,
    'gameId'     => 10,
    'number'     => $number, 
    'created_at' => now(),
    'updated_at' => now(),
]);

return back()->with('success', 'Winner added successfully!');

    }
    //jhandi munda
    public function jm_winner()
    {
        $bets = DB::table('bet_results')->where('game_id', 23)->orderBy('created_at', 'desc')->paginate(10);
        return view('adminWinner.jm1', compact('bets'));
    }
    public function hilo_winner()
    {
        $bets = DB::table('bet_results')->where('game_id', 15)->orderBy('created_at', 'desc')->paginate(10);
        return view('adminWinner.hl1', compact('bets'));
    }
    public function rb_winner()
    {
        $bets = DB::table('bet_results')->where('game_id', 21)->orderBy('created_at', 'desc')->paginate(10);
        return view('adminWinner.rb1', compact('bets'));
    }
    public function mr_winner()
    {
        $bets = DB::table('mini_roulette_result')
            ->where('game_id', 14)
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('adminWinner.mr1', compact('bets'));
    }
    public function hb_winner()
    {
        $bets = DB::table('balloon_result')->where('game_id', 25)->orderBy('datetime', 'desc')->paginate(10);
        return view('adminWinner.hb1', compact('bets'));
    }
    public function aviator_winner()
    {
        $bets = DB::table('aviator_results')->where('game_id', 5)->orderBy('created_at', 'desc')->paginate(10);
        return view('adminWinner.aviator1', compact('bets'));
    }
    public function plinko_winner()
    {
        $bets = DB::table('plinko_bets')->where('game_id', 11)->orderBy('created_at', 'desc')->paginate(10);
        return view('adminWinner.plinko1', compact('bets'));
    }
    public function ht_winner()
    {
        $latestGame = DB::table('bet_results')->orderByDesc('games_no')->where('game_id', 13)->first();
        $nextGameNo = ($latestGame->games_no ?? 0) + 1;
    
        return view('adminWinner.ht1', [
            'latestGame'    => $latestGame,
            'nextGameNo'    => $nextGameNo,
        ]);
    }
    public function updown_winner()
    {
        $bets = DB::table('bet_results')->where('game_id', 22)->orderBy('created_at', 'desc')->paginate(10);
        return view('adminWinner.updown1', compact('bets'));
    }
    public function kino_winner()
    {
        $latestGame = DB::table('keno_bet_result')->orderByDesc('games_no')->first();
        $nextGameNo = ($latestGame->games_no ?? 0) + 1;
    
        $multipliers = DB::table('keno_multipliers')->select('selections', 'id')->get();
        $bets = DB::table('bets')->where('games_no', $nextGameNo)->get();
    
        $betsAmount = $multipliers->mapWithKeys(function ($multiplier) use ($bets) {
            $total = 0;
            foreach ($bets as $bet) {
                $betItems = json_decode($bet->bets, true);
                if (is_array($betItems)) {
                    foreach ($betItems as $item) {
                        if (($item['number'] ?? null) == $multiplier->id) {
                            $total += $item['amount'];
                        }
                    }
                }
            }
            return [$multiplier->id => $total];
        });
    
        return view('adminWinner.kino1', [
            'latestGame'    => $latestGame,
            'nextGameNo'    => $nextGameNo,
            'multiplierData'=> $multipliers,
            'betsAmount'    => $betsAmount
        ]);
    }
    
    public function update_winner(Request $request)
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
    
        return back()->with('success', 'Winner added successfully!');
    }
    
    
    public function tp_winner()
    {
        $bets = DB::table('teen_patti_bet_result')->where('game_id', 17)->orderBy('created_at', 'desc')->paginate(10);
        return view('adminWinner.tp1', compact('bets'));
    }
    public function jckpt_winner()
    {
        $bets = DB::table('bet_results')->where('game_id', 19)->orderBy('created_at', 'desc')->paginate(10);
    
        return view('adminWinner.jkpt1', compact('bets'));
    }

}