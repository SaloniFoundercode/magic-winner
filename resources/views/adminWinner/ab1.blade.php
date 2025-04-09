@extends('admin.body.adminmaster')

@section('admin')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="white_shd full margin_bottom_30" style="padding: 30px; background: #ffffff; border-radius: 10px; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);">
                <h4 style="font-weight: 700; margin-bottom: 25px;">🎮 Period No - {{ $nextGameNo ?? 'N/A' }}</h4>
                <form action="{{ route('adminWinner.addAB') }}" method="post">
                    @csrf
                    <div style="text-align: center; margin-bottom: 30px;">
                        <p style="font-size: 16px; font-weight: 600;">Choose Card</p>
                        <div id="card-selection" style="display: flex; justify-content: center; gap: 20px; flex-wrap: wrap;">
                            <div id="card-andar" onclick="selectCard('Andar')"
                                style="padding: 16px 30px; border-radius: 12px; font-weight: 700; font-size: 18px; cursor: pointer;
                                background: linear-gradient(135deg, #1e3c72, #2a5298); color: white;
                                border: 3px solid transparent; transition: all 0.3s ease;">
                                🔵 Andar
                            </div>

                            <div id="card-bahar" onclick="selectCard('Bahar')"
                                style="padding: 16px 30px; border-radius: 12px; font-weight: 700; font-size: 18px; cursor: pointer;
                                background: linear-gradient(135deg, #c31432, #240b36); color: white;
                                border: 3px solid transparent; transition: all 0.3s ease;">
                                🔴 Bahar
                            </div>
                        </div>
                    </div>
                    <div style="display: flex; justify-content: center; align-items: end; flex-wrap: wrap; gap: 30px;">
                        <div style="text-align: left;">
                            <label for="game_type" style="font-weight: 600;">Select Type:</label><br>
                            <select id="game_type" name="game_type"
                                style="width: 160px; padding: 5px 10px; font-size: 14px; border-radius: 5px; border: 1px solid #ced4da;">
                                <option value="">-- Select --</option>
                                <option value="Andar">Andar</option>
                                <option value="Bahar">Bahar</option>
                            </select>
                        </div>
                        <div style="text-align: left;">
                            <label for="games_no" style="font-weight: 600;">Game No:</label><br>
                            <input type="text" id="games_no" name="games_no"
                                value="{{ $nextGameNo ?? '' }}"
                                style="width: 160px; padding: 5px 10px; font-size: 14px;
                                font-weight: bold; border-radius: 5px; border: 1px solid #ced4da;">
                        </div>
                        <div>
                            <button type="submit"
                                style="background-color: #28a745; color: white; font-weight: 600;
                                border: none; border-radius: 6px; padding: 8px 20px;
                                font-size: 14px; margin-top: 10px; cursor: pointer; transition: all 0.3s ease;">
                                ✅ Submit
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<style>
    @keyframes blue-glow {
        0% {
            box-shadow: 0 0 5px #00c3ff, 0 0 10px #00c3ff, 0 0 15px #00c3ff;
        }
        50% {
            box-shadow: 0 0 10px #00e0ff, 0 0 20px #00e0ff, 0 0 30px #00e0ff;
        }
        100% {
            box-shadow: 0 0 5px #00c3ff, 0 0 10px #00c3ff, 0 0 15px #00c3ff;
        }
    }
    @keyframes red-glow {
        0% {
            box-shadow: 0 0 5px #ff4d4d, 0 0 10px #ff4d4d, 0 0 15px #ff4d4d;
        }
        50% {
            box-shadow: 0 0 10px #ff1a1a, 0 0 20px #ff1a1a, 0 0 30px #ff1a1a;
        }
        100% {
            box-shadow: 0 0 5px #ff4d4d, 0 0 10px #ff4d4d, 0 0 15px #ff4d4d;
        }
    }
    .blue-glow {
        animation: blue-glow 1.5s infinite;
        border: 3px solid #ffffff !important;
    }
    .red-glow {
        animation: red-glow 1.5s infinite;
        border: 3px solid #ffffff !important;
    }
</style>
<script>
    function selectCard(value) {
        document.getElementById('game_type').value = value;

        const andar = document.getElementById('card-andar');
        const bahar = document.getElementById('card-bahar');
        andar.classList.remove("blue-glow", "red-glow");
        bahar.classList.remove("blue-glow", "red-glow");
        if (value === 'Andar') {
            andar.classList.add("blue-glow");
        } else if (value === 'Bahar') {
            bahar.classList.add("red-glow");
        }
    }
</script>
@endsection
