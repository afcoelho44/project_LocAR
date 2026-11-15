// dica-mock.js — MOCK do componente de IA quente/frio.
//
// Enquanto a API Flask (componente_IA, POST /prever) não está pronta, a dica é
// calculada aqui com a mesma ideia do rótulo do rotular.py, só que olhando o
// PASSADO em vez do futuro:
//   quente : a distância diminuiu mais que MARGEM metros nos últimos JANELA_S
//   frio   : a distância aumentou mais que MARGEM
//   neutro : no meio disso
//   confiavel = false quando o GPS está ruim (accuracy alta) ou ainda não há
//   histórico suficiente -> a tela mostra "sem dica".
//
// O retorno tem o MESMO formato que a API vai devolver
// ({ dica, confiavel, dist }), então trocar o mock pela API depois é só
// substituir a chamada de calcularDica() por um fetch.
//
// ?dica=quente|frio|neutro|semdica força o estado (útil para gravar o vídeo).

const ACCURACY_MAX = 20; // m   — igual ao rotular.py
const JANELA_S = 3;      // s   — igual ao delta_dist_3s
const MARGEM = 2;        // m

const historico = []; // { t, dist }

const forcada = new URLSearchParams(location.search).get('dica');

export function registrarPosicao(dist, accuracy, t = Date.now()) {
    historico.push({ t, dist, accuracy });
    // guarda só o necessário (um pouco mais que a janela)
    while (historico.length && t - historico[0].t > (JANELA_S + 2) * 1000) {
        historico.shift();
    }
}

export function calcularDica() {
    const ultima = historico[historico.length - 1];
    if (!ultima) return { dica: null, confiavel: false, dist: null };

    if (forcada) {
        return forcada === 'semdica'
            ? { dica: null, confiavel: false, dist: ultima.dist }
            : { dica: forcada, confiavel: true, dist: ultima.dist };
    }

    if (ultima.accuracy > ACCURACY_MAX) {
        return { dica: null, confiavel: false, dist: ultima.dist };
    }

    // amostra mais antiga que ainda está dentro de ~JANELA_S segundos
    const alvoT = ultima.t - JANELA_S * 1000;
    const antiga = historico.find((h) => h.t >= alvoT - 500);
    if (!antiga || ultima.t - antiga.t < (JANELA_S - 0.5) * 1000) {
        return { dica: null, confiavel: false, dist: ultima.dist };
    }

    const delta = ultima.dist - antiga.dist;
    let dica = 'neutro';
    if (delta < -MARGEM) dica = 'quente';
    else if (delta > MARGEM) dica = 'frio';

    return { dica, confiavel: true, dist: ultima.dist };
}

// ---------- selo na tela ----------
const ESTILOS = {
    quente:  { texto: '🔥 Quente — está chegando perto!', cor: '#dc2626' },
    frio:    { texto: '❄️ Frio — está se afastando',      cor: '#2563eb' },
    neutro:  { texto: '〰️ Neutro — continue procurando',  cor: '#6b7280' },
    semdica: { texto: '📡 Sem dica — GPS impreciso',       cor: '#374151' },
};

export function mostrarDica(elemento, resultado) {
    const chave = resultado.confiavel && resultado.dica ? resultado.dica : 'semdica';
    const estilo = ESTILOS[chave] ?? ESTILOS.semdica;
    elemento.textContent = estilo.texto;
    elemento.style.background = estilo.cor;
    elemento.style.display = 'block';
}

export function esconderDica(elemento) {
    elemento.style.display = 'none';
}
