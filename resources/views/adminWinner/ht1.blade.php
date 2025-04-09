@extends('admin.body.adminmaster')

@section('admin')
<div style="padding: 20px;">
    <div style="max-width: 800px; margin: auto;">
        <div style="padding: 30px; background: linear-gradient(135deg, #fefefe, #f0f0ff); border-radius: 15px; box-shadow: 0 8px 24px rgba(0,0,0,0.08);">
            <h4 style="font-weight: 800; margin-bottom: 30px; font-size: 24px; color: #2c3e50;">
                🎮 Period No - <span style="color: #5a00e0;">{{ $nextGameNo ?? 'N/A' }}</span>
            </h4>

            <form action="{{ route('adminWinner.addAB') }}" method="post">
                @csrf

                <div style="text-align: center; margin-bottom: 35px;">
                    <p style="font-size: 18px; font-weight: 700; color: #444;">Choose Card</p>
                    <div style="display: flex; justify-content: center; gap: 40px; flex-wrap: wrap;">
                        <div id="card-head" onclick="selectCard('Andar')" style="cursor: pointer; transition: transform 0.2s;">
                            <img src="https://magicwinner.motug.com/public/image/heads.png" alt="Heads"
                                 style="width: 100px; height: 100px; border-radius: 50%; box-shadow: 0 4px 12px rgba(0,0,0,0.1); transition: border 0.3s;">
                        </div>
                        <div id="card-tail" onclick="selectCard('Bahar')" style="cursor: pointer; transition: transform 0.2s;">
                            <img src="https://magicwinner.motug.com/public/image/tails.png" alt="Tails"
                                 style="width: 100px; height: 100px; border-radius: 50%; box-shadow: 0 4px 12px rgba(0,0,0,0.1); transition: border 0.3s;">
                        </div>
                    </div>
                </div>

                <div style="display: flex; justify-content: center; align-items: end; flex-wrap: wrap; gap: 30px;">
                    <div style="text-align: left;">
                        <label for="game_type" style="font-weight: 600; font-size: 14px; color: #2c3e50;">Select Type:</label><br>
                        <select id="game_type" name="game_type"
                                style="width: 180px; padding: 8px 12px; font-size: 15px; border-radius: 6px; border: 1px solid #ced4da; background-color: #f8f9fa;">
                            <option value="">-- Select --</option>
                            <option value="Andar">Head</option>
                            <option value="Bahar">Tail</option>
                        </select>
                    </div>

                    <div style="text-align: left;">
                        <label for="games_no" style="font-weight: 600; font-size: 14px; color: #2c3e50;">Game No:</label><br>
                        <input type="text" id="games_no" name="games_no"
                               value="{{ $nextGameNo ?? '' }}"
                               style="width: 180px; padding: 8px 12px; font-size: 15px; font-weight: bold; border-radius: 6px; border: 1px solid #ced4da; background-color: #f8f9fa;">
                    </div>

                    <div>
                        <button type="submit"
                                style="background-color: #4CAF50; color: white; font-weight: 700; border: none; border-radius: 8px; padding: 10px 24px; font-size: 15px; margin-top: 10px; cursor: pointer; box-shadow: 0 4px 10px rgba(0,0,0,0.1); transition: all 0.3s ease;">
                            ✅ Submit
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function selectCard(value) {
        document.getElementById('game_type').value = value;

        const head = document.getElementById('card-head').querySelector('img');
        const tail = document.getElementById('card-tail').querySelector('img');

        // Reset borders
        head.style.border = "2px solid transparent";
        tail.style.border = "2px solid transparent";

        // Highlight selection with attractive thin border
        if (value === 'Andar') {
            head.style.border = "2px solid #00bcd4"; // Cyan
        } else if (value === 'Bahar') {
            tail.style.border = "2px solid #ff4081"; // Pink
        }
    }
</script>
@endsection
