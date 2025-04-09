@extends('admin.body.adminmaster')

@section('admin')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="white_shd full margin_bottom_30" style="padding: 50px; background: linear-gradient(to right, #fdfbfb, #ebedee); border-radius: 15px; box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);">
                <h4 style="font-weight: 700; margin-bottom: 30px; font-size: 24px; color: #333;">
                    🎮 Period No - <span style="color: #28a745;">{{ $nextGameNo ?? 'N/A' }}</span>
                </h4>

                <form action="{{ route('adminWinner.addWingo1') }}" method="post">
                    @csrf

                    <!-- Andar/Bahar Card Selection -->
                    <div style="text-align: center; margin-bottom: 40px;">
                        <p style="font-size: 18px; font-weight: 600; margin-bottom: 20px;">Choose Number</p>
                        <div id="number-selection" style="display: flex; justify-content: center; gap: 20px; flex-wrap: wrap;">
                            @for ($i = 0; $i <= 9; $i++)
                                <div onclick="selectNumber({{ $i }})"
                                    id="number-{{ $i }}"
                                    style="width: 50px; height: 50px; line-height: 50px; text-align: center;
                                    background: #ffffff; border-radius: 50%; font-weight: bold; cursor: pointer;
                                    border: 2px solid #ccc; transition: all 0.3s ease;
                                    box-shadow: 0 2px 6px rgba(0,0,0,0.1); font-size: 16px;">
                                    {{ $i }}
                                </div>
                            @endfor
                        </div>
                        <input type="hidden" name="selected_number" id="selected_number" value="">
                    </div>

                    <!-- Game Details -->
                    <div style="display: flex; justify-content: center; align-items: flex-end; flex-wrap: wrap; gap: 30px;">
                        <div style="text-align: left;">
                            <label for="game_type" style="font-weight: 600; font-size: 15px;">Select Type:</label><br>
                            <select id="game_type" name="game_type"
                                style="width: 180px; padding: 8px 12px; font-size: 14px; border-radius: 8px;
                                border: 1px solid #ccc; background-color: #fff;">
                                <option value="">-- Select --</option>
                                @for ($i = 0; $i <= 9; $i++)
                                    <option value="{{ $i }}">{{ $i }}</option>
                                @endfor
                            </select>
                        </div>

                        <div style="text-align: left;">
                            <label for="games_no" style="font-weight: 600; font-size: 15px;">Game No:</label><br>
                            <input type="text" id="games_no" name="games_no"
                                value="{{ $nextGameNo ?? '' }}"
                                style="width: 180px; padding: 8px 12px; font-size: 14px; font-weight: bold;
                                border-radius: 8px; border: 1px solid #ccc;">
                        </div>
                        
                        <div style="text-align: left;">
                            <label for="game_id" style="font-weight: 600; font-size: 15px;">Select Game ID:</label><br>
                            <select id="game_id" name="game_id"
                                style="width: 180px; padding: 8px 12px; font-size: 14px; border-radius: 8px;
                                border: 1px solid #ccc; background-color: #fff;">
                                <option value="">-- Select --</option>
                                @for ($i = 1; $i <= 4; $i++)
                                    <option value="{{ $i }}">{{ $i }}</option>
                                @endfor
                            </select>
                        </div>

                        <div>
                            <button type="submit"
                                style="background: linear-gradient(to right, #28a745, #218838); color: white;
                                font-weight: 600; border: none; border-radius: 8px; padding: 10px 25px;
                                font-size: 15px; cursor: pointer; transition: background 0.3s ease;">
                                ✅ Submit
                            </button>
                        </div>
                    </div>

                </form>
            </div>
        </div>
    </div>
</div>

<!-- Script -->
<script>
    function selectNumber(num) {
        document.getElementById('selected_number').value = num;
        document.getElementById('game_type').value = num;
        for (let i = 0; i <= 9; i++) {
            let div = document.getElementById('number-' + i);
            div.style.border = "2px solid #ccc";
            div.style.background = "#fff";
        }
        let selected = document.getElementById('number-' + num);
        selected.style.border = "2px solid #28a745";
        selected.style.background = "#e6ffe6";
    }
</script>
@endsection
