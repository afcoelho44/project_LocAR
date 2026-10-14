import * as THREE from 'three';
import * as LocAR from 'locar';
import { GLTFLoader } from 'three/addons/loaders/GLTFLoader.js';

const root = document.getElementById('ar-root');
const el = {
    status: document.getElementById('status'),
    step: document.getElementById('step'),
    panel: document.getElementById('panel'),
    qText: document.getElementById('q-text'),
    qAnswers: document.getElementById('q-answers'),
};

// ?fake=far  -> simula GPS a ~33 m do ponto (teste no computador)
// ?fake=here -> simula GPS em cima do ponto
const fake = new URLSearchParams(location.search).get('fake');

const setStatus = (t) => (el.status.textContent = t);

function distanceMeters(lat1, lng1, lat2, lng2) {
    const R = 6371000;
    const rad = (d) => (d * Math.PI) / 180;
    const dLat = rad(lat2 - lat1);
    const dLng = rad(lng2 - lng1);
    const a = Math.sin(dLat / 2) ** 2 +
        Math.cos(rad(lat1)) * Math.cos(rad(lat2)) * Math.sin(dLng / 2) ** 2;
    return 2 * R * Math.asin(Math.sqrt(a));
}

// ---------- cena three.js ----------
const canvas = document.getElementById('ar-canvas');
const renderer = new THREE.WebGLRenderer({ canvas, antialias: true, alpha: true });
renderer.setPixelRatio(window.devicePixelRatio);
renderer.setSize(window.innerWidth, window.innerHeight);

const camera = new THREE.PerspectiveCamera(80, window.innerWidth / window.innerHeight, 0.001, 1000);
const scene = new THREE.Scene();
scene.add(new THREE.HemisphereLight(0xffffff, 0x444444, 2.5));
const sun = new THREE.DirectionalLight(0xffffff, 2);
sun.position.set(5, 10, 5);
scene.add(sun);

window.addEventListener('resize', () => {
    renderer.setSize(window.innerWidth, window.innerHeight);
    camera.aspect = window.innerWidth / window.innerHeight;
    camera.updateProjectionMatrix();
});

// ---------- LocAR ----------
const locar = new LocAR.LocationBased(scene, camera);
const cam = new LocAR.Webcam({ video: { facingMode: 'environment' } }, null);
const controls = new LocAR.DeviceOrientationControls(camera);

cam.on('webcamstarted', (ev) => { scene.background = ev.texture; });
cam.on('webcamerror', (err) => setStatus(`Erro na câmera: ${err.message ?? err.code}`));

let spinner = null;
renderer.setAnimationLoop(() => {
    controls.update();
    if (spinner) spinner.rotation.y += 0.01;
    renderer.render(scene, camera);
});

// ---------- objeto 3D ----------
const TARGET_SIZE = 10; // maior dimensão do modelo, em metros

async function buildObject(url) {
    const group = new THREE.Group();

    if (url) {
        try {
            const gltf = await new GLTFLoader().loadAsync(url);
            const model = gltf.scene;

            // normaliza o tamanho e centraliza o modelo
            const box = new THREE.Box3().setFromObject(model);
            const size = box.getSize(new THREE.Vector3());
            model.scale.multiplyScalar(TARGET_SIZE / (Math.max(size.x, size.y, size.z) || 1));
            const center = new THREE.Box3().setFromObject(model).getCenter(new THREE.Vector3());
            model.position.sub(center);

            group.add(model);
            return group;
        } catch (e) {
            console.error('Falha ao carregar o GLB', e);
        }
    }

    // sem objeto (ou erro): cubo padrão
    group.add(new THREE.Mesh(
        new THREE.BoxGeometry(1, 1, 1),
        new THREE.MeshStandardMaterial({ color: 0xef4444 })
    ));
    return group;
}
// formato do payload varia (GPS real x fakeGps): extrai as coordenadas dos dois jeitos
function extractCoords(payload) {
    const c = payload?.coords ?? payload?.position?.coords ?? payload?.detail?.coords ?? payload;
    return c && typeof c.latitude === 'number' ? c : null;
}
// ---------- fluxo ----------
async function main() {
    const res = await fetch(root.dataset.stateUrl, {
        headers: { Accept: 'application/json' },
        credentials: 'same-origin',
    });
    if (!res.ok) return setStatus(`Erro ao carregar a atividade (${res.status}).`);

    const state = await res.json();
    if (state.finished) return setStatus('Percurso concluído!');

    const q = state.question;
    el.step.textContent = `Etapa ${state.number} de ${state.total}`;

    let placed = false;
    let unlocked = false;

    locar.on('gpserror', (code) => setStatus(`Erro de GPS (código ${code}). Libere a localização.`));

    let fakePos = null;
    if (fake) {
        const offset = fake === 'here' ? 0 : -0.0003; // ~33 m ao norte
        fakePos = { latitude: q.lat + offset, longitude: q.lng, accuracy: 0 };
    }

    locar.on('gpsupdate', async (pos) => {
        const c = extractCoords(pos) ?? fakePos;
        if (!c) {
            console.warn('gpsupdate com formato inesperado:', pos);
            return;
        }
        const { latitude, longitude, accuracy } = c;
        const dist = distanceMeters(latitude, longitude, q.lat, q.lng);

        setStatus(`${Math.round(dist)} m do ponto · GPS ±${Math.round(accuracy ?? 0)} m`);

        if (!placed) {
            placed = true;
            const obj = await buildObject(q.object_url);
            spinner = obj;
            locar.add(obj, q.lng, q.lat, -1);
        }

        if (!unlocked && dist <= q.radius) {
            unlocked = true;
            showQuestion(q);
        }
    });

    if (fake) {
        locar.fakeGps(fakePos.longitude, fakePos.latitude);
    } else {
        locar.startGps();
    }
}

function showQuestion(q) {
    el.qText.textContent = q.description;
    el.qAnswers.replaceChildren(...q.answers.map((a) => {
        const b = document.createElement('button');
        b.type = 'button';
        b.dataset.id = a.id;
        b.textContent = `${a.alternative}) ${a.description}`;
        return b; // o clique será ligado na Etapa 7
    }));
    el.panel.style.display = 'block';
}

main().catch((e) => {
    console.error(e);
    setStatus('Erro inesperado. Veja o console.');
});