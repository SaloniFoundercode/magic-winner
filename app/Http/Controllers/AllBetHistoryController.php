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
// public function all_bet_history(string $id)
// {
//     $perPage = 100;
//     if (in_array($id, [1, 2, 3, 4, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23])) {
//         $bets = Bet::with('user:name,id')
//             ->where('game_id', $id)
//             ->orderByDesc('id')
//             ->paginate($perPage);
//         $bets->getCollection()->transform(function ($item) {
//             $item->status = match($item->status) {
//                 1 => 'win',
//                 2 => 'loss',
//                 0 => 'pending',
//                 default => $item->status
//             };
//             $item->number = match($item->number) {
//                 10 => 'Green',
//                 20 => 'Voilet',
//                 30 => 'Red',
//                 40 => 'Big',
//                 50 => 'Small',
//                 default => $item->number
//             };
//             return $item;
//         });
//         $total_bet = Bet::where('game_id', $id)->count();
//         return view('All_bet_history.color', compact('bets', 'total_bet'));
//     } elseif ($id == 5) {
//         $bets = AviatorBet::with('user:name,id')
//             ->where('game_id', $id)
//             ->orderByDesc('id')
//             ->paginate($perPage);
//         $bets->getCollection()->transform(function ($item) {
//             $item->status = match($item->status) {
//                 1 => 'win',
//                 2 => 'loss',
//                 0 => 'pending',
//                 default => $item->status
//             };
//             return $item;
//         });
//         $total_bet = AviatorBet::where('game_id', $id)->count();
//         return view('All_bet_history.aviator', compact('bets', 'total_bet'));
//     } elseif ($id == 11) {
//         $bets = PlinkoBet::where('game_id', $id)
//             ->orderByDesc('id')
//             ->paginate($perPage);
//         $bets->getCollection()->transform(function ($item) {
//             $item->type = match($item->type) {
//                 1 => 'Green',
//                 2 => 'yellow',
//                 3 => 'red',
//                 default => $item->type
//             };
//             return $item;
//         });
//         $total_bet = PlinkoBet::where('game_id', $id)->count();
//         return view('All_bet_history.plinko', compact('bets', 'total_bet'));
//     }
// }

    public function andarbahar()
    {
        return view('All_bet_history.andarbahar');
    }
}