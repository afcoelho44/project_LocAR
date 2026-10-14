@csrf

@php
    $letters = ['A', 'B', 'C', 'D'];
    $current = isset($question->id) ? $question->answers->keyBy('alternative') : collect();
    $correctIdx = array_search($current->firstWhere('is_correct', true)?->alternative, $letters, true);
    $correctIdx = old('correct_answer', $correctIdx === false ? 0 : $correctIdx);
@endphp

<div>
    <x-input-label for="description" value="Pergunta" />
    <textarea id="description" name="description" rows="3" required
        class="block mt-1 w-full border-gray-300 rounded-md shadow-sm">{{ old('description', $question->description) }}</textarea>
    <x-input-error :messages="$errors->get('description')" class="mt-2" />
</div>

<div class="mt-4">
    <x-input-label for="clue_text" value="Texto da pista (exibido ao acertar)" />
    <x-text-input id="clue_text" name="clue_text" type="text" class="block mt-1 w-full"
        :value="old('clue_text', $question->clue_text)" />
    <x-input-error :messages="$errors->get('clue_text')" class="mt-2" />
</div>

<div class="mt-6">
    <x-input-label value="Alternativas (marque a correta)" />
    @foreach ($letters as $i => $letter)
        <div class="mt-2 flex items-center gap-3">
            <input type="radio" name="correct_answer" value="{{ $i }}"
                @checked((int) $correctIdx === $i)>
            <span class="font-semibold w-6">{{ $letter }}</span>
            <input type="text" name="answers[{{ $i }}][description]"
                value="{{ old("answers.$i.description", $current[$letter]->description ?? '') }}"
                placeholder="{{ $i < 2 ? 'Obrigatória' : 'Opcional' }}"
                class="flex-1 border-gray-300 rounded-md shadow-sm text-sm">
        </div>
        <x-input-error :messages="$errors->get("answers.$i.description")" class="mt-1" />
    @endforeach
    <x-input-error :messages="$errors->get('correct_answer')" class="mt-2" />
</div>

<div class="mt-6 grid grid-cols-1 sm:grid-cols-2 gap-4">
    <div>
        <x-input-label for="position" value="Ordem no percurso" />
        <x-text-input id="position" name="position" type="number" min="1" class="block mt-1 w-full"
            :value="old('position', $question->position)" required />
        <x-input-error :messages="$errors->get('position')" class="mt-2" />
    </div>
    <div>
        <x-input-label for="radius" value="Raio de ativação (metros)" />
        <x-text-input id="radius" name="radius" type="number" min="5" max="500" class="block mt-1 w-full"
            :value="old('radius', $question->radius)" required />
        <x-input-error :messages="$errors->get('radius')" class="mt-2" />
    </div>
</div>

<div class="mt-4">
    <x-input-label for="object_3d_id" value="Objeto 3D" />
    <select id="object_3d_id" name="object_3d_id" class="block mt-1 w-full border-gray-300 rounded-md shadow-sm">
        <option value="">Nenhum</option>
        @foreach ($objects as $object)
            <option value="{{ $object->id }}" @selected((int) old('object_3d_id', $question->object_3d_id) === $object->id)>
                {{ $object->name }}
            </option>
        @endforeach
    </select>
    <x-input-error :messages="$errors->get('object_3d_id')" class="mt-2" />
</div>

<div class="mt-6">
    <div class="flex items-center justify-between">
        <x-input-label value="Local da pergunta (clique no mapa)" />
        <button type="button" id="btn-locate" class="text-xs text-indigo-600 underline">Usar minha localização</button>
    </div>
    <div id="map" class="mt-2 rounded-md border" style="height: 400px; z-index: 0;"></div>

    <div class="mt-2 grid grid-cols-2 gap-4">
        <div>
            <x-input-label for="lat" value="Latitude" />
            <x-text-input id="lat" name="lat" type="text" class="block mt-1 w-full bg-gray-50" readonly
                :value="old('lat', $question->lat)" />
        </div>
        <div>
            <x-input-label for="lng" value="Longitude" />
            <x-text-input id="lng" name="lng" type="text" class="block mt-1 w-full bg-gray-50" readonly
                :value="old('lng', $question->lng)" />
        </div>
    </div>
    <x-input-error :messages="$errors->get('lat')" class="mt-2" />
</div>

<div class="mt-6 flex items-center gap-4">
    <x-primary-button>Salvar</x-primary-button>
    <a href="{{ route('admin.activities.show', $activity) }}" class="text-sm text-gray-600 underline">Cancelar</a>
</div>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
    const latInput = document.getElementById('lat');
    const lngInput = document.getElementById('lng');
    const radiusInput = document.getElementById('radius');

    const hasPoint = latInput.value !== '' && lngInput.value !== '';
    const start = hasPoint
        ? [parseFloat(latInput.value), parseFloat(lngInput.value)]
        : [-26.3045, -48.8487]; // Joinville

    const map = L.map('map').setView(start, hasPoint ? 17 : 14);
    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap'
    }).addTo(map);

    let marker = null;
    let circle = null;

    function setPoint(lat, lng) {
        latInput.value = lat.toFixed(7);
        lngInput.value = lng.toFixed(7);

        const radius = parseInt(radiusInput.value) || 20;

        if (marker) {
            marker.setLatLng([lat, lng]);
            circle.setLatLng([lat, lng]).setRadius(radius);
        } else {
            marker = L.marker([lat, lng]).addTo(map);
            circle = L.circle([lat, lng], { radius: radius }).addTo(map);
        }
    }

    if (hasPoint) setPoint(start[0], start[1]);

    map.on('click', e => setPoint(e.latlng.lat, e.latlng.lng));

    radiusInput.addEventListener('input', () => {
        if (circle) circle.setRadius(parseInt(radiusInput.value) || 20);
    });

    document.getElementById('btn-locate').addEventListener('click', () => {
        if (!navigator.geolocation) return alert('Geolocalização não suportada.');
        navigator.geolocation.getCurrentPosition(
            pos => {
                setPoint(pos.coords.latitude, pos.coords.longitude);
                map.setView([pos.coords.latitude, pos.coords.longitude], 17);
            },
            () => alert('Não foi possível obter a localização.')
        );
    });
</script>