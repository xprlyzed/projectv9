# artirdim.com — Laravel 12 + Inertia + Vue 3 Canlı Müzayede Platformu

## Genel
- Kaynak: GitHub public repo `xprlyzed/projectv8` → Laravel projesi `/app/projecct` altında.
- Stack: Laravel 12 (PHP 8.2), MySQL/MariaDB, Redis, Inertia.js + Vue 3, Vite, Metronic teması.
- Servisler (supervisor): `mariadb`, `redis`, `laravel` (php artisan serve :3000), `laravel-schedule`.
- Preview kalıcı DEĞİL (pod restart'ta PHP/DB/apt paketleri sıfırlanır — KURULUM.md not).

## Kurulum Notları (bu ortamda)
- PHP 8.2 + eklentiler, Composer, MariaDB, Redis apt ile kuruldu.
- DB: `auction` / user `auction` / pass `auction123` (127.0.0.1:3306).
- `.env`: BROADCAST_CONNECTION=log, QUEUE_CONNECTION=sync, SCOUT_DRIVER=database, CACHE=database, SESSION=database.
- Reverb/LiveKit anahtarları boş → chat polling ile, canlı yayın anahtarsız (production'da doldurulur).
- Build: `yarn install --ignore-engines && yarn build`.
- Frontend port 3000 üzerinden `php artisan serve` (ingress → 3000).

## Bu Oturumda Yapılan (7 Görev) — 2026-06
1. **Sidebar toggle kaldırıldı** (masaüstü): hamburger/minimize state + CSS temizlendi; sidebar sabit tam genişlik. Mobil drawer korundu.
2. **Mesaj rozeti anlık güncelleme**: `composables/useUnreadMessages.js` (paylaşılan reactive sayaç); AppLayout'ta shared prop init + 15sn polling yedeği + Echo user-channel (`.message.notification`) dinleyici. Backend: `GET /messages/unread-count`, `App\Events\NewMessageNotification`, store()'da alıcıya broadcast.
3. **Renk paleti**: theme-new.css'e merkezi anlamsal değişkenler (`--color-primary/-hover/-surface/-surface-alt/-sidebar-bg/-success/-danger/-warning/-info/-text-*`) light+dark. Primary daha canlı (dark #3b82f6 / light #1d4ed8), sidebar arka planı içerikten ayrı, birincil butonlar + kart fiyat/süre + durum rozetleri güncellendi.
4. **Register yeniden**: telefon (tel+numeric, sadece rakam, otomatik 0 önek, "0 505 021 8283" görünüm, backend regex `^0[0-9]{10}$` + normalize), kullanım koşulları modalı (KVKK, ESC/backdrop kapatma, state korunur), rol kartları (ikon+açıklama+hover+seçili vurgu), blur inline doğrulama, submit spinner, adım göstergesi (progress bar), Enter ile ilerleme.
5. **E-posta şablonları**: `emails/layout.blade.php` ortak wrapper (tablo tabanlı, inline CSS, responsive); verify-custom / reset-password / contact yeniden yazıldı (marka header + CTA + footer).
6. **Skeleton loading**: theme-new.css `.skeleton` (çok duraklı gradyan shimmer, light/dark `--skeleton-base/-highlight`); `Components/Skeleton/SkeletonCard|Line|Avatar.vue`; Browse/Auctions + Explore sonsuz kaydırma yüklemesinde kullanıldı.
7. **Ölü markup**: `idx-price-overlay` (AuctionCard.vue + theme-new.css) kaldırıldı (fiyat kart gövdesinde zaten var).

## Test Hesapları (şifre: password)
admin@test.com · seller@test.com · buyer@test.com

## Kapsam Dışı Bırakılanlar (gözlem)
### Regresyon Düzeltmeleri (2. oturum)
- Sidebar rengi geri alındı: `.app-sidebar` background `var(--color-sidebar-bg)` → `var(--bg-soft)`. Diğer renkler (buton/rozet/fiyat) korundu.
- Skeleton görünürlüğü: useInfiniteScroll'a min 650ms loading süresi → Browse'da 8 skeleton doğrulandı (dark+light gradyan).
- Mesajlaşma: Messages poll 12s→3s; AudioContext resume+unlock (ses); badge poll 15s→4s. Sunucu testi: alıcı unread 0→1.
- **Rozet anında düşme**: `HandleInertiaRequests` `unread_messages` eager değer → `fn()=>...` lazy closure. Inertia share() controller'dan ÖNCE çalıştığı için eski (okunmadan önceki) sayı geliyordu; closure yanıt anında (index() okundu işaretledikten sonra) çözülüyor. Kanıt: liste sayfası unread=1 → sohbet açılınca AYNI yanıtta unread=0 → sidebar rozeti poll beklemeden anında düşüyor.


- Register step2 (satıcı) IBAN/vergi backend validasyonu mevcut haliyle korundu (yalnızca uzunluk); gerçek IBAN mod-97 doğrulaması eklenmedi (kapsam dışı).
- Reverb/LiveKit preview'da anahtarsız; gerçek zamanlı push production'da çalışır, preview'da polling yedeği devrede.

## Backlog / Sonraki
- P1: IBAN mod-97 doğrulaması; register e-posta/kullanıcı adı benzersizliğinin blur'da anlık AJAX kontrolü.
- P2: Diğer AJAX bölümlerine (profil, bildirim dropdown) skeleton yayma.
