#!/usr/bin/env bash
# Treina o modelo do componente_IA no servidor e reinicia a API.
# Antes: copie o leituras_processadas.csv (saída do experimento/analisar.py) para componente_IA/dados/
#   bash deploy/treinar_ia.sh                                   # usa dados/leituras_processadas.csv
#   bash deploy/treinar_ia.sh dados/outro.csv --excluir-local "Entre o bloco F e o RU"
set -euo pipefail
cd "$(dirname "$0")/.."
DC="sudo docker compose -f docker-compose.prod.yml"
CSV=${1:-dados/leituras_processadas.csv}
shift || true

$DC run --rm ia python treinar.py "$CSV" modelos/modelo.joblib --saida-validacao dados/validacao "$@"
$DC restart ia
sleep 3
echo "Saúde da IA:"
$DC exec -T app curl -s http://ia:5000/saude; echo
# o Laravel pausa a IA por 30 s depois de uma falha; limpa para usar já
$DC exec -T app php artisan cache:forget ia_fora_do_ar >/dev/null 2>&1 || true
