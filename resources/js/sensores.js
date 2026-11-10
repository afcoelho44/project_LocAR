let girou = 0;

window.addEventListener('devicemotion', (e) => {
    const r = e.rotationRate;
    if(!r || r.alpha == null) return;
    girou += e.rotationRate.alpha * e.interval / 1000;
});
export async function pedirPermissao() {
    // Android: a função não existe → não precisa pedir, já funciona
    if (typeof DeviceMotionEvent.requestPermission !== 'function') {
        return true;
    }

    // iPhone: pede e espera a resposta
    const resposta = await DeviceMotionEvent.requestPermission();
    return resposta === 'granted';
}

export function getGirou() {
    const aux = girou;
    girou = 0;
    return aux;
}