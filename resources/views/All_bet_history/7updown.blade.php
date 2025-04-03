@extends('admin.body.adminmaster')

@section('admin')
<div class="container-fluid mt-5">
    <div class="row">
        <div class="col-md-12">
            <div class="card shadow-lg border-0 rounded-lg">
                <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                    <h2 class="mb-0"><i class="fas fa-history"></i> 7UP-Down Bet History</h2>
                </div>
                
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="example" class="table table-hover table-bordered text-center">
                            <thead class="thead-dark">
                                <tr>
                                    <th><i class="fas fa-hashtag"></i> ID</th>
                                    <th><i class="fas fa-user"></i> Username</th>
                                    <th><i class="fas fa-dollar-sign"></i> Amount</th>
                                    <th><i class="fas fa-chart-line"></i> Stop Multiplier</th>
                                    <th><i class="fas fa-sort-numeric-up-alt"></i> Number</th>
                                    <th><i class="fas fa-layer-group"></i> Game SR Num</th>
                                    <th><i class="fas fa-toggle-on"></i> Status</th>
                                    <th><i class="fas fa-trophy"></i> Win</th>
                                    <th><i class="fas fa-percentage"></i> Multiplier</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Dynamic Data Will Be Loaded Here -->
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <nav aria-label="Page navigation">
                        <ul class="pagination justify-content-center mt-3">
                            <li class="page-item">
                                <a class="page-link text-dark" href="#" aria-label="First">
                                    <i class="fas fa-angle-double-left"></i>
                                </a>
                            </li>
                            <li class="page-item">
                                <a class="page-link text-dark" href="#" aria-label="Previous">
                                    <i class="fas fa-angle-left"></i>
                                </a>
                            </li>
                            <li class="page-item">
                                <a class="page-link text-dark" href="#" aria-label="Next">
                                    <i class="fas fa-angle-right"></i>
                                </a>
                            </li>
                            <li class="page-item">
                                <a class="page-link text-dark" href="#" aria-label="Last">
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
