@extends('admin.body.adminmaster')

@section('admin')
<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-md-12">
            <div class="card shadow-sm border-0 rounded-lg" style="background-color: white; color: black;">
                <div class="card-header d-flex justify-content-between align-items-center" 
                     style="background: #f8f9fa; color: black; border-bottom: 2px solid #FFD369; padding: 10px 15px;">
                    <h4 class="mb-0" style="font-size: 18px;"><i class="fas fa-history"></i> Red Black Bet History</h4>
                </div>
                <div class="card-body p-3">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover table-bordered text-center" style="background-color: white; color: black; border-color: #ddd;">

                            <thead style="background-color: #f8f9fa; color: black;">
                                <tr style="font-size: 14px; font-weight: 700; text-transform: uppercase;">
                                    <th>ID</th>
                                    <th>Username</th>
                                    <th>Amount</th>
                                    <th>Trade</th>
                                    <th>Win</th>
                                    <th>Bet</th>
                                    <th>Win No</th>
                                    <th>Game</th>
                                    <th>Order</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                </tr>
                            </thead>

                            <tbody>
                                @foreach($bets as $bet)
                                    <tr>
                                        <td style="font-weight: 600; color: #333;">{{ $bet->id }}</td>
                                        <td style="font-weight: 600; color: #333;">{{ $bet->userid }}</td>
                                        <td style="font-weight: 600; color: #333;">{{ $bet->amount }}</td>
                                        <td style="font-weight: 600; color: #333;">{{ $bet->trade_amount }}</td>
                                        <td style="font-weight: 600; color: #333;">{{ $bet->win_amount }}</td>
                                        <td style="font-weight: 600; color: #333;">{{ $bet->number }}</td>
                                        <td style="font-weight: 600; color: #333;">{{ $bet->win_number }}</td>
                                        <td style="font-weight: 600; color: #333;">{{ $bet->games_no }}</td>
                                        <td style="font-weight: 600; color: #333;">{{ $bet->order_id }}</td>
                                        <td style="font-weight: 600; color: #333;">
                                            @if($bet->status == 0)
                                                <span class="badge badge-info">Pending</span>
                                            @elseif($bet->status == 1)
                                                <span class="badge badge-success">Win</span>
                                            @elseif($bet->status == 2)
                                                <span class="badge badge-danger">Lose</span>
                                            @endif
                                        </td>
                                        <td style="font-weight: 600; color: #333;">{{ \Carbon\Carbon::parse($bet->created_at)->format('d-m-Y H:i') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>

                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- FontAwesome for Icons -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
@endsection
