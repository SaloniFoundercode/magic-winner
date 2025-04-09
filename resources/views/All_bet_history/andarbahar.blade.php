@extends('admin.body.adminmaster')

@section('admin')
<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-md-12">
            <div class="card shadow-sm border-0 rounded-lg" style="background-color: white; color: black;">
                <div class="card-header d-flex justify-content-between align-items-center" 
                     style="background: #f8f9fa; color: black; border-bottom: 2px solid #FFD369; padding: 10px 15px;">
                    <h4 class="mb-0" style="font-size: 18px;"><i class="fas fa-history"></i>Andar Bahar Bet History</h4>
                </div>
                <div class="card-body p-3">
                    <div class="table-responsive">
                            <table style="width: 100%; border: 1px solid #ddd; font-size: 14px; border-collapse: collapse;">
                                <thead style="background-color: #f8f9fa; color: black;">
                                    <tr>
                                        <th style="padding: 8px; border: 1px solid #ddd;">ID</th>
                                        <th style="padding: 8px; border: 1px solid #ddd;">Username</th>
                                        <th style="padding: 8px; border: 1px solid #ddd;">Amount</th>
                                        <th style="padding: 8px; border: 1px solid #ddd;">Trade</th>
                                        <th style="padding: 8px; border: 1px solid #ddd;">Win</th>
                                        <th style="padding: 8px; border: 1px solid #ddd;">Bet</th>
                                        <th style="padding: 8px; border: 1px solid #ddd;">Win No</th>
                                        <th style="padding: 8px; border: 1px solid #ddd;">Game</th>
                                        <th style="padding: 8px; border: 1px solid #ddd;">Order</th>
                                        <th style="padding: 8px; border: 1px solid #ddd;">Status</th>
                                        <th style="padding: 8px; border: 1px solid #ddd;">Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($bets as $bet)
                                    <tr>
                                        <td style="padding: 8px; border: 1px solid #ddd;">{{ $bet->id }}</td>
                                        <td style="padding: 8px; border: 1px solid #ddd;">{{ $bet->userid }}</td>
                                        <td style="padding: 8px; border: 1px solid #ddd;">{{ $bet->amount }}</td>
                                        <td style="padding: 8px; border: 1px solid #ddd;">{{ $bet->trade_amount }}</td>
                                        <td style="padding: 8px; border: 1px solid #ddd;">{{ $bet->win_amount }}</td>
                                        <td style="padding: 8px; border: 1px solid #ddd;">{{ $bet->number }}</td>
                                        <td style="padding: 8px; border: 1px solid #ddd;">{{ $bet->win_number }}</td>
                                        <td style="padding: 8px; border: 1px solid #ddd;">{{ $bet->games_no }}</td>
                                        <td style="padding: 8px; border: 1px solid #ddd;">{{ $bet->order_id }}</td>
                                        <td style="padding: 8px; border: 1px solid #ddd;">
                                            @if($bet->status == 0)
                                                <span style="background-color: #17a2b8; color: white; padding: 4px 8px; border-radius: 4px;">Pending</span>
                                            @elseif($bet->status == 1)
                                                <span style="background-color: #28a745; color: white; padding: 4px 8px; border-radius: 4px;">Win</span>
                                            @elseif($bet->status == 2)
                                                <span style="background-color: #dc3545; color: white; padding: 4px 8px; border-radius: 4px;">Loss</span>
                                            @endif
                                        </td>
                                        <td style="padding: 8px; border: 1px solid #ddd;">{{ \Carbon\Carbon::parse($bet->created_at)->format('d-m-Y H:i:s') }}</td>
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
