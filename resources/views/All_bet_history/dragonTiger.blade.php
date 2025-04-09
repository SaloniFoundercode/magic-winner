@extends('admin.body.adminmaster')

@section('admin')
<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-md-12">
            <div class="card shadow-sm border-0 rounded-lg" style="background-color: white; color: black;">
                <div class="card-header d-flex justify-content-between align-items-center" 
                     style="background: #f8f9fa; color: black; border-bottom: 2px solid #FFD369; padding: 10px 15px;">
                    <h4 class="mb-0" style="font-size: 18px;"><i class="fas fa-history"></i>Dragon Tiger Bet History</h4>
                </div>
                <div class="card-body p-3">
                    <div class="table-responsive">
                        <table id="example" class="table table-sm table-hover table-bordered text-center" 
                               style="background-color: white; color: black; border-color: #ddd;">
                            <table class="table">
                                <thead style="background-color: #f8f9fa; color: black;">
                                <tr style="font-size: 14px;">
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
                                    <td>{{ $bet->id }}</td>
                                    <td>{{ $bet->userid }}</td>
                                    <td>{{ $bet->amount }}</td>
                                    <td>{{ $bet->trade_amount }}</td>
                                    <td>{{ $bet->win_amount }}</td>
                                    <td>{{ $bet->number }}</td>
                                    <td>{{ $bet->win_number }}</td>
                                    <td>{{ $bet->games_no }}</td>
                                    <td>{{ $bet->order_id }}</td>
                                    <td>
                                        @if($bet->status == 1)
                                            <span class="badge badge-success">Win</span>
                                        @elseif($bet->status == 0)
                                            <span class="badge badge-danger">Lost</span>
                                        @else
                                            <span class="badge badge-warning">Pending</span>
                                        @endif
                                    </td>
                                    <td>{{ \Carbon\Carbon::parse($bet->created_at)->format('d M Y h:i A') }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                            </table>
                            <div class="d-flex justify-content-center">
                                {{ $bets->links() }}
                            </div>

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
