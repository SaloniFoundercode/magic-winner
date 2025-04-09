@extends('admin.body.adminmaster')

@section('admin')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div style="padding: 30px; background: #fff; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.1);">
                <h4 style="font-weight: 700; margin-bottom: 25px;">🎮 Period No - {{ $nextGameNo ?? 'N/A' }}</h4>
                <form action="{{ route('adminWinner.dtWinner') }}" method="POST">
                    @csrf
                    <div style="text-align: center; margin-bottom: 30px;">
                        <p style="font-size: 18px; font-weight: 600;">Choose Winner</p>
                        <div style="display: flex; justify-content: center; gap: 20px; flex-wrap: wrap;">
                            <div id="card-dragon" onclick="selectCard('Dragon')" style="cursor: pointer; border-radius: 12px; padding: 10px; transition: all 0.3s ease; border: 3px solid transparent; opacity: 1;">
                                <img src="https://magicwinner.motug.com/public/uploads/gameimage/dragon2.gif" alt="Dragon" style="height: 110px; border-radius: 12px;">
                            </div>
                            <div id="card-tie" onclick="selectCard('Tie')" style="cursor: pointer; border-radius: 12px; padding: 10px; transition: all 0.3s ease; border: 3px solid transparent; opacity: 1;">
                                <img src="https://magicwinner.motug.com/public/uploads/gameimage/2.png" alt="Tie" style="height: 100px; border-radius: 12px;">
                            </div>
                            <div id="card-tiger" onclick="selectCard('Tiger')" style="cursor: pointer; border-radius: 12px; padding: 10px; transition: all 0.3s ease; border: 3px solid transparent; opacity: 1;">
                                <img src="https://magicwinner.motug.com/public/uploads/gameimage/1.gif" alt="Tiger" style="height: 110px; border-radius: 12px;">
                            </div>
                        </div>
                    </div>

                    <div style="display: flex; justify-content: center; align-items: flex-end; flex-wrap: wrap; gap: 30px;">
                        <div>
                            <label for="game_type" style="font-weight: 600;">Select Type:</label><br>
                            <select id="game_type" name="game_type" style="width: 160px; padding: 8px 12px; font-size: 14px; border-radius: 6px; border: 1px solid #ccc;">
                                <option value="">-- Select --</option>
                                <option value="Dragon">Dragon</option>
                                <option value="Tiger">Tiger</option>
                                <option value="Tie">Tie</option>
                            </select>
                        </div>
                        <div>
                            <label for="games_no" style="font-weight: 600;">Game No:</label><br>
                            <input type="text" id="games_no" name="games_no" value="{{ $nextGameNo ?? '' }}" style="width: 160px; padding: 8px 12px; font-size: 14px; font-weight: bold; border-radius: 6px; border: 1px solid #ccc;">
                        </div>
                        <div>
                            <button type="submit" style="background-color: #28a745; color: #fff; padding: 10px 20px; font-weight: 600; font-size: 14px; border: none; border-radius: 6px; cursor: pointer; transition: 0.3s;">
                                ✅ Submit
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    function selectCard(value) {
        document.getElementById('game_type').value = value;

        // Reset all cards: remove borders and set opacity to dull
        ['card-dragon', 'card-tiger', 'card-tie'].forEach(id => {
            const el = document.getElementById(id);
            el.style.border = "3px solid transparent";
            el.style.opacity = "0.4";
            el.style.transform = "scale(1)";
        });

        // Highlight selected card
        const selectedCard = document.getElementById('card-' + value.toLowerCase());
        selectedCard.style.opacity = "1";
        selectedCard.style.transform = "scale(1.05)";
        if (value === 'Dragon') {
            selectedCard.style.border = "3px solid #007bff";
        } else if (value === 'Tiger') {
            selectedCard.style.border = "3px solid #dc3545";
        } else if (value === 'Tie') {
            selectedCard.style.border = "3px solid #28a745";
        }
    }
</script>
@endsection
