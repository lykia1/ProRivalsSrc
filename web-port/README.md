# AceTR PHP uyarlaması — ilk gerçek kural aktarımı

Bu klasör, komut tabanlı tarayıcı uyarlamasının başlangıcıdır. Tam AirRivals kopyası veya tamamlanmış çok oyunculu oyun değildir. Mevcut C++ projelerini değiştirmez.

## Aktarılanlar

- `Common/AtumSJ.cpp` içindeki 342 satırlık stat ve 121 satırlık deneyim tablosu.
- `S_CAN_SERVER_SETTING_HSSON` profilindeki dört geminin altı stat artışı. Bu profil özellikle seçilmiştir; başka derleme ayarlarının aynı olduğu varsayılmaz.
- `GetCharacterHP/DP/SP/EP`, saldırı artışı, isabet bonusu, savunma ve kaçınma temel değerleri.
- `GetAttackC` / `GetMaxAttackC` temel silah formülü (Ability × modifier × attack stat rate).
- 100. seviyeye kadar 300, sonrasında 340 stat limiti; seviye başlangıç deneyimi.
- C++ INT/SHORT dönüşümleri, özellikle Pierce ve Shield değerlerinde korunur.

## Kullanım

PHP 8.2+ ile `php web-port/tests/rules.php` çalıştırılır. Bağımlılık gerekmez. Hesaplayıcıyı açmak için `php -S 127.0.0.1:8080 -t web-port/public` çalıştırıp `http://127.0.0.1:8080` adresine gidin. XAMPP için `web-port` klasörünü htdocs içine koyup `web-port/public/` açabilirsiniz. `src/Rules.php` içindeki `AceTR\Rules` sunucuda kullanılabilir.

Kaynak tablolarını yeniden çıkarmak için depo kökünde `python web-port/tools/extract_rules.py` çalıştırılır. JSON kaynağın commit bilgisini taşır.

## Gerçek eşya / mob / harita verileri

Depoda `Database/acc.bak`, `db_1.bak`, `arena.bak` bulunuyor. Bunlar SQL Server yedekleridir; JSON veya MySQL dosyaları değildir. Yedekler ayrı bir yerel SQL Server ortamında restore edilip incelenmelidir. Bu çalışma sırasında restore edilmedi.

`tools/export_catalog.php`, restore edilmiş oyun tanımlarının bulunduğu veritabanından yalnızca yedi `ti_*` tablosunu dışarı aktarır. `ACETR_SOURCE_DSN`, isteğe bağlı `ACETR_SOURCE_USER` ve `ACETR_SOURCE_PASSWORD` ortam değişkenlerini kullanır. PDO SQLSRV sürücüsü gerekir. Windows kimlik doğrulamasında kullanıcı ve parola değişkenleri gerekmez. Salt okunur bağlantı kullanın. Account, karakter ve log verileri dışarı aktarılmaz.

Örnek DSN: `sqlsrv:Server=localhost;Database=restored_game_definitions;Encrypt=yes`. Parolaları kaynak dosyasına yazmayın. Araç bağlantı hatalarını veya DSN'i çıktıya dökmez. Tablo bulunamazsa eksik katalogla devam etmez.

## Henüz aktarılmayanlar

Gerçek katalog satırları, tam hasar / enchant / skill / drop hesapları, uçuş yerine komut çözümleme, PHP/MySQL hesapları ve kalıcı görev kuyruğu, tugay/ulus savaşları ve özgün istemci görselleri. Prototipteki savaş ve eşya örnekleri bu aktarımla birebir orijinal olarak sunulmaz.
