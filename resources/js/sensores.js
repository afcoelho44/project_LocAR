// =====================================================================
// sensores.js — leitura dos sensores + coletor do dataset quente/frio
// =====================================================================
// Uma linha do dataset = uma janela de 1 s:
//   - último valor do GPS (bruto, sem Kalman)
//   - resumo do que os sensores rápidos (~60 Hz) mediram nessa janela
// As linhas vão para um buffer e são enviadas ao Laravel em lotes.
// =====================================================================

const JANELA_MS = 1000;   // tamanho de cada linha do CSV
const ENVIO_MS = 10000;   // de quanto em quanto tempo envia o lote
const LOTE_MAX = 100;     // máximo de linhas por requisição (igual ao back)

// ---------------------------------------------------------------------
// HUD: giro acumulado (o que já existia — continua funcionando igual)
// ---------------------------------------------------------------------
let girou = 0;

window.addEventListener('devicemotion', (e) => {
    const r = e.rotationRate;
    if (!r || r.alpha == null) return;
    girou += r.alpha * e.interval / 1000;
});

export function getGirou() {
    const aux = girou;
    girou = 0;
    return aux;
}

// ---------------------------------------------------------------------
// Permissões (Android: não existe requestPermission → já funciona;
//             iPhone: precisa pedir as duas, dentro de um clique)
// ---------------------------------------------------------------------
export async function pedirPermissao() {
    const pedidos = [];

    if (typeof DeviceMotionEvent?.requestPermission === 'function') {
        pedidos.push(DeviceMotionEvent.requestPermission());
    }
    if (typeof DeviceOrientationEvent?.requestPermission === 'function') {
        pedidos.push(DeviceOrientationEvent.requestPermission());
    }
    if (pedidos.length === 0) return true;

    const respostas = await Promise.all(pedidos);
    return respostas.every((r) => r === 'granted');
}

// ---------------------------------------------------------------------
// Ângulos
// ---------------------------------------------------------------------
const RAD = Math.PI / 180;

// qualquer ângulo -> 0..360
function normalizar360(graus) {
    return ((graus % 360) + 360) % 360;
}

// ---------------------------------------------------------------------
// Estado do coletor
// ---------------------------------------------------------------------
let cfg = null;           // { url, csrf, sessao, questionId, alvo: {lat, lng} }
let buffer = [];
let enviando = false;
let timerJanela = null;
let timerEnvio = null;
let gpsWatchId = null;

const ultimo = {
    gps: null,            // { lat, lng, accuracy, speed, heading, t }
    orient: null,         // { alpha, beta, gamma }  (deviceorientation relativo)
};

let jan = novaJanela();

function novaJanela() {
    return {
        // giroscópio (graus/s)
        giroAlphaSoma: 0,           // integrado em graus (igual ao "girou")
        giroBetaSoma: 0, giroGammaSoma: 0, giroN: 0,
        // acelerômetro (m/s²) — magnitude, para média e desvio
        accMagSoma: 0, accMagSoma2: 0, accN: 0,
        // bússola — soma de seno/cosseno para a média circular
        bussolaSen: 0, bussolaCos: 0, bussolaN: 0,
    };
}

// ---------------------------------------------------------------------
// Listeners
// ---------------------------------------------------------------------
function onMotion(e) {
    const r = e.rotationRate;
    if (r && r.alpha != null) {
        jan.giroAlphaSoma += r.alpha * e.interval / 1000;
        jan.giroBetaSoma += r.beta ?? 0;
        jan.giroGammaSoma += r.gamma ?? 0;
        jan.giroN++;
    }

    const a = e.accelerationIncludingGravity;
    if (a && a.x != null) {
        const mag = Math.sqrt(a.x ** 2 + a.y ** 2 + a.z ** 2);
        jan.accMagSoma += mag;
        jan.accMagSoma2 += mag * mag;
        jan.accN++;
    }
}

// Android/Chrome: alpha absoluto (0 = norte, cresce no sentido anti-horário)
// -> rumo da bússola = 360 - alpha (cresce no sentido horário)
function onOrientAbsoluta(e) {
    if (!e.absolute || e.alpha == null) return;
    somarRumo(normalizar360(360 - e.alpha));
}

// iPhone: não tem deviceorientationabsolute, mas tem webkitCompassHeading
function onOrientRelativa(e) {
    if (e.alpha != null) {
        ultimo.orient = { alpha: e.alpha, beta: e.beta, gamma: e.gamma };
    }
    if (typeof e.webkitCompassHeading === 'number') {
        somarRumo(e.webkitCompassHeading);
    }
}

function somarRumo(rumo) {
    jan.bussolaSen += Math.sin(rumo * RAD);
    jan.bussolaCos += Math.cos(rumo * RAD);
    jan.bussolaN++;
}

function onGps(pos) {
    const c = pos.coords;
    ultimo.gps = {
        lat: c.latitude,
        lng: c.longitude,
        accuracy: c.accuracy,
        speed: c.speed,         // null quando parado/indisponível — manter null
        heading: c.heading,     // idem (NaN quando parado em alguns aparelhos)
        t: pos.timestamp,
    };
}

function onGpsErro(err) {
    console.warn('[coleta] erro de GPS', err.code, err.message);
}

// ---------------------------------------------------------------------
// Fechamento da janela -> uma linha
// ---------------------------------------------------------------------
function numOuNull(v) {
    return typeof v === 'number' && Number.isFinite(v) ? v : null;
}

function arred(v, casas) {
    if (v == null) return null;
    const f = 10 ** casas;
    return Math.round(v * f) / f;
}

function fecharJanela() {
    const j = jan;
    jan = novaJanela();

    const g = ultimo.gps;
    if (!g) return; // ainda sem nenhum fix de GPS: não grava

    // bússola: média e desvio circulares
    let bussolaMedia = null;
    let bussolaDesvio = null;
    if (j.bussolaN > 0) {
        const s = j.bussolaSen / j.bussolaN;
        const c = j.bussolaCos / j.bussolaN;
        bussolaMedia = normalizar360(Math.atan2(s, c) / RAD);
        const R = Math.min(1, Math.sqrt(s * s + c * c));    // 1 = todas iguais
        bussolaDesvio = R > 0 ? Math.sqrt(-2 * Math.log(R)) / RAD : 180;
    }

    // acelerômetro: média e desvio da magnitude
    let accMedia = null;
    let accDesvio = null;
    if (j.accN > 0) {
        accMedia = j.accMagSoma / j.accN;
        const variancia = Math.max(0, j.accMagSoma2 / j.accN - accMedia ** 2);
        accDesvio = Math.sqrt(variancia);
    }

    const o = ultimo.orient;

    buffer.push({
        sessao: cfg.sessao,
        question_id: cfg.questionId,
        t_ms: Date.now(),

        alvo_lat: cfg.alvo.lat,
        alvo_lng: cfg.alvo.lng,

        lat: g.lat,
        lng: g.lng,
        accuracy: arred(numOuNull(g.accuracy), 2),
        speed: arred(numOuNull(g.speed), 3),
        gps_heading: arred(numOuNull(g.heading), 2),
        gps_t_ms: g.t ? Math.round(g.t) : null,

        bussola_media: arred(bussolaMedia, 2),
        bussola_desvio: arred(bussolaDesvio, 2),
        bussola_n: j.bussolaN,

        alpha_rel: arred(numOuNull(o?.alpha), 2),
        beta: arred(numOuNull(o?.beta), 2),
        gamma: arred(numOuNull(o?.gamma), 2),

        giro_alpha_soma: arred(j.giroAlphaSoma, 3),
        giro_beta_media: j.giroN ? arred(j.giroBetaSoma / j.giroN, 3) : null,
        giro_gamma_media: j.giroN ? arred(j.giroGammaSoma / j.giroN, 3) : null,

        acc_mag_media: arred(accMedia, 4),
        acc_mag_desvio: arred(accDesvio, 4),
    });

    if (buffer.length >= LOTE_MAX) enviar();
}

// ---------------------------------------------------------------------
// Envio em lotes
// ---------------------------------------------------------------------
async function enviar() {
    if (enviando || buffer.length === 0 || !cfg) return;
    enviando = true;

    const lote = buffer.slice(0, LOTE_MAX);
    try {
        const res = await fetch(cfg.url, {
            method: 'POST',
            credentials: 'same-origin',
            keepalive: true, // deixa a requisição terminar mesmo se a página fechar
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': cfg.csrf,
            },
            body: JSON.stringify({ samples: lote }),
        });

        if (res.ok) {
            buffer.splice(0, lote.length); // só tira do buffer se o servidor salvou
        } else {
            console.warn('[coleta] servidor recusou o lote', res.status, await res.text());
        }
    } catch (err) {
        console.warn('[coleta] falha de rede, tenta de novo no próximo ciclo', err);
    } finally {
        enviando = false;
    }
}

function onVisibilidade() {
    if (document.visibilityState === 'hidden') enviar();
}

// ---------------------------------------------------------------------
// API pública
// ---------------------------------------------------------------------
function gerarSessao() {
    return crypto.randomUUID?.() ?? `${Date.now()}-${Math.random().toString(16).slice(2)}`;
}

/**
 * Chamar depois do clique em "Ativar sensores" e depois de carregar a pergunta.
 * @param {{url: string, csrf: string, questionId: number, alvo: {lat: number, lng: number}}} opcoes
 */
export function iniciarColeta({ url, csrf, questionId, alvo }) {
    if (cfg) return; // já está coletando

    cfg = { url, csrf, questionId, alvo, sessao: gerarSessao() };
    buffer = [];
    jan = novaJanela();

    window.addEventListener('devicemotion', onMotion);
    window.addEventListener('deviceorientationabsolute', onOrientAbsoluta);
    window.addEventListener('deviceorientation', onOrientRelativa);
    document.addEventListener('visibilitychange', onVisibilidade);
    window.addEventListener('pagehide', enviar);

    gpsWatchId = navigator.geolocation.watchPosition(onGps, onGpsErro, {
        enableHighAccuracy: true,
        maximumAge: 0,
        timeout: 20000,
    });

    timerJanela = setInterval(fecharJanela, JANELA_MS);
    timerEnvio = setInterval(enviar, ENVIO_MS);

    console.info('[coleta] iniciada, sessão', cfg.sessao);
}

export async function pararColeta() {
    if (!cfg) return;

    clearInterval(timerJanela);
    clearInterval(timerEnvio);
    if (gpsWatchId != null) navigator.geolocation.clearWatch(gpsWatchId);

    window.removeEventListener('devicemotion', onMotion);
    window.removeEventListener('deviceorientationabsolute', onOrientAbsoluta);
    window.removeEventListener('deviceorientation', onOrientRelativa);
    document.removeEventListener('visibilitychange', onVisibilidade);
    window.removeEventListener('pagehide', enviar);

    fecharJanela();
    while (buffer.length > 0) {
        const antes = buffer.length;
        await enviar();
        if (buffer.length === antes) break; // servidor recusou: não fica em loop
    }

    console.info('[coleta] parada, sessão', cfg.sessao);
    cfg = null;
}

// para depurar no console do celular (chrome://inspect)
export function getBufferColeta() {
    return buffer;
}
