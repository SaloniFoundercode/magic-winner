<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Category;
use App\Models\Betlog;
use App\Models\AdminWinnerResult;
use App\Models\GameSetting;
use Illuminate\Support\Facades\Storage;
use DB;

class ColourPredictionController extends Controller
{
    public function colour_prediction_create($gameid)
    {
        $bets = Betlog::where('game_id', $gameid)
            ->leftJoin('game_settings', 'betlogs.game_id', '=', 'game_settings.id')
            ->select('betlogs.*', 'game_settings.winning_percentage as parsantage', 'game_settings.id as game_setting_id')
            ->limit(23)
            ->get();
        return view('colour_prediction.index')->with('bets', $bets)->with('gameid', $gameid);
    }
	public function fetchData($gameid)
    {
        $bets = BetLog::with('gameSetting:id,winning_percentage')
            ->where('game_id', $gameid)
            ->limit(23)
            ->get(['*']); 
    
        return response()->json(['bets' => $bets, 'gameid' => $gameid]);
    }
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'game_no' => 'required|unique:admin_winner_results,games_no',
        ]);
        $gameid = $request->game_id;
        $gamesno = $request->game_no;
        $number = $request->number;
        AdminWinnerResult::insert([
            'games_no' => $gamesno,
            'gameId' => $gameid,
            'number' => $number,
            'status' => 1,
        ]);
        return redirect()->back();
    }
    public function update(Request $request)
    {
        $gameId = $request->id;
        $percentage = $request->parsantage;
        $gameSetting = GameSetting::find($gameId);
        if ($gameSetting) {
            $gameSetting->winning_percentage = $percentage;
            $gameSetting->save();
        }
        return redirect()->back();
    } 
}
