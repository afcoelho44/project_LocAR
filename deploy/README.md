# Deploy na VM do Oracle Cloud (Always Free)

Sobe tudo numa VM só: Laravel (PHP-FPM) + MySQL + componente_IA + Caddy (HTTPS automático,
necessário para câmera e GPS no celular). O endereço é `https://<ip-com-hifens>.sslip.io`,
sem precisar comprar domínio.

## 1. Criar a VM (console da Oracle)
1. **Compute → Instances → Create instance**
   - Imagem: **Ubuntu 22.04 ou 24.04**
   - Shape: **VM.Standard.A1.Flex** (Ampere, 1–2 OCPU, 6–12 GB) se houver capacidade;
     senão **VM.Standard.E2.1.Micro** (1 GB — o script cria swap, só fica mais lento para instalar)
   - Rede: deixe criar a VCN com sub-rede pública e **Assign a public IPv4 address**
   - Baixe a chave SSH
2. **Liberar as portas na rede**: na página da instância → Subnet → **Security List** →
   *Add Ingress Rules*:
   - Source `0.0.0.0/0`, TCP, porta **80**
   - Source `0.0.0.0/0`, TCP, porta **443**

   (sem a 80 aberta o certificado HTTPS não é emitido)

## 2. Acessar e clonar
Os repositórios são privados: crie um token em GitHub → Settings → Developer settings →
**Fine-grained tokens**, com acesso aos repos `project_LocAR` e `componente_IA`,
permissão *Contents: Read-only*.

```bash
ssh -i chave.key ubuntu@IP_DA_VM
sudo apt-get update && sudo apt-get install -y git
git clone https://afcoelho44:TOKEN@github.com/afcoelho44/project_LocAR.git
cd project_LocAR
```

## 3. Instalar
```bash
bash deploy/instalar.sh
```
O script instala o Docker, libera 80/443 no iptables da VM, clona o componente_IA ao lado,
gera o `.env` (senhas aleatórias), sobe os containers, compila o front, roda as migrations
e o seeder. No fim mostra o endereço e a senha do usuário `professor`.

Domínio próprio/DuckDNS: `DOMINIO=meu.duckdns.org bash deploy/instalar.sh` (antes de existir o `.env`).

## No dia a dia
| O quê | Comando (dentro de `project_LocAR`) |
|---|---|
| Atualizar depois de um merge na main | `bash deploy/atualizar.sh` |
| Ver logs | `sudo docker compose -f docker-compose.prod.yml logs -f caddy app ia` |
| Log do Laravel | `tail -f storage/logs/laravel.log` |
| Parar / subir | `sudo docker compose -f docker-compose.prod.yml down` / `up -d` |

## Treinar a IA (depois do experimento com a fita)
Sem modelo, a IA responde 503 e as dicas usam a regra de distância — o jogo funciona normalmente.

1. No PC: `python analisar.py coleta_*.csv` → gera `resultados/leituras_processadas.csv`
2. Copiar para a VM:
   `scp -i chave.key resultados/leituras_processadas.csv ubuntu@IP_DA_VM:~/componente_IA/dados/`
3. Na VM: `bash deploy/treinar_ia.sh dados/leituras_processadas.csv --excluir-local "Entre o bloco F e o RU"`
   (treina, reinicia a API e mostra o `/saude`; as tabelas da validação (IA x regra) ficam em `componente_IA/dados/validacao/`)
