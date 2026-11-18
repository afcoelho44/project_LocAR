#!/usr/bin/env bash
# Instala o project_LocAR + componente_IA numa VM Ubuntu (Oracle Cloud Always Free).
# Rode de dentro da pasta do project_LocAR:   bash deploy/instalar.sh
# Pode rodar de novo sem problema (o que já existe é mantido).
set -euo pipefail
cd "$(dirname "$0")/.."
RAIZ=$(pwd)
IA_DIR=${IA_DIR:-$(realpath -m "$RAIZ/../componente_IA")}
# mesmo endereço (e token, se houver) do project_LocAR, trocando o nome do repo
ORIGEM=$(git remote get-url origin 2>/dev/null || echo https://github.com/afcoelho44/project_LocAR.git)
IA_REPO=${IA_REPO:-$(echo "$ORIGEM" | sed -E 's#project_LocAR(\.git)?$#componente_IA.git#I')}
DC="sudo docker compose -f docker-compose.prod.yml"

passo() { echo -e "\n==> $*"; }

# ---------- 1. swap (a VM AMD gratuita tem só 1 GB de RAM) ----------
if [ "$(free -m | awk '/Mem:/{print $2}')" -lt 3000 ] && ! sudo swapon --show | grep -q /swapfile; then
  passo "Criando 2 GB de swap"
  sudo fallocate -l 2G /swapfile && sudo chmod 600 /swapfile
  sudo mkswap /swapfile && sudo swapon /swapfile
  echo '/swapfile none swap sw 0 0' | sudo tee -a /etc/fstab >/dev/null
fi

# ---------- 2. Docker ----------
if ! command -v docker >/dev/null; then
  passo "Instalando o Docker"
  curl -fsSL https://get.docker.com | sudo sh
fi

# ---------- 3. firewall da própria VM (as imagens Ubuntu da Oracle bloqueiam 80/443 no iptables) ----------
passo "Liberando as portas 80 e 443 no iptables"
liberar() {  # $1 = tcp|udp, $2 = porta. Insere antes do REJECT final (se houver).
  sudo iptables -C INPUT -p "$1" --dport "$2" -j ACCEPT 2>/dev/null && return
  local pos
  pos=$(sudo iptables -L INPUT --line-numbers -n | awk '$2=="REJECT"{print $1; exit}')
  if [ -n "$pos" ]; then sudo iptables -I INPUT "$pos" -p "$1" --dport "$2" -j ACCEPT
  else sudo iptables -A INPUT -p "$1" --dport "$2" -j ACCEPT; fi
}
liberar tcp 80; liberar tcp 443; liberar udp 443
if command -v netfilter-persistent >/dev/null; then sudo netfilter-persistent save; fi

# ---------- 4. componente_IA ----------
if [ ! -d "$IA_DIR" ]; then
  passo "Clonando o componente_IA em $IA_DIR"
  git clone "$IA_REPO" "$IA_DIR"
fi
mkdir -p "$IA_DIR/modelos" "$IA_DIR/dados"

# ---------- 5. .env ----------
if [ ! -f .env ]; then
  passo "Criando o .env"
  if [ -z "${DOMINIO:-}" ]; then
    IP=$(curl -fsS https://ifconfig.me || curl -fsS https://api.ipify.org)
    DOMINIO=${IP//./-}.sslip.io
  fi
  aleatorio() { openssl rand -hex 12; }
  SENHA_PROF=${SENHA_PROFESSOR:-$(openssl rand -base64 9 | tr -d '/+=')}
  sed -e "s/__DOMINIO__/$DOMINIO/g" \
      -e "s/__DB_PASSWORD__/$(aleatorio)/" \
      -e "s/__DB_ROOT_PASSWORD__/$(aleatorio)/" \
      -e "s/__SENHA_PROFESSOR__/$SENHA_PROF/" \
      deploy/env.producao.exemplo > .env
fi
grep -q "^IA_DIR=" .env || echo "IA_DIR=$IA_DIR" >> .env

# ---------- 6. containers e dependências ----------
passo "Subindo MySQL, PHP e IA"
$DC up -d --build mysql app ia

passo "composer install"
$DC exec -T app composer install --no-dev --optimize-autoloader --no-interaction

passo "Build do front (Vite)"
$DC run --rm node sh -c "npm ci --no-audit --no-fund && npm run build"

passo "Esperando o MySQL"
env_val() { grep "^$1=" .env | cut -d= -f2-; }
for i in $(seq 1 60); do
  $DC exec -T app php -r "new PDO('mysql:host=mysql;dbname=$(env_val DB_DATABASE)','$(env_val DB_USERNAME)','$(env_val DB_PASSWORD)');" \
    >/dev/null 2>&1 && break
  [ "$i" = 60 ] && { echo "MySQL não respondeu"; exit 1; }
  sleep 3
done

passo "Laravel: chave, banco, storage"
$DC exec -T app php artisan config:clear
grep -q '^APP_KEY=base64' .env || $DC exec -T app php artisan key:generate --force
$DC exec -T app php artisan migrate --force
if [ "$($DC exec -T app php artisan tinker --execute='echo \App\Models\User::count();' | tail -1)" = "0" ]; then
  $DC exec -T app php artisan db:seed --force
fi
$DC exec -T app php artisan storage:link 2>/dev/null || true
sudo chown -R 33:33 storage bootstrap/cache
$DC exec -T app php artisan config:cache
$DC exec -T app php artisan route:cache
$DC exec -T app php artisan view:cache

passo "Subindo o Caddy (HTTPS)"
$DC up -d caddy

DOMINIO=$(grep '^DOMINIO=' .env | cut -d= -f2)
echo
echo "Pronto: https://$DOMINIO"
echo "Professor: usuário 'professor', senha: $(grep '^SENHA_PROFESSOR=' .env | cut -d= -f2)"
echo "(o primeiro acesso pode levar ~1 min enquanto o certificado é emitido)"
