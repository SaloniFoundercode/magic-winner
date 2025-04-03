@extends('admin.body.adminmaster')

@section('admin')
<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-md-12">
            <div class="card shadow-sm border-0 rounded-lg" style="background-color: #222831; color: #ddd;">
                <!-- Header -->
                <div class="card-header d-flex justify-content-between align-items-center" 
                     style="background: #31363F; color: white; border-bottom: 2px solid #FFD369; padding: 10px 15px;">
                    <h4 class="mb-0" style="font-size: 18px;"><i class="fas fa-history"></i> Andar Bahar History</h4>
                </div>

                <!-- Table Section -->
                <div class="card-body p-3">
                    <div class="table-responsive">
                        <table id="example" class="table table-sm table-hover table-bordered text-center" 
                               style="background-color: #393E46; color: #EEEEEE;">
                            <thead style="background-color: #FFD369; color: black;">
                                <tr style="font-size: 14px;">
                                    <th>ID</th>
                                    <th>Username</th>
                                    <th>Amount</th>
                                    <th>Commission</th>
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
                                <!-- Dynamic Data Will Be Loaded Here -->
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <nav aria-label="Page navigation">
                        <ul class="pagination pagination-sm justify-content-center mt-2">
                            <li class="page-item">
                                <a class="page-link" href="#" style="background-color: #FFD369; color: black; border-radius: 4px; padding: 5px 8px;">
                                    <i class="fas fa-angle-double-left"></i>
                                </a>
                            </li>
                            <li class="page-item">
                                <a class="page-link" href="#" style="background-color: #FFD369; color: black; border-radius: 4px; padding: 5px 8px;">
                                    <i class="fas fa-angle-left"></i>
                                </a>
                            </li>
                            <li class="page-item">
                                <a class="page-link" href="#" style="background-color: #FFD369; color: black; border-radius: 4px; padding: 5px 8px;">
                                    <i class="fas fa-angle-right"></i>
                                </a>
                            </li>
                            <li class="page-item">
                                <a class="page-link" href="#" style="background-color: #FFD369; color: black; border-radius: 4px; padding: 5px 8px;">
                                    <i class="fas fa-angle-double-right"></i>
                                </a>
                            </li>
                        </ul>
                    </nav>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- FontAwesome for Icons -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
@endsection
