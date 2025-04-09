<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\{User,Bet,AviatorBet,PlinkoBet};
use Illuminate\Support\Facades\Storage;
use Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class AllBetHistoryController extends Controller
{
    public function andarbahar()
    {
        $bets = DB::table('bets')->where('game_id', 13)->orderBy('created_at', 'desc')->paginate(10);
        return view('All_bet_history.andarbahar', compact('bets'));
    }
    public function wingo()
    {
        $bets = DB::table('bets')->whereIn('game_id', [1, 2, 3, 4])->orderBy('created_at', 'desc')->paginate(10);
        return view('All_bet_history.color', compact('bets'));
    }
    public function trx()
    {
        $bets = DB::table('bets')->where('game_id', [6,7,8,9])->orderBy('created_at', 'desc')->paginate(10);
        return view('All_bet_history.trx', compact('bets'));
    }
    public function mines()
    {
        $bets = DB::table('mine_game_bets')->where('game_id', 12)->orderBy('created_at', 'desc')->paginate(10);
        return view('All_bet_history.mines', compact('bets'));
    }
    public function dragontiger()
    {
        $bets = DB::table('bets')->where('game_id', 10)->orderBy('created_at', 'desc')->paginate(10);
        return view('All_bet_history.dragonTiger', compact('bets'));
    }
    public function jhandimunda()
    {
        $bets = DB::table('bets')->where('game_id', 23)->orderBy('created_at', 'desc')->paginate(10);
        return view('All_bet_history.jhandimunda', compact('bets'));
    }
    public function hilo()
    {
        $bets = DB::table('high_low_bets')->where('game_id', 15)->orderBy('created_at', 'desc')->paginate(10);
        return view('All_bet_history.hilo', compact('bets'));
    }
    public function redBlack()
    {
        $bets = DB::table('bets')->where('game_id', 21)->orderBy('created_at', 'desc')->paginate(10);
        return view('All_bet_history.redBlack', compact('bets'));
    }
    public function miniRoullete()
    {
        $bets = DB::table('mini_roulette_bets')
            ->where('game_id', 14)
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('All_bet_history.miniRoullete', compact('bets'));
    }
    public function hotairballoon()
    {
        $bets = DB::table('balloon_bet')->where('game_id', 27)->orderBy('created_at', 'desc')->paginate(10);
        return view('All_bet_history.hotairballoon', compact('bets'));
    }
    public function aviator()
    {
        $bets = DB::table('aviator_bets')->where('game_id', 5)->orderBy('created_at', 'desc')->paginate(10);
        return view('All_bet_history.aviator', compact('bets'));
    }
    public function plinko()
    {
        $bets = DB::table('plinko_bets')->where('game_id', 11)->orderBy('created_at', 'desc')->paginate(10);
        return view('All_bet_history.plinko', compact('bets'));
    }
    public function headtail()
    {
        $bets = DB::table('bets')->where('game_id', 20)->orderBy('created_at', 'desc')->paginate(10);
        return view('All_bet_history.headtail', compact('bets'));
    }
    public function updown()
    {
        $bets = DB::table('bets')->where('game_id', 22)->orderBy('created_at', 'desc')->paginate(10);
        return view('All_bet_history.7updown', compact('bets'));
    }
    public function kino()
    {
        $bets = DB::table('keno_bet')->where('game_id', 16)->orderBy('created_at', 'desc')->paginate(10);
        return view('All_bet_history.kino', compact('bets'));
    }
    public function teenPatti()
    {
        $bets = DB::table('teen_patti_bet')->where('game_id', 17)->orderBy('created_at', 'desc')->paginate(10);
        return view('All_bet_history.teenPatti', compact('bets'));
    }
    public function jackpot()
    {
        $bets = DB::table('bets')->where('game_id', 19)->orderBy('created_at', 'desc')->paginate(10);
    
        return view('All_bet_history.jackpot', compact('bets'));
    }

}