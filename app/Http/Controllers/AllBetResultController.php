<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\{User,Bet,AviatorBet,PlinkoBet};
use Illuminate\Support\Facades\Storage;
use Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class AllBetResultController extends Controller
{
    public function andarbahar_result()
    {
        $bets = DB::table('bet_results')->where('game_id', 13)->orderBy('created_at', 'desc')->paginate(10);
        return view('All_bet_result.ab', compact('bets'));
    }
    public function wingo_result()
    {
        $bets = DB::table('bet_results')->whereIn('game_id', [1, 2, 3, 4])->orderBy('created_at', 'desc')->paginate(10);
        return view('All_bet_result.wingo', compact('bets'));
    }
    public function trx_result()
    {
        $bets = DB::table('bet_results')->where('game_id', [6,7,8,9])->orderBy('created_at', 'desc')->paginate(10);
        return view('All_bet_result.tr', compact('bets'));
    }
    public function mines_result()
    {
        $bets = DB::table('mine_game_bets')->where('game_id', 12)->orderBy('created_at', 'desc')->paginate(10);
        return view('All_bet_result.mine', compact('bets'));
    }
    public function dragontiger_result()
    {
        $bets = DB::table('bet_results')->where('game_id', 10)->orderBy('created_at', 'desc')->paginate(10);
        return view('All_bet_result.dt', compact('bets'));
    }
    public function jhandimunda_result()
    {
        $bets = DB::table('bet_results')->where('game_id', 23)->orderBy('created_at', 'desc')->paginate(10);
        return view('All_bet_result.jm', compact('bets'));
    }
    public function hilo_result()
    {
        $bets = DB::table('bet_results')->where('game_id', 15)->orderBy('created_at', 'desc')->paginate(10);
        return view('All_bet_result.hl', compact('bets'));
    }
    public function redBlack_result()
    {
        $bets = DB::table('bet_results')->where('game_id', 21)->orderBy('created_at', 'desc')->paginate(10);
        return view('All_bet_result.rb', compact('bets'));
    }
    public function miniRoullete_result()
    {
        $bets = DB::table('mini_roulette_result')
            ->where('game_id', 14)
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('All_bet_result.mr', compact('bets'));
    }
    public function hotairballoon_result()
    {
        $bets = DB::table('balloon_result')->where('game_id', 25)->orderBy('datetime', 'desc')->paginate(10);
        return view('All_bet_result.hb', compact('bets'));
    }
    public function aviator_result()
    {
        $bets = DB::table('aviator_results')->where('game_id', 5)->orderBy('created_at', 'desc')->paginate(10);
        return view('All_bet_result.av', compact('bets'));
    }
    public function plinko_result()
    {
        $bets = DB::table('plinko_bets')->where('game_id', 11)->orderBy('created_at', 'desc')->paginate(10);
        return view('All_bet_result.plnko', compact('bets'));
    }
    public function headtail_result()
    {
        $bets = DB::table('bet_results')->where('game_id', 20)->orderBy('created_at', 'desc')->paginate(10);
        return view('All_bet_result.ht', compact('bets'));
    }
    public function updown_result()
    {
        $bets = DB::table('bet_results')->where('game_id', 22)->orderBy('created_at', 'desc')->paginate(10);
        return view('All_bet_result.updown', compact('bets'));
    }
    public function kino_result()
    {
        $bets = DB::table('keno_bet_result')->where('game_id', 16)->orderBy('created_at', 'desc')->paginate(10);
        return view('All_bet_result.kn', compact('bets'));
    }
    public function teenPatti_result()
    {
        $bets = DB::table('teen_patti_bet_result')->where('game_id', 17)->orderBy('created_at', 'desc')->paginate(10);
        return view('All_bet_result.tp', compact('bets'));
    }
    public function jackpot_result()
    {
        $bets = DB::table('bet_results')->where('game_id', 19)->orderBy('created_at', 'desc')->paginate(10);
    
        return view('All_bet_result.jkpt', compact('bets'));
    }

}