// fake-walk.js — ?fake=walk: simula uma pessoa andando até o ponto, para
// demonstrar (e gravar) as dicas quente/frio sem sair da mesa.
//
// Roteiro (1 passo por segundo, distância medida a partir do alvo, ao sul dele):
//   1. aproxima de 70 m até ~50 m           -> quente
//   2. GPS ruim por alguns segundos         -> sem dica
//   3. vira e se afasta                     -> frio
//   4. fica parado                          -> neutro
//   5. volta a se aproximar até o ponto     -> quente -> pergunta abre
// No fim, para em cima do ponto.

const METROS_POR_GRAU_LAT = 111320;
const VELOCIDADE = 1.4; // m/s, passo normal

function montarRoteiro() {
    const passos = []; // { dist, accuracy }
    let d = 70;
    const add = (n, delta, accuracy = 5) => {
        for (let i = 0; i < n; i++) {
            d = Math.max(0, d + delta);
            passos.push({ dist: d, accuracy });
        }
    };
    add(14, -VELOCIDADE);          // 1. aproximando
    add(5, -0.3, 35);              // 2. GPS ruim
    add(9, +VELOCIDADE);           // 3. se afastando
    add(5, 0);                     // 4. parado
    add(Math.ceil(d / VELOCIDADE) + 1, -VELOCIDADE); // 5. até o ponto
    return passos;
}

/**
 * Inicia a caminhada simulada.
 * @param alvo        { lat, lng } do ponto
 * @param aoMover     callback({ latitude, longitude, accuracy }) a cada passo
 * @returns função para parar
 */
export function iniciarCaminhada(alvo, aoMover) {
    const passos = montarRoteiro();
    let i = 0;
    let timer = null;

    const passo = () => {
        const p = passos[Math.min(i, passos.length - 1)];
        // pessoa ao sul do alvo: latitude menor
        aoMover({
            latitude: alvo.lat - p.dist / METROS_POR_GRAU_LAT,
            longitude: alvo.lng,
            accuracy: p.accuracy,
        });
        i++;
        if (i >= passos.length && timer !== null) clearInterval(timer);
    };

    passo();
    timer = setInterval(passo, 1000);
    return () => clearInterval(timer);
}
