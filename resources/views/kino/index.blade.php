@extends('admin.body.adminmaster')

@section('admin')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="white_shd full margin_bottom_30">
                
                <div class="table_section padding_infor_info">
                    <div class="table-responsive-sm">
                        <h4>Period No - {{ $nextGameNo ?? 'N/A' }}</h4>
                        
                        @for($row = 0; $row < 4; $row++)
                            <div class="d-flex justify-content-center mb-2">
                                @for($col = 1; $col <= 10; $col++) 
                                    @php $num = $row * 10 + $col; @endphp
                                    <div class="number-box" style="
                                        width: 55px; height: 55px; line-height: 55px;
                                        text-align: center; font-size: 20px; font-weight: bold;
                                        color: white; text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.3);
                                        border-radius: 50%; margin: 5px;
                                        background: linear-gradient(135deg, #006800, #32CD32);
                                        box-shadow: 2px 2px 10px rgba(0, 0, 0, 0.2);
                                        transition: transform 0.2s, box-shadow 0.2s;"
                                        data-id="{{ $num }}">
                                        {{ $num }}
                                    </div>
                                @endfor
                            </div>
                        @endfor
                        <form action="{{ route('kino.add') }}" method="post" class="d-flex align-items-center justify-content-center">
                            @csrf
                            <div class="form-group mx-5">
                                <label for="games_no" class="mr-6">Game No:</label>
                                <input type="text" id="games_no" name="games_no" 
                                       class="form-control form-control-sm font-weight-bold" 
                                       value="{{ $nextGameNo ?? '' }}" style="width: 150px;">
                            </div>
                            <div class="form-group mx-3">
                                <label for="selections" class="mr-2">Select Multiple Number:</label>
                                <select id="selections" name="selections[]" class="form-control form-control-sm font-weight-bold" style="width: 100px;" multiple>
                                    <option value="">Select</option>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary btn-sm mx-3">Submit</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    let selectedNumbers = [];

    $(".number-box").click(function() {
        let num = $(this).text().trim();

        if (selectedNumbers.includes(num)) {
            selectedNumbers = selectedNumbers.filter(n => n !== num);
            $(this).css("background", "linear-gradient(135deg, #006800, #32CD32)");
        } else {
            if (selectedNumbers.length < 10) {
                selectedNumbers.push(num);
                $(this).css("background", "linear-gradient(135deg, #FFD700, #FFA500)"); 
            }
        }

        // Update select box
        $("#selections").html('<option value="">Select</option>'); 
        selectedNumbers.forEach(number => {
            $("#selections").append(`<option value="${number}" selected>${number}</option>`);
        });
    });
});
</script>

@endsection