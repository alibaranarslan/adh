<?php

namespace Database\Seeders;

use App\Models\Page;
use Illuminate\Database\Seeder;

class CustomerContentSeeder extends Seeder
{
    public function run(): void
    {
        $privacyTextTr = <<<'HTML'
<h2>KİŞİSEL VERİLERİN KORUNMASI AYDINLATMA METNİ (KVKK)</h2>
<p>Adıyaman Dijital Haber olarak, 6698 sayılı Kişisel Verilerin Korunması Kanunu (“KVKK”) kapsamında kişisel verilerinizin güvenliğine büyük önem vermekteyiz. Bu aydınlatma metni ile, kişisel verilerinizin hangi amaçlarla işlendiği ve haklarınız hakkında sizleri bilgilendirmekteyiz.</p>
<h3>1. Veri Sorumlusu</h3>
<p>KVKK uyarınca, kişisel verileriniz; veri sorumlusu sıfatıyla Adıyaman Dijital Haber tarafından aşağıda açıklanan kapsamda işlenebilecektir.</p>
<h3>2. İşlenen Kişisel Veriler</h3>
<p>Tarafımıza ilettiğiniz veya dijital platformlarımız üzerinden elde edilen;</p>
<ul>
<li>Ad, soyad</li>
<li>Telefon numarası</li>
<li>E-posta adresi</li>
<li>IP adresi ve kullanıcı işlem bilgileri</li>
<li>Yorum ve mesaj içerikleri</li>
</ul>
<p>gibi kişisel verileriniz işlenebilmektedir.</p>
<h3>3. Kişisel Verilerin İşlenme Amaçları</h3>
<p>Toplanan kişisel verileriniz;</p>
<ul>
<li>Haber, duyuru ve bilgilendirme faaliyetlerinin yürütülmesi</li>
<li>Okuyucu taleplerinin karşılanması ve iletişimin sağlanması</li>
<li>Web sitesi ve dijital platformların geliştirilmesi</li>
<li>Yasal yükümlülüklerin yerine getirilmesi</li>
</ul>
<p>amaçlarıyla işlenmektedir.</p>
<h3>4. Kişisel Verilerin Aktarılması</h3>
<p>Kişisel verileriniz;</p>
<ul>
<li>Yasal zorunluluklar kapsamında yetkili kamu kurum ve kuruluşlarına,</li>
<li>Hizmet aldığımız teknik altyapı sağlayıcılarına (hosting, yazılım vb.)</li>
</ul>
<p>KVKK’ya uygun şekilde aktarılabilmektedir.</p>
<h3>5. Kişisel Veri Toplama Yöntemi ve Hukuki Sebep</h3>
<p>Kişisel verileriniz; web sitemiz, sosyal medya platformları, iletişim formları ve benzeri dijital kanallar aracılığıyla otomatik veya kısmen otomatik yöntemlerle toplanmakta olup; KVKK’nın 5. ve 6. maddelerinde belirtilen hukuki sebeplere dayanılarak işlenmektedir.</p>
<h3>6. KVKK Kapsamındaki Haklarınız</h3>
<p>KVKK’nın 11. maddesi uyarınca;</p>
<ul>
<li>Kişisel verilerinizin işlenip işlenmediğini öğrenme</li>
<li>İşlenmişse buna ilişkin bilgi talep etme</li>
<li>Amacına uygun kullanılıp kullanılmadığını öğrenme</li>
<li>Eksik veya yanlış işlenmişse düzeltilmesini isteme</li>
<li>Silinmesini veya yok edilmesini talep etme</li>
<li>İşlemenin hukuka aykırı olması halinde zararın giderilmesini talep etme</li>
</ul>
<p>haklarına sahipsiniz.</p>
<h3>7. İletişim</h3>
<p>KVKK kapsamındaki haklarınıza ilişkin taleplerinizi bizimle aşağıdaki iletişim kanallarından paylaşabilirsiniz:</p>
<p>Telefon: 0552 271 70 67</p>
<p>Adres: Adıyaman</p>
<p>Adıyaman Dijital Haber, kişisel verilerinizi hukuka uygun şekilde işlemeyi ve korumayı taahhüt eder.</p>
HTML;

        $cookiePolicyTr = <<<'HTML'
<h2>ÇEREZ POLİTİKASI</h2>
<p>Adıyaman Dijital Haber web sitesinde kullanıcı deneyimini geliştirmek, site performansını artırmak ve ziyaretçilere kişiselleştirilmiş içerik sunmak amacıyla çerezler kullanılmaktadır. Bu politika, hangi çerezlerin kullanıldığı ve kullanıcı haklarınız hakkında bilgilendirme amacı taşır.</p>
<h3>1. Çerez Nedir?</h3>
<p>Çerez, bir web sitesini ziyaret ettiğinizde tarayıcınıza kaydedilen küçük veri dosyalarıdır. Çerezler, siteyi tekrar ziyaret ettiğinizde bilgilerinizi hatırlamak ve kullanıcı deneyimini geliştirmek için kullanılır.</p>
<h3>2. Kullanılan Çerez Türleri</h3>
<h4>Zorunlu Çerezler</h4>
<p>Web sitesinin temel işlevlerini sağlamak için kullanılır. Site navigasyonu ve güvenlik gibi işlemler için gereklidir.</p>
<h4>Performans ve Analitik Çerezler</h4>
<p>Site trafiğini ve kullanıcı davranışlarını analiz eder. Site kullanımını iyileştirmek ve içerikleri geliştirmek için kullanılır.</p>
<h4>Fonksiyonel Çerezler</h4>
<p>Tercihlerinizin (dil, tema vb.) hatırlanmasını sağlar. Kullanıcı deneyimini kişiselleştirmek için kullanılır.</p>
<h4>Reklam / Pazarlama Çerezleri</h4>
<p>İlgi alanlarınıza uygun reklam ve içerik sunmak için kullanılır. Üçüncü taraf platformlar tarafından da yerleştirilebilir.</p>
<h3>3. Çerezlerin Yönetimi</h3>
<p>Kullanıcılar, tarayıcı ayarlarından çerezleri reddedebilir veya silebilir. Ancak bazı çerezleri devre dışı bırakmanız durumunda, web sitesinin bazı özellikleri tam olarak çalışmayabilir.</p>
<p>Chrome, Firefox, Safari, Edge vb. tarayıcılarda “Ayarlar &gt; Gizlilik ve güvenlik &gt; Çerezler” bölümünden yönetilebilir.</p>
<h3>4. KVKK ve Kişisel Veriler</h3>
<p>Çerezler aracılığıyla elde edilen bilgiler, 6698 sayılı Kişisel Verilerin Korunması Kanunu (KVKK) kapsamında işlenmektedir. Kullanıcılar, kişisel verilerine ilişkin haklarını KVKK Aydınlatma Metni üzerinden kullanabilir.</p>
<h3>5. İletişim</h3>
<p>Çerez politikası veya kişisel verilerinizle ilgili sorularınız için bizimle iletişime geçebilirsiniz:</p>
<p>Telefon: 0552 271 70 67</p>
<p>Adres: Adıyaman</p>
HTML;

        $pages = [
            [
                'slug' => 'gizlilik-politikasi',
                'title' => ['tr' => 'Gizlilik Politikası', 'en' => 'Privacy Policy', 'ku' => 'Siyaseta Nepenîtiyê'],
                'content' => [
                    'tr' => $privacyTextTr,
                    'en' => <<<'HTML'
<h2>PRIVACY POLICY</h2>
<p>Adıyaman Digital News values the protection of personal data under Turkish Personal Data Protection Law No. 6698.</p>
<p>Personal data may be processed to publish news, respond to reader requests, improve digital services and comply with legal obligations. Data may be transferred to technical infrastructure providers or public authorities when legally required.</p>
HTML,
                ],
                'meta_title' => ['tr' => 'Gizlilik Politikası | Adıyaman Dijital Haber', 'en' => 'Privacy Policy | Adıyaman Digital News'],
                'meta_description' => ['tr' => 'Adıyaman Dijital Haber KVKK Aydınlatma Metni ve Gizlilik Politikası', 'en' => 'Adıyaman Digital News KVKK Disclosure Text and Privacy Policy'],
                'is_published' => true,
                'sort_order' => 3,
            ],
            [
                'slug' => 'cerez-politikasi',
                'title' => ['tr' => 'Çerez Politikası', 'en' => 'Cookie Policy', 'ku' => 'Siyaseta Çerezê'],
                'content' => [
                    'tr' => $cookiePolicyTr,
                    'en' => <<<'HTML'
<h2>COOKIE POLICY</h2>
<p>Adıyaman Digital News uses cookies to improve user experience, remember preferences, measure site performance and support relevant content delivery.</p>
<p>You can manage cookie preferences through your browser or our consent banner. Disabling some cookies may affect certain site features.</p>
HTML,
                ],
                'meta_title' => ['tr' => 'Çerez Politikası | Adıyaman Dijital Haber', 'en' => 'Cookie Policy | Adıyaman Digital News'],
                'meta_description' => ['tr' => 'Adıyaman Dijital Haber çerez politikası hakkında bilgi', 'en' => 'Information about Adıyaman Digital News cookie policy'],
                'is_published' => true,
                'sort_order' => 4,
            ],
            [
                'slug' => 'kvkk-aydinlatma',
                'title' => ['tr' => 'KVKK Aydınlatma Metni', 'en' => 'KVKK Disclosure Text', 'ku' => 'Metna Ronîkirina KVKK'],
                'content' => [
                    'tr' => $privacyTextTr,
                    'en' => <<<'HTML'
<h2>KVKK DISCLOSURE TEXT</h2>
<p>This notice explains the processing of personal data by Adıyaman Digital News under Law No. 6698 and the rights available to data subjects.</p>
HTML,
                ],
                'is_published' => true,
                'sort_order' => 5,
            ],
            [
                'slug' => 'kunye',
                'title' => ['tr' => 'Künye', 'en' => 'Masthead', 'ku' => 'Nasname'],
                'content' => [
                    'tr' => <<<'HTML'
<h2>KÜNYE</h2>
<p><strong>Adıyaman Dijital Haber</strong></p>
<p><strong>Yayın Sahibi / Sorumlu Müdür:</strong> Nesim ALTUNGÜL</p>
<p><strong>Adres:</strong> Adıyaman, Türkiye</p>
<p><strong>Telefon:</strong> 0552 271 70 67</p>
<p><strong>E-posta:</strong> info@adiyamandijitalhaber.com</p>
<p><strong>Web Sitesi:</strong> www.adiyamandijitalhaber.com</p>
<p><strong>Kayıt ve Telif Hakları:</strong> Tüm içerikler Adıyaman Dijital Haber’e aittir ve izinsiz kullanılamaz.</p>
<p><strong>Yayın Türü:</strong> Dijital Haber Platformu</p>
<p><strong>Kuruluş Amacı:</strong> Adıyaman ve çevresindeki gelişmeleri hızlı, doğru ve tarafsız şekilde okuyuculara ulaştırmak.</p>
<p><strong>KVKK ve Gizlilik Politikası:</strong> Kişisel verileriniz 6698 sayılı KVKK kapsamında korunmaktadır. Detaylar için <a href="/sayfa/gizlilik-politikasi">Gizlilik Politikası</a> sayfamızı inceleyebilirsiniz.</p>
<p><strong>Çerez Politikası:</strong> Web sitemizde kullanıcı deneyimini geliştirmek için çerezler kullanılmaktadır. Detaylar için <a href="/sayfa/cerez-politikasi">Çerez Politikası</a> sayfamızı inceleyebilirsiniz.</p>
<p><strong>İtiraz ve Şikayet:</strong> Yayın içerikleri ile ilgili görüş, itiraz veya şikayetlerinizi info@adiyamandijitalhaber.com adresine iletebilirsiniz.</p>
HTML,
                    'en' => <<<'HTML'
<h2>MASTHEAD</h2>
<p><strong>Adıyaman Digital News</strong></p>
<p><strong>Publisher / Editor-in-Chief:</strong> Nesim ALTUNGÜL</p>
<p><strong>Address:</strong> Adıyaman, Türkiye</p>
<p><strong>Phone:</strong> 0552 271 70 67</p>
<p><strong>Email:</strong> info@adiyamandijitalhaber.com</p>
<p><strong>Website:</strong> www.adiyamandijitalhaber.com</p>
<p><strong>Copyright:</strong> All content belongs to Adıyaman Digital News and may not be used without permission.</p>
HTML,
                ],
                'meta_title' => ['tr' => 'Künye | Adıyaman Dijital Haber', 'en' => 'Masthead | Adıyaman Digital News'],
                'meta_description' => ['tr' => 'Adıyaman Dijital Haber künye bilgileri', 'en' => 'Adıyaman Digital News masthead information'],
                'is_published' => true,
                'sort_order' => 6,
            ],
            [
                'slug' => 'yayin-ilkeleri',
                'title' => ['tr' => 'Yayın İlkeleri', 'en' => 'Editorial Principles', 'ku' => 'Prensîbên Weşanê'],
                'content' => [
                    'tr' => <<<'HTML'
<h2>YAYIN İLKELERİ</h2>
<p>Adıyaman Dijital Haber, yayın faaliyetlerini; doğruluk, tarafsızlık ve etik gazetecilik ilkeleri çerçevesinde sürdürmeyi taahhüt eder.</p>
<p>Yayınlanan tüm haber ve içeriklerde doğruluk ve güvenilirlik esastır. Kaynağı doğrulanmamış bilgiler kamuoyuna sunulmaz.</p>
<p>Haberlerde tarafsızlık ilkesi gözetilir; kişi, kurum ve kuruluşlara karşı ön yargılı veya yönlendirici bir dil kullanılmaz.</p>
<p>Özel hayatın gizliliğine saygı duyulur; kişilik haklarını ihlal edici yayınlardan kaçınılır.</p>
<p>Toplumu yanıltıcı, panik oluşturucu veya kamu düzenini zedeleyici içeriklere yer verilmez.</p>
<p>Hakaret, ayrımcılık, nefret söylemi ve şiddeti teşvik eden ifadeler yayınlanmaz.</p>
<p>Yayınlanan içeriklerde kaynak gösterimine özen gösterilir ve telif haklarına saygı duyulur.</p>
<p>Hatalı veya eksik olduğu tespit edilen içerikler, en kısa sürede düzeltilir veya yayından kaldırılır.</p>
<p>Okuyucuların eleştiri ve görüşlerine açık olunarak, gerekli durumlarda düzeltme ve cevap hakkı tanınır.</p>
<p>Reklam ve sponsorlu içerikler, haber içeriklerinden ayırt edilebilir şekilde sunulur.</p>
<p>Adıyaman Dijital Haber, kamuoyunun doğru ve hızlı bilgiye ulaşma hakkını gözeterek, sorumlu yayıncılık anlayışıyla faaliyet göstermeye devam eder.</p>
HTML,
                    'en' => <<<'HTML'
<h2>EDITORIAL PRINCIPLES</h2>
<p>Adıyaman Digital News commits to publishing within the framework of accuracy, impartiality and ethical journalism.</p>
<p>Unverified information is not presented to the public, privacy is respected and hateful, discriminatory or manipulative language is avoided.</p>
HTML,
                ],
                'meta_title' => ['tr' => 'Yayın İlkeleri | Adıyaman Dijital Haber', 'en' => 'Editorial Principles | Adıyaman Digital News'],
                'meta_description' => ['tr' => 'Adıyaman Dijital Haber yayın ilkeleri ve etik kuralları', 'en' => 'Adıyaman Digital News editorial principles and ethics'],
                'is_published' => true,
                'sort_order' => 7,
            ],
            [
                'slug' => 'hakkimizda',
                'title' => ['tr' => 'Hakkımızda', 'en' => 'About Us', 'ku' => 'Derbarê Me'],
                'content' => [
                    'tr' => <<<'HTML'
<h2>Adıyaman Dijital Haber</h2>
<p>Adıyaman Dijital Haber, Adıyaman ve çevresindeki gelişmeleri güncel, tarafsız ve hızlı bir şekilde okuyucularına ulaştırmayı hedefleyen dijital haber platformudur.</p>
<p>İHA iş birliğiyle ulusal haberleri de takipçilerine sunan platformumuz, yerel gazeteciliği dijital çağa taşıma vizyonuyla hareket etmektedir.</p>
<p>Doğruluk, tarafsızlık ve etik gazetecilik ilkeleriyle kamuoyunu bilgilendirmeyi; okuyucu güvenini ve şeffaf yayıncılığı merkeze almayı sürdürüyoruz.</p>
<p><strong>Yayın Sahibi / Sorumlu Müdür:</strong> Nesim ALTUNGÜL</p>
<p><strong>İletişim:</strong> info@adiyamandijitalhaber.com.tr | 0552 271 70 67</p>
HTML,
                    'en' => <<<'HTML'
<h2>Adıyaman Digital News</h2>
<p>Adıyaman Digital News is a digital news platform focused on delivering developments from Adıyaman and its surroundings in a current, impartial and fast manner.</p>
HTML,
                ],
                'is_published' => true,
                'sort_order' => 1,
            ],
            [
                'slug' => 'iletisim',
                'title' => ['tr' => 'İletişim', 'en' => 'Contact', 'ku' => 'Pêwendî'],
                'content' => [
                    'tr' => <<<'HTML'
<h2>Bize Ulaşın</h2>
<p><strong>Adres:</strong> Adıyaman, Türkiye</p>
<p><strong>Telefon:</strong> 0552 271 70 67</p>
<p><strong>E-posta:</strong> info@adiyamandijitalhaber.com.tr</p>
<p><strong>Web:</strong> www.adiyamandijitalhaber.com.tr</p>
<p><strong>Çalışma Saatleri:</strong> Pazartesi - Cumartesi, 09:00 - 18:00</p>
HTML,
                    'en' => <<<'HTML'
<h2>Get in Touch</h2>
<p><strong>Address:</strong> Adıyaman, Türkiye</p>
<p><strong>Phone:</strong> 0552 271 70 67</p>
<p><strong>Email:</strong> info@adiyamandijitalhaber.com.tr</p>
<p><strong>Web:</strong> www.adiyamandijitalhaber.com.tr</p>
HTML,
                ],
                'is_published' => true,
                'sort_order' => 2,
            ],
        ];

        foreach ($pages as $data) {
            Page::updateOrCreate(['slug' => $data['slug']], $data);
        }
    }
}
