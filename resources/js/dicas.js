// dicas.js — dica de proximidade (quente / morno / frio) na tela de RA.
//
// A cada segundo manda as últimas leituras do GPS para o Laravel, que devolve a
// classe. O Laravel decide quem classifica (IA_MODO no .env):
//   - componente de IA (Random Forest, repo componente_IA), ou
//   - a regra de distância: quente <= 10 m, morno <= 15 m, frio > 15 m.
// O aluno não fica sabendo qual dos dois classificou.
//
// Sem dica quando o GPS está ruim (accuracy > ACCURACY_MAX) ou sem leitura nova há
// mais de GPS_VELHO_MS. Se o servidor falhar ou demorar, a dica sai pela regra aqui
// mesmo, com os mesmos limites. O jogo nunca trava por causa dela.
//
// ?dica=quente|morno|frio|semdica força o estado (útil para gravar vídeo).
// ?debug=1 mostra no status quem classificou (IA ou regra).

// mesmos limites do App\Support\Proximidade (Laravel) e do modelo_classe.py (componente_IA)
const LIMITES = [[10, 'quente'], [15, 'morno']];

const ACCURACY_MAX = 20;    // m — acima disso, sem dica
const MAX_LEITURAS = 5;     // leituras enviadas por pedido (a IA usa as 5 últimas)
const INTERVALO_MS = 1000;  // uma dica por segundo
const GPS_VELHO_MS = 5000;  // sem leitura nova há mais que isso -> sem dica
const TIMEOUT_MS = 2000;    // tempo máximo esperando o servidor

const ESTILOS = {
    quente:  { texto: '🔥 Quente — o objeto está bem perto!', cor: '#dc2626' },
    morno:   { texto: '〰️ Morno — está chegando',            cor: '#d97706' },
    frio:    { texto: '❄️ Frio — ainda está longe',           cor: '#2563eb' },
    semdica: { texto: '📡 Sem dica — GPS impreciso',          cor: '#374151' },
};

export function classePorDistancia(dist) {
    for (const [limite, nome] of LIMITES) {
        if (dist <= limite) return nome;
    }
    return 'frio';
}

/**
 * @param elemento    div onde a dica aparece
 * @param url         rota POST do Laravel que classifica (null = só a regra, sem servidor)
 * @param csrf        token CSRF do Laravel
 * @param aoAtualizar callback(resultado) opcional, chamado a cada dica (para o HUD)
 */
export function criarDicas({ elemento, url = null, csrf = null, aoAtualizar = null }) {
    const params = new URLSearchParams(location.search);
    const forcada = params.get('dica') === 'neutro' ? 'morno' : params.get('dica');
    const leituras = [];  // { lat, lng, accuracy, speed, t_ms, dist } — mais antiga primeiro
    let timer = null;
    let ocupado = false;

    function pelaRegra() {
        const dist = leituras[leituras.length - 1].dist;
        return { classe: classePorDistancia(dist), fonte: 'regra', dist };
    }

    async function classificar() {
        if (!url) return pelaRegra();

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
                body: JSON.stringify({
                    leituras: leituras.map(({ lat, lng, accuracy, speed, t_ms }) => ({ lat, lng, accuracy, speed, t_ms })),
                }),
            });
            if (!res.ok) return pelaRegra();
            const json = await res.json();
            return ESTILOS[json.classe] && json.classe !== 'semdica'
                ? { classe: json.classe, fonte: json.fonte ?? 'ia', dist: json.dist_m }
                : pelaRegra();
        } catch {
            return pelaRegra(); // timeout ou rede
        } finally {
            clearTimeout(corte);
        }
    }

    function mostrar(chave) {
        const estilo = ESTILOS[chave] ?? ESTILOS.semdica;
        elemento.textContent = estilo.texto;
        elemento.style.background = estilo.cor;
        elemento.style.display = 'block';
    }

    async function tick() {
        if (ocupado) return;
        ocupado = true;
        try {
            const ultima = leituras[leituras.length - 1];
            let resultado = { classe: null, fonte: null, dist: ultima?.dist ?? null };

            if (forcada) {
                resultado = { ...resultado, classe: forcada === 'semdica' ? null : forcada, fonte: 'forcada' };
            } else if (ultima && ultima.accuracy <= ACCURACY_MAX && Date.now() - ultima.t_ms <= GPS_VELHO_MS) {
                resultado = await classificar();
            }

            if (timer === null) return; // parou enquanto esperava o servidor
            mostrar(resultado.classe ?? 'semdica');
            aoAtualizar?.(resultado);
        } finally {
            ocupado = false;
        }
    }

    return {
        /** chamar a cada atualização do GPS (posição BRUTA e distância até o objeto) */
        registrar({ lat, lng, accuracy, speed = null, dist }) {
            leituras.push({
                lat, lng, dist, t_ms: Date.now(),
                accuracy: accuracy ?? Infinity,
                speed: Number.isFinite(speed) ? speed : null,
            });
            while (leituras.length > MAX_LEITURAS) leituras.shift();
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
