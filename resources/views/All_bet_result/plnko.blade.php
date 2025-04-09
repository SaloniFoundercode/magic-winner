@extends('admin.body.adminmaster')

@section('admin')
<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-md-12">
            <div class="card shadow-sm border-0 rounded-lg" style="background-color: white; color: black;">
                <div class="card-header d-flex justify-content-between align-items-center" 
                     style="background: #f8f9fa; color: black; border-bottom: 2px solid #FFD369; padding: 10px 15px;">
                    <h4 class="mb-0" style="font-size: 18px;"><i class="fas fa-history"></i>Plinko Result</h4>
                </div>
                <div class="card-body p-3">
                    <div class="table-responsive">
                        <table id="example" class="table table-sm table-hover table-bordered text-center" 
                               style="background-color: white; color: black; border-color: #ddd;">
                            <table class="table">
                                <thead style="background-color: #f8f9fa; color: black;">
                                <tr style="font-size: 14px;">
                                    <th>Id</th>
                                    <th>Type</th>
                                    <th>multiplier</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($bets as $bet)
                                    <tr>
                                        <td>{{ $bet->id }}</td>
                                        <td>
                                            @if ($bet->type == 1)
                                                <span class="badge bg-success">.</span>
                                            @elseif ($bet->type == 2)
                                                <span class="badge bg-warning">.</span>
                                            @elseif ($bet->type == 3)
                                                <span class="badge bg-danger">.</span>
                                            @else
                                                <span class="badge bg-secondary">{{ $bet->type }}</span>
                                            @endif
                                        </td>
                                        <td>{{ $bet->multipler }}</td>
                                        <td>{{ $bet->created_at }}</td>
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
