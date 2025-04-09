<div class="full_container">
<div class="full_container">
    <div class="inner_container">
        <nav id="sidebar">
            <div class="sidebar_blog_1">
                <div class="sidebar-header">
                    <div class="logo_section">
                       <a href="index.html"><img class="logo_icon img-responsive" src="images/logo/logo_icon.png" alt="#" /></a>
                    </div>
                </div>
                <div class="sidebar_user_info">
                    <div class="icon_setting">
                        
                    </div>
                    <div class="user_profle_side">
                        <div class="user_img"><img class="img-responsive" src="https://root.jupitergames.app/uploads/jupiter_logo.png" style="height:50px; width:100px;" alt="#" />
                        </div>
                        <div class="user_info">
                           <h6>Admin</h6>
                           <p><span class="online_animation"></span> Online</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="sidebar_blog_2">
              <h4>General</h4>
              <ul class="list-unstyled components">
                <li><a href="{{route('dashboard')}}"><i class="fa fa-dashboard yellow_color"></i> <span>Dashboard</span></a></li>
                <li><a href="{{route('users')}}"><i class="fa fa-user orange_color"></i> <span>Players</span></a></li>
                <li><a href="{{route('block.user.list')}}"><i class="fa fa-user orange_color"></i> <span>Block Player list</span></a></li>
                <li><a href="{{route('mlmlevel')}}"><i class="fa fa-list red_color"></i> <span>MlM Levels</span></a></li>
				<li>
                    <a href="#apps-xy" data-toggle="collapse" aria-expanded="true" class="dropdown-toggle"><i class="fa fa-object-group blue2_color"></i><span>Bet History</span></a>
                    <ul class="collapse list-unstyled" id="apps-xy">
                        <li><a href="{{ route('All_bet_history.andarbahar') }}">Andar Bahar</a></li>
                        <li><a href="{{ route('All_bet_history.color') }}">Wingo</a></li>
                        <li><a href="{{ route('All_bet_history.mines') }}">Mines</a></li>
                        <li><a href="{{ route('All_bet_history.dragonTiger') }}">Dragon Tiger</a></li>
                        <li><a href="{{ route('All_bet_history.jhandimunda') }}">Jhandi Munda</a></li>
                        <li><a href="{{ route('All_bet_history.hilo') }}">High Low</a></li>
                        <li><a href="{{ route('All_bet_history.redBlack') }}">Red & Black</a></li>
                        <li><a href="{{ route('All_bet_history.miniRoullete') }}">Mini Roullete</a></li>
                        <li><a href="{{ route('All_bet_history.hotairballoon') }}">Hot Air Balloon</a></li>
                        <li><a href="{{ route('All_bet_history.aviator') }}">Aviator</a></li>
                        <li><a href="{{ route('All_bet_history.trx') }}">Trx</a></li>
                        <li><a href="{{ route('All_bet_history.plinko') }}">Plinko</a></li>
                        <li><a href="{{ route('All_bet_history.headtail') }}">Head Tail</a></li>
                        <li><a href="{{ route('All_bet_history.7updown') }}">7 Up & 7 Down</a></li>
                        <li><a href="{{ route('All_bet_history.kino') }}">Kino</a></li>
                        <li><a href="{{ route('All_bet_history.teenPatti') }}">Teen Patti</a></li>
                        <li><a href="{{ route('All_bet_history.jackpot') }}">Jackpot</a></li>
                    </ul>
                </li>
                
                <li>
                    <a href="#saloni" data-toggle="collapse" aria-expanded="true" class="dropdown-toggle"><i class="fa fa-object-group blue2_color"></i><span>Bet Result</span></a>
                    <ul class="collapse list-unstyled" id="saloni">
                        <li><a href="{{ route('All_bet_result.ab') }}">Andar Bahar</a></li>
                        <li><a href="{{ route('All_bet_result.wingo') }}">Wingo</a></li>
                        <li><a href="{{ route('All_bet_result.mine') }}">Mines</a></li>
                        <li><a href="{{ route('All_bet_result.dt') }}">Dragon Tiger</a></li>
                        <li><a href="{{ route('All_bet_result.jm') }}">Jhandi Munda</a></li>
                        <li><a href="{{ route('All_bet_result.hl') }}">High Low</a></li>
                        <li><a href="{{ route('All_bet_result.rb') }}">Red & Black</a></li>
                        <li><a href="{{ route('All_bet_result.mr') }}">Mini Roullete</a></li>
                        <li><a href="{{ route('All_bet_result.hb') }}">Hot Air Balloon</a></li>
                        <li><a href="{{ route('All_bet_result.av') }}">Aviator</a></li>
                        <li><a href="{{ route('All_bet_result.tr') }}">Trx</a></li>
                        <li><a href="{{ route('All_bet_result.plnko') }}">Plinko</a></li>
                        <li><a href="{{ route('All_bet_result.ht') }}">Head Tail</a></li>
                        <li><a href="{{ route('All_bet_result.updown') }}">7 Up & 7 Down</a></li>
                        <li><a href="{{ route('All_bet_result.kn') }}">Kino</a></li>
                        <li><a href="{{ route('All_bet_result.tp') }}">Teen Patti</a></li>
                        <li><a href="{{ route('All_bet_result.jkpt') }}">Jackpot</a></li>
                    </ul>
                </li>
                <li>
                    <a href="#gupta" data-toggle="collapse" aria-expanded="true" class="dropdown-toggle"><i class="fa fa-object-group blue2_color"></i><span>Admin Winner Result</span></a>
                    <ul class="collapse list-unstyled" id="gupta">
                        <li><a href="{{ route('adminWinner.ab1') }}">Andar Bahar</a></li>
                        <li><a href="{{ route('adminWinner.wingo1') }}">Wingo</a></li>
                        <li><a href="{{ route('adminWinner.mines1') }}">Mines</a></li>
                        <li><a href="{{ route('adminWinner.dt1') }}">Dragon Tiger</a></li>
                        <li><a href="{{ route('adminWinner.jm1') }}">Jhandi Munda</a></li>
                        <li><a href="{{ route('adminWinner.hl1') }}">High Low</a></li>
                        <li><a href="{{ route('adminWinner.rb1') }}">Red & Black</a></li>
                        <li><a href="{{ route('adminWinner.mr1') }}">Mini Roullete</a></li>
                        <li><a href="{{ route('adminWinner.hb1') }}">Hot Air Balloon</a></li>
                        <li><a href="{{ route('adminWinner.aviator1') }}">Aviator</a></li>
                        <li><a href="{{ route('adminWinner.trx1') }}">Trx</a></li>
                        <li><a href="{{ route('adminWinner.plinko1') }}">Plinko</a></li>
                        <li><a href="{{ route('adminWinner.ht1') }}">Head Tail</a></li>
                        <li><a href="{{ route('adminWinner.updown1') }}">7 Up & 7 Down</a></li>
                        <li><a href="{{ route('adminWinner.kino1') }}">Kino</a></li>
                        <li><a href="{{ route('adminWinner.tp1') }}">Teen Patti</a></li>
                        <li><a href="{{ route('adminWinner.jkpt1') }}">Jackpot</a></li>
                    </ul>
                </li>
                
                <!--admin winner result-->
                 <li><a href="{{route('gift')}}"><i class="fa fa-table purple_color2"></i> <span>Gift</span></a></li>
				  <li><a href="{{route('giftredeemed')}}"><i class="fa fa-table purple_color2"></i> <span>Gift Redeemed History</span></a></li>
                <li><a href="{{route('banner')}}"><i class="fa fa-picture-o" aria-hidden="true"></i> <span> Activity & Banner</span></a></li> 
                <li><a href="{{route('salary.list')}}"><i class="fa fa-file blue1_color"></i> <span> Salary</span></a></li>
                <li>
                    <a href="#app13" data-toggle="collapse" aria-expanded="false" class="dropdown-toggle"><i class="fa fa-tasks  green_color"></i><span>Payin</span></a>
                        <ul class="collapse list-unstyled" id="app13">
                            <li><a href="{{ route('deposit', 1) }}">Pending</a></li>
                            <li><a href="{{ route('deposit', 2) }}">Success</a></li>
                            <li><a href="{{ route('deposit',3) }}">Reject</a></li>
                        </ul>
                </li> 
                <li>
                    <a href="#app20" data-toggle="collapse" aria-expanded="false" class="dropdown-toggle">
                    <i class="fa fa-tasks  green_color"></i><span>USDT Payin</span></a>
                        <ul class="collapse list-unstyled" id="app20">
                            <li><a href="{{ route('usdt_deposit', 1) }}">Pending</a></li>
                            <li><a href="{{ route('usdt_deposit', 2) }}">Success</a></li>
                            <li><a href="{{ route('usdt_deposit',3) }}">Reject</a></li>
                        </ul>
                </li>
                <li>
                    <a href="#app21" data-toggle="collapse" aria-expanded="false" class="dropdown-toggle">
                        <i class="fa fa-tasks  green_color"></i> <span> Withdrawal</span>
                    </a>
                    <ul class="collapse list-unstyled" id="app21">
                        <li><a href="{{ route('widthdrawl', 1) }}">Pending</a></li>
                        <li><a href="{{ route('widthdrawl', 2) }}">Success</a></li>
                        <li><a href="{{ route('widthdrawl',3) }}">Reject</a></li>
                    </ul>
                <li>
                    <a href="#app21" data-toggle="collapse" aria-expanded="false" class="dropdown-toggle">
                        <i class="fa fa-tasks  green_color"></i> <span>USDT Withdrawal</span></a>
                        <ul class="collapse list-unstyled" id="app21">
                            <li><a href="{{ route('usdt_widthdrawl', 1) }}">Pending</a></li>
                            <li><a href="{{ route('usdt_widthdrawl', 2) }}">Success</a></li>
                            <li><a href="{{ route('usdt_widthdrawl',3) }}">Reject</a></li>
                        </ul>
                </li>
            		<!--<li><a href="{{route('notification')}}"><i class="fa fa-bell  yellow_color"></i> <span>Notification</span></a></li>-->
              <!--      <li><a href="{{route('setting')}}"><i class="fa fa-info-circle  yellow_color"></i> <span>Setting</span></a></li>-->
            		<!--<li><a href="{{route('support_setting')}}"><i class="fa fa-info-circle  yellow_color"></i> <span>Support Setting </span></a></li> -->
            		<!--<li><a href="{{route('businessSetting.index')}}"><i class="fa fa-warning red_color"></i> <span>Business Setting</span></a></li>-->
              <!--      <li><a href="{{route('change_password')}}"><i class="fa fa-warning red_color"></i> <span>Change Password</span></a></li>-->
                <li><a href="{{route('auth.logout')}}"><i class="fa fa-line-chart yellow_color"></i> <span>Logout</span></a></li>
                    

                
                <!--{{-- <li>-->
                 <!--   <a href="contact.html">-->
                 <!--   <i class="fa fa-paper-plane red_color"></i> <span>Contact</span></a>-->
                 <!--</li>-->
                 <!--<li class="active">-->
                 <!--   <a href="#additional_page" data-toggle="collapse" aria-expanded="false" class="dropdown-toggle"><i class="fa fa-clone yellow_color"></i> <span>Additional Pages</span></a>-->
                 <!--   <ul class="collapse list-unstyled" id="additional_page">-->
                 <!--      <li>-->
                 <!--         <a href="profile.html">> <span>Profile</span></a>-->
                 <!--      </li>-->
                 <!--      <li>-->
                 <!--         <a href="project.html">> <span>Projects</span></a>-->
                 <!--      </li>-->
                 <!--      <li>-->
                 <!--         <a href="login.html">> <span>Login</span></a>-->
                 <!--      </li>-->
                 <!--      <li>-->
                 <!--         <a href="404_error.html">> <span>404 Error</span></a>-->
                 <!--      </li>-->
                 <!--   </ul>-->
                 <!--</li>-->
                 <!--<li><a href="map.html"><i class="fa fa-map purple_color2"></i> <span>Map</span></a></li>-->
                 <!--<li><a href="charts.html"><i class="fa fa-bar-chart-o green_color"></i> <span>Charts</span></a></li>-->
                 <!--<li><a href="settings.html"><i class="fa fa-cog yellow_color"></i> <span>Settings</span></a></li> --}}-->
              </ul>
            </div>
        </nav>
    </div>
</div>
            <!-- end sidebar -->