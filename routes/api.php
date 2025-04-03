<?php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\{PublicApiController,GameApiController,AviatorApiController,AgencyPromotionController,SalaryApiController,VipController,ZiliApiController,TestJilliController,SpribeApiController,MiniRoulleteController,HighLowgameApiController,TeenPattiController,KenoGameController,RedBlackController,JackpotController,HeadTailController,Jckpt2Controller,HotAirBalloonController};

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/vip_level',[VipController::class,'vip_level']);
Route::get('/vip_level_history',[VipController::class,'vip_level_history']);
Route::post('/add_money',[VipController::class,'receive_money']);


Route::controller(PublicApiController::class)->group(function () {
    Route::post('/otp-register',[PublicApiController::class,'otp_register']);
	Route::get('/image_all','image_all');
    Route::post('/register', 'registers');
    Route::post('/check_number', 'check_existsnumber');
    Route::post('/login', 'login');
    Route::get('/profile/{id}', 'Profile');
	Route::post('/update_profile','update_profile');
    Route::get('/slider','slider_image_view');
    Route::post('/changepassword','changepassword');
    // Route::post('/forget_Password','resetPassword');
    Route::post('/addAccount','addAccount');
    Route::get('/accountView','accountView');
    // Route::post('/payin','payin');
    // Route::get('/checkPayment','checkPayment');
    // Route::get('/payin-successfully','redirect_success')->name('payin.successfully');
    // Route::post('/withdraw','withdraw');
    // Route::get('/withdraw-history','withdrawHistory');
    // Route::get('/deposit-history','deposit_history');
    Route::get('/account-delete/{id}','accountDelete');
    // Route::post('/gift_cart_apply','giftCartApply');
    // Route::get('/gift_redeem_list','claim_list');
    Route::get('/customer_service','customer_service');
// 	Route::post('/wallet_transfers','wallet_transfer');
// 	Route::post('/main_wallet_transfers','main_wallet_transfer');
// 	Route::post('/winning_wallet_transfers','winning_wallet_transfers');
// 	Route::get('/version_apk_link','versionApkLink');
	Route::post('/salary_list','salary_list');
// 	Route::get('/betting_rebate','betting_rebate');
//     Route::get('/betting_rebate_history','betting_rebate_history');
// 	Route::post('/invitation_bonus_claim','invitation_bonus_claim');
// 	Route::get('/updateMissingSpribeAndJilliData','updateSpribeIdForUsers');
// 	Route::get('/commission_details','commission_details');
});
Route::controller(AgencyPromotionController::class)->group(function () {
    Route::get('/agency-promotion-data-{id}', 'promotion_data');
	Route::get('/new-subordinate', 'new_subordinate');
	Route::get('/tier', 'tier');
	Route::post('/subordinate-data','subordinate_data');
	Route::get('/turnovers','turnover_new');
});
Route::controller(GameApiController::class)->group(function () {
     Route::post('/bets', 'bet');
      Route::post('/dragon_bet', 'dragon_bet');
     Route::get('/all-win-amount', 'all_win_amount');
     Route::get('/results','results');
     Route::get('/last-five-result','lastFiveResults');
     Route::get('/last_result','lastResults');
     Route::post('/bet_history','bet_history');
     Route::get('/cron/{game_id}/','cron');
     /// mine game route //
    Route::post('/mine_bet','mine_bet');
    Route::post('/mine_cashout','mine_cashout');
    Route::get('/mine_result','mine_result');
    Route::get('/mine_multiplier','mine_multiplier');
    Route::post('/plinko_bet','plinkoBet');
    Route::get('/plinko_index_list','plinko_index_list');
    Route::get('/plinko_result','plinko_result');
    Route::get('/plinko_cron','plinko_cron');
    Route::post('/plinko_multiplier','plinko_multiplier'); 
});
    Route::controller(AviatorApiController::class)->group(function () {
    Route::get('/aviator_bet','aviatorBet');
    Route::post('/aviator_cashout','aviator_cashout');
    Route::post('/aviator_history','aviator_history');
    Route::get('/aviator_last_five_result','last_five_result');
    Route::get('/aviator_bet_cancel','bet_cancel');
    Route::get('/result_half_new','result_half_new');
    Route::post('/result_insert_new','result_insert_new');
    });

    Route::controller(SalaryApiController::class)->group(function () {
        // Route::get('/aviator_salary', 'aviator_salary');
        Route::get('/daily_bonus','dailyBonus');
    	Route::get('/monthly_bonus','monthlyBonus');
    });
    Route::post('/usdt_payin',[PublicApiController::class,'payin_usdt']);
    // Route::post('/payin_call_back',[PublicApiController::class,'payin_call_back']);
    // Route::get('/end_user_register',[TestJilliController::class,'end_user_register']);
    // Route::get('/get_all_game_list',[TestJilliController::class,'get_all_game_list']);
    // Route::get('/get_game_url_gameid',[TestJilliController::class,'get_game_url_gameid']);
    // Route::get('/add_amount_to_user',[TestJilliController::class,'transfer_amount_to_user']);
    // Route::get('/get_jilli_transaction_details',[TestJilliController::class,'get_jilli_transaction_details']);
    // Route::get('/wallet_deduct_from_user',[TestJilliController::class,'wallet_deduct_from_user']);
    // Route::get('/get_bet_history',[TestJilliController::class,'get_bet_history']);
    // Route::get('/get_reseller_info',[TestJilliController::class,'get_reseller_info']);
    // Route::post('/user_register',[ZiliApiController::class,'user_register']);
    // Route::post('/all_game_list',[ZiliApiController::class,'all_game_list']);
    // Route::post('/all_game_list_test',[ZiliApiController::class,'all_game_list_test']);
    // Route::post('/get_game_url',[ZiliApiController::class,'get_game_url']);
    // Route::post('/get_jilli_transactons_details',[ZiliApiController::class,'get_jilli_transactons_details']);
    // Route::post('/jilli_deduct_from_wallet',[ZiliApiController::class,'jilli_deduct_from_wallet']);
    // Route::post('/jilli_get_bet_history',[ZiliApiController::class,'jilli_get_bet_history']);
    // Route::post('/add_in_jilli_wallet ',[ZiliApiController::class,'add_in_jilli_wallet']);
    // Route::post('/update_main_wallet ',[ZiliApiController::class,'update_main_wallet']);
    // Route::post('/get_jilli_wallet ',[ZiliApiController::class,'get_jilli_wallet']);
    // Route::post('/update_jilli_wallet ',[ZiliApiController::class,'update_jilli_wallet']);
    // Route::post('/update_jilli_to_user_wallet ',[ZiliApiController::class,'update_jilli_to_user_wallet']);


    // Route::get('/test_get_user_info ',[ZiliApiController::class,'test_get_user_info']);
    // Route::get('/get-reseller-info/{manager_key?}',[ZiliApiController::class,'get_reseller_info']);
    // Route::controller(SpribeApiController::class)->group(function () {
    //     Route::get('/get_reseller_info', 'get_reseller_info');
    //     Route::post('/get_spribe_game_urls','get_spribe_game_urls');
    // 	Route::post('/spribe_betting_history','spribe_betting_history');
    // 	Route::post('/spribe_all_betting_history','spribe_all_betting_history');
    // 	Route::post('/sprb/spribe/callback','handleCallback');
    // 	Route::post('/spribe_user_register','spribe_user_register'); 
    // 	Route::post('/spribe_transactons_details','spribe_transactons_details'); 
    // 	Route::post('/scribe_deduct_from_wallet','scribe_deduct_from_wallet');
    // 	Route::post('/get_spribe_wallet ','get_spribe_wallet');
    // 	Route::post('/add_in_spribe_wallet ','add_in_spribe_wallet');
    // 	Route::post('/update_spribe_wallet ','update_spribe_wallet');
    // 	Route::post('/update_spribe_to_user_wallet ','update_spribe_to_user_wallet');
    // });
    Route::controller(MiniRoulleteController::class)->group(function(){
        Route::post('mini-bet', 'mini_bets');
        Route::post('bet-history', 'bet_history');
        Route::post('mini_results', 'mini_results');
        Route::get('mini_cron/{game_id}','mini_cron');
        Route::post('win-amount', 'win_amount');
     });
    Route::controller(HighLowgameApiController::class)->group(function () {
        Route::post('/high_low_bet', 'high_low_bet');
        Route::get('/high_low_results','high_low_results');
          Route::get('/high_low_win_amount','high_low_win_amount');
        Route::get('/high_low_bet_history','high_low_bet_history');
        Route::get('/high_low_cron/{game_id}/','high_low_cron');
    });
    Route::controller(TeenPattiController::class)->group(function(){
        Route::post('bet-result','teenPatti_results'); 
        Route::post('bet','bets'); 
        Route::post('bethistory', 'bet_history');
        Route::get('teen-patti-cron/{game_id}/','teen_patti_cron');
        Route::post('teen-patti-win-amt','teen_patti_win_amt');
     });
    Route::controller(KenoGameController::class)->group(function(){
        Route::post('keno-bet', 'bets');
        Route::get('keno-cron/{game_id}/','keno_cron');
        Route::post('/keno_result','keno_result');
        Route::post('keno-bet-history', 'bet_history');
        Route::post('/keno_multiplier', 'keno_multipliers');
        Route::post('keno-win-amount', 'keno_win_amount');
    });
    Route::controller(RedBlackController::class)->group(function(){
        Route::get('/result','results');
        Route::post('/rb_bets', 'rb_bets');
        Route::post('/win_amount', 'win_amount');
        Route::get('/bet_history' ,'bet_history'); 
    });
    Route::controller(JackpotController::class)->group(function(){
        Route::post('/jackpot-bet', 'jackpot_bet');
        Route::get('jackpot_cron/{game_id}','jackpot_cron');
        Route::post('/jackpot_results', 'jackpot_results');
        Route::post('/jack_five_result','jack_five_result');
        Route::post('/jackpot_history', 'jackpot_history');
        Route::post('/win_amount', 'win_amount');
    });
    Route::controller(HeadTailController::class)->group(function(){
        Route::post('/headtail-bet', 'headtail_bet');
        Route::get('headtail_cron/{game_id}','headtail_cron');
        Route::post('/headtail_results', 'headtail_results');
        Route::post('/headtail_five_result','headtail_five_result');
        Route::post('/headtail_history', 'headtail_history');
        Route::post('/headtail_win_amount', 'headtail_win_amount');
    });
    //jackpot testing
    Route::controller(Jckpt2Controller::class)->group(function(){
        Route::post('/jack-bet', 'jack_bet');
        Route::get('jack_cron/{game_id}', 'jack_cron');
    });
    //Hot Air Balloon
     Route::controller(HotAirBalloonController::class)->group(function (){
     Route::post('/balloon_bet','balloon_bet');
     Route::get('/balloon_history' , 'balloon_history');
     Route::get('/hot_last_five_result' , 'last_five_result');
     Route::post('/balloon_bet_cancle' , 'balloon_bet_cancle');
     Route::get('get-image','get_image');
     Route::post('balloon-cashout','balloon_cashout');
     Route::post('update-image','post_image');
 });