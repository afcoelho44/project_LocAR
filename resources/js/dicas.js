// dicas.js — dicas "quente / frio" na tela de RA, integradas ao componente de IA.
//
// Divisão de trabalho:
//   - IA (componente_IA, via Laravel): CORRIGE a distância do GPS até o objeto,
//     com o modelo treinado nas distâncias medidas com fita no experimento.
//   - Este arquivo: guarda o histórico da distância corrigida e decide a dica
//     olhando os últimos JANELA_S segundos (mesma ideia do rotular.py):
//       quente : a distância caiu mais que MARGEM metros
//       frio   : a distância subiu mais que MARGEM metros
//       neutro : no meio disso
//       sem dica (confiavel = false): GPS ruim, GPS parado há muito tempo,
//                ou histórico ainda curto.
//
// Se a IA não estiver configurada, estiver fora do ar ou demorar, a dica usa a
// distância do GPS sem correção (fonte = 'gps'). O jogo nunca trava por causa dela.
//
// ?dica=quente|frio|neutro|semdica força o estado (útil para gravar vídeo).

const ACCURACY_MAX = 20;    // m — acima disso, sem dica (igual ao rotular.py)
const JANELA_S = 3;        // s — janela da comparação (igual ao delta_dist_3s)
const MARGEM = 2;          // m — precisa ser maior que o ruído do GPS parado (SD_2D do analisar.py)
const INTERVALO_MS = 1000; // uma dica por segundo
const GPS_VELHO_MS = 5000; // sem leitura nova há mais que isso -> sem dica
const TIMEOUT_MS = 2000;   // tempo máximo esperando a correção da IA

const ESTILOS = {
    quente:  { texto: '🔥 Quente — está chegando perto!', cor: '#dc2626' },
    frio:    { texto: '❄️ Frio — está se afastando',      cor: '#2563eb' },
    neutro:  { texto: '〰️ Morno — continue procurando',   cor: '#6b7280' },
    semdica: { texto: '📡 Sem dica — GPS impreciso',       cor: '#374151' },
};

/**
 * @param elemento  div onde a dica aparece
 * @param url       rota POST do Laravel que pede a correção à IA (null = sem IA, só GPS)
 * @param csrf      token CSRF do Laravel
 * @param aoAtualizar callback(resultado) opcional, chamado a cada dica (para o HUD)
 */
export function criarDicas({ elemento, url = null, csrf = null, aoAtualizar = null }) {
    const forcada = new URLSearchParams(location.search).get('dica');
    const historico = [];      // { t, dist }
    let ultimaLeitura = null;  // { t, dGps, accuracy }
    let timer = null;
    let ocupado = false;

    async function corrigir(leitura) {
        const gps = { dist: leitura.dGps, fonte: 'gps' };
        if (!url) return gps;

        const ctrl = new AbortController();
        const corte = setTimeout(() => ctrl.abort(), TIMEOUT_MS);
        try {
            const res = await fetch(url, {
                method: 'POST',
                credentials: 'same-origin',
                signal: ctrl.signal,
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrf ?? '',
                },
                body: JSON.stringify({ d_gps_m: leitura.dGps, accuracy: leitura.accuracy }),
            });
            if (!res.ok) return gps;
            const json = await res.json();
            return typeof json.dist_m === 'number' ? { dist: json.dist_m, fonte: json.fonte ?? 'ia' } : gps;
        } catch {
            return gps; // timeout ou rede: segue com o GPS
        } finally {
            clearTimeout(corte);
        }
    }

    function decidir(agora, fonte) {
        const ultima = historico[historico.length - 1];
        const base = { dica: null, confiavel: false, dist: ultima?.dist ?? null, fonte };

        if (forcada) {
            return forcada === 'semdica' ? base : { ...base, dica: forcada, confiavel: true };
        }
        if (!ultimaLeitura || ultimaLeitura.accuracy > ACCURACY_MAX) return base;
        if (agora - ultimaLeitura.t > GPS_VELHO_MS) return base;

        // ponto mais antigo ainda dentro da janela
        const antiga = historico.find((h) => h.t >= agora - JANELA_S * 1000 - 500);
        if (!antiga || ultima.t - antiga.t < (JANELA_S - 0.5) * 1000) return base;

        const delta = ultima.dist - antiga.dist;
        let dica = 'neutro';
        if (delta < -MARGEM) dica = 'quente';
        else if (delta > MARGEM) dica = 'frio';
        return { ...base, dica, confiavel: true, delta };
    }

    function mostrar(resultado) {
        const chave = resultado.confiavel && resultado.dica ? resultado.dica : 'semdica';
        const estilo = ESTILOS[chave] ?? ESTILOS.semdica;
        elemento.textContent = estilo.texto;
        elemento.style.background = estilo.cor;
        elemento.style.display = 'block';
    }

    async function tick() {
        if (ocupado || !ultimaLeitura) return;
        ocupado = true;
        try {
            const agora = Date.now();
            let fonte = 'gps';

            // GPS ruim: nem pergunta à IA, e zera o histórico para não comparar com lixo
            if (ultimaLeitura.accuracy > ACCURACY_MAX) {
                historico.length = 0;
            } else {
                const corrigida = await corrigir(ultimaLeitura);
                fonte = corrigida.fonte;
                historico.push({ t: agora, dist: corrigida.dist });
                while (historico.length && agora - historico[0].t > (JANELA_S + 2) * 1000) {
                    historico.shift();
                }
            }

            if (timer === null) return; // parou enquanto esperava a IA
            const resultado = decidir(agora, fonte);
            mostrar(resultado);
            aoAtualizar?.(resultado);
        } finally {
            ocupado = false;
        }
    }

    return {
        /** chamar a cada atualização do GPS, com a distância BRUTA até o objeto */
        registrar(dGps, accuracy) {
            ultimaLeitura = { t: Date.now(), dGps, accuracy: accuracy ?? Infinity };
        },
        iniciar() {
            if (timer === null) timer = setInterval(tick, INTERVALO_MS);
        },
        parar() {
            if (timer !== null) clearInterval(timer);
            timer = null;
            elemento.style.display = 'none';
        },
        /** dentro do raio do ponto: para as dicas e avisa que chegou */
        chegou() {
            this.parar();
            elemento.textContent = '🎯 Você chegou! Procure o objeto pela câmera';
            elemento.style.background = '#16a34a';
            elemento.style.display = 'block';
        },
    };
}
