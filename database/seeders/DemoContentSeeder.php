<?php

namespace Database\Seeders;

use App\Models\Advertisement;
use App\Models\Category;
use App\Models\NewsArticle;
use App\Models\Page;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DemoContentSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('email', 'admin@admin.com')->first()
            ?? User::first();

        $this->seedTags();
        // Demo haberler devre dışı — site IHA canlı haberleriyle çalışıyor
        // $this->seedNews($admin);
        $this->seedAdvertisements();
        $this->seedPages();
    }

    private function seedTags(): void
    {
        $tags = [
            ['tr' => 'Adıyaman',  'en' => 'Adıyaman',   'ku' => 'Adiyaman'],
            ['tr' => 'Belediye',  'en' => 'Municipality','ku' => 'Şaredarî'],
            ['tr' => 'Nemrut',    'en' => 'Nemrut',      'ku' => 'Nemrûd'],
            ['tr' => 'Deprem',    'en' => 'Earthquake',  'ku' => 'Erdhej'],
            ['tr' => 'Eğitim',   'en' => 'Education',   'ku' => 'Perwerde'],
            ['tr' => 'Sağlık',   'en' => 'Health',      'ku' => 'Tenduristî'],
            ['tr' => 'Tarım',    'en' => 'Agriculture', 'ku' => 'Çandinî'],
            ['tr' => 'Ekonomi',  'en' => 'Economy',     'ku' => 'Aborî'],
            ['tr' => 'Spor',     'en' => 'Sports',      'ku' => 'Werzîş'],
            ['tr' => 'Kültür',  'en' => 'Culture',     'ku' => 'Çand'],
            ['tr' => 'Şanlıurfa','en' => 'Şanlıurfa',  'ku' => 'Riha'],
            ['tr' => 'Gaziantep','en' => 'Gaziantep',  'ku' => 'Dîlok'],
            ['tr' => 'İnşaat',  'en' => 'Construction','ku' => 'Avakarî'],
            ['tr' => 'Turizm',  'en' => 'Tourism',     'ku' => 'Turîzm'],
            ['tr' => 'Çevre',   'en' => 'Environment', 'ku' => 'Jîngeh'],
            ['tr' => 'Teknoloji','en' => 'Technology',  'ku' => 'Teknolojî'],
            ['tr' => 'Gençlik', 'en' => 'Youth',       'ku' => 'Ciwanî'],
            ['tr' => 'Festival','en' => 'Festival',    'ku' => 'Festîval'],
        ];

        foreach ($tags as $tag) {
            Tag::firstOrCreate(
                ['slug' => Str::slug($tag['tr'])],
                ['name' => $tag]
            );
        }
    }

    private function seedNews(?User $admin): void
    {
        $categories = Category::where('is_active', true)->get()->keyBy('slug');

        $articles = [
            // GÜNDEM
            [
                'category' => 'gundem',
                'title' => [
                    'tr' => 'Adıyaman Valiliği Yeni Yatırım Planlarını Açıkladı',
                    'en' => 'Adıyaman Governorate Announces New Investment Plans',
                    'ku' => 'Waliyê Adiyamanê Planên Veberhênanê yên Nû Ragihand',
                ],
                'summary' => [
                    'tr' => 'Adıyaman Valisi, 2026 yılı için planlanan altyapı ve sosyal yatırım projelerini kamuoyuyla paylaştı.',
                    'en' => 'The Governor of Adıyaman shared the planned infrastructure and social investment projects for 2026 with the public.',
                    'ku' => 'Waliyê Adiyamanê projeyên binesaziyê û veberhênanên civakî yên plansazkirî ji bo sala 2026 bi giştî re parve kir.',
                ],
                'content' => [
                    'tr' => '<p>Adıyaman Valiliği, 2026 yılına ait kapsamlı yatırım planlarını basın toplantısıyla duyurdu. Açıklamaya göre, şehrin farklı ilçelerinde toplam 15 yeni projeye başlanacak.</p><p>Yatırımlar arasında yeni okul binaları, sağlık merkezleri, tarımsal sulama projeleri ve turizm altyapı geliştirmeleri yer alıyor. Vali, projelerin toplam bütçesinin 850 milyon TL olduğunu belirtti.</p><p>Özellikle deprem sonrası yeniden yapılanma kapsamında konut projelerinin hızla devam ettiği vurgulanan toplantıda, vatandaşların taleplerinin de dikkate alındığı ifade edildi.</p>',
                    'en' => '<p>Adıyaman Governorate announced its comprehensive investment plans for 2026 at a press conference. According to the statement, 15 new projects will commence in various districts of the city.</p><p>The investments include new school buildings, health centers, agricultural irrigation projects and tourism infrastructure development. The Governor stated that the total budget for the projects is 850 million TL.</p><p>At the meeting, it was emphasized that housing projects are rapidly progressing within the scope of post-earthquake reconstruction, and that citizens\' demands are also being taken into account.</p>',
                    'ku' => '<p>Waliyatiya Adiyamanê di konferansa çapemeniyê de planên veberhênanê yên berfireh ji bo sala 2026 ragihand. Li gor daxuyaniyê, di navçeyên cuda yên bajêr de dê 15 projeyek nû dest pê bike.</p><p>Di nav veberhênanan de avahiyên dibistanên nû, navendên tendurustiyê, projeyên avdankirina çandiniyê û pêşxistina binesaziya turîzmê hene. Walî diyar kir ku budceya giştî ya projeyan 850 milyon TL e.</p>',
                ],
                'is_featured' => true,
                'is_breaking' => false,
                'tags' => ['adiyaman', 'belediye'],
            ],
            [
                'category' => 'gundem',
                'title' => [
                    'tr' => 'Adıyaman\'da Bahar Temizliği Kampanyası Başladı',
                    'en' => 'Spring Cleaning Campaign Launched in Adıyaman',
                    'ku' => 'Li Adiyamanê Kampanyaya Paqijiya Biharê Dest Pê Kir',
                ],
                'summary' => [
                    'tr' => 'Belediye koordinasyonunda başlatılan kampanyayla şehir genelinde kapsamlı temizlik çalışmaları yürütülüyor.',
                    'en' => 'Comprehensive cleaning works are being carried out city-wide with the campaign launched under municipal coordination.',
                    'ku' => 'Bi kampanyaya ku di bin hevrêziya şaredariyê de hat destpêkirin, xebatên paqijiya berfireh li seranserê bajêr tên meşandin.',
                ],
                'content' => [
                    'tr' => '<p>Adıyaman Belediyesi, bahar aylarının gelmesiyle birlikte kapsamlı bir şehir temizliği kampanyası başlattı. "Temiz Adıyaman, Sağlıklı Adıyaman" sloganıyla başlatılan kampanya kapsamında 200\'den fazla personel görev yapıyor.</p><p>Belediye Başkanı yaptığı açıklamada, park ve bahçelerin yenilenmesi, cadde ve sokakların derinlemesine temizlenmesi, çöp toplama noktalarının modernize edilmesi gibi çalışmaların sürdüğünü belirtti.</p>',
                    'en' => '<p>Adıyaman Municipality launched a comprehensive city cleaning campaign with the arrival of spring. More than 200 staff are working within the scope of the campaign launched under the slogan "Clean Adıyaman, Healthy Adıyaman".</p><p>The Mayor stated that work continues on renewing parks and gardens, deep cleaning of streets and avenues, and modernizing garbage collection points.</p>',
                    'ku' => '<p>Şaredariya Adiyamanê bi hatina mehên biharê re kampanyayek paqijiya bajêr ya berfireh dest pê kir. Di çarçoveya kampanyayê de ku bi slogana "Adiyamana Paqij, Adiyamana Saxlem" hat destpêkirin, zêdetirî 200 personel kar dikin.</p>',
                ],
                'is_featured' => false,
                'is_breaking' => false,
                'tags' => ['adiyaman', 'belediye', 'cevre'],
            ],
            [
                'category' => 'gundem',
                'title' => [
                    'tr' => 'Adıyaman Havalimanı Yenileme Çalışmaları Tamamlandı',
                    'en' => 'Adıyaman Airport Renovation Works Completed',
                    'ku' => 'Xebatên Nûkirina Balafirgeha Adiyamanê Qediya',
                ],
                'summary' => [
                    'tr' => 'Adıyaman Havalimanı\'ndaki modernizasyon çalışmaları sona erdi, yeni terminal hizmete açılıyor.',
                    'en' => 'Modernization works at Adıyaman Airport have concluded and the new terminal is opening for service.',
                    'ku' => 'Xebatên modernizikirinê li Balafirgeha Adiyamanê bi dawî bûn û terminalê nû ji bo karûbarê vedibe.',
                ],
                'content' => [
                    'tr' => '<p>Adıyaman Havalimanı\'nda yaklaşık bir yıldır süren modernizasyon çalışmaları tamamlandı. Yeni terminal binası, artırılmış yolcu kapasitesi ve modern check-in sistemleriyle hizmete hazır hale getirildi.</p><p>DHMİ Genel Müdürlüğü yetkilileri, yeni terminalin yıllık 1,5 milyon yolcu kapasitesine sahip olduğunu ve enerji verimliliği açısından en son teknolojiyle donatıldığını açıkladı.</p>',
                    'en' => '<p>Modernization works that have been ongoing for about a year at Adıyaman Airport have been completed. The new terminal building has been made ready for service with increased passenger capacity and modern check-in systems.</p><p>DHMI General Directorate officials announced that the new terminal has an annual capacity of 1.5 million passengers and is equipped with the latest technology in terms of energy efficiency.</p>',
                    'ku' => '<p>Xebatên modernizikirinê ku li Balafirgeha Adiyamanê ji nêzîkê salek ve didome qediya. Avahiya terminalê nû bi kapasîteya rêwiyên zêdekirî û pergalên check-in ên nûjen ji bo karûbarê amade bû.</p>',
                ],
                'is_featured' => true,
                'is_breaking' => true,
                'tags' => ['adiyaman', 'insaat'],
            ],

            // SİYASET
            [
                'category' => 'siyaset',
                'title' => [
                    'tr' => 'Milletvekilleri Adıyaman\'ın Sorunlarını Meclis Gündemine Taşıdı',
                    'en' => 'MPs Bring Adıyaman\'s Problems to the Parliamentary Agenda',
                    'ku' => 'Parlamenteran Pirsgirêkên Adiyamanê Anîn Rojevê Meclîsê',
                ],
                'summary' => [
                    'tr' => 'Adıyaman milletvekilleri, şehrin kronik altyapı sorunlarına ilişkin TBMM\'de önerge verdi.',
                    'en' => 'Adıyaman MPs submitted a parliamentary motion in the TBMM regarding the city\'s chronic infrastructure problems.',
                    'ku' => 'Parlamenterên Adiyamanê li TBMM\'ê pêşniyareke lêkolînê di derbarê pirsgirêkên binesaziya kronîk ên bajêr de pêşkêş kirin.',
                ],
                'content' => [
                    'tr' => '<p>Adıyaman milletvekilleri, şehrin kronik altyapı sorunlarına dikkat çekmek amacıyla TBMM\'ye araştırma önergesi sundu. Önerge kapsamında su arıtma tesisleri, kanalizasyon altyapısı ve ulaşım ağının iyileştirilmesi talep ediliyor.</p><p>Milletvekilleri ayrıca deprem sonrası kalıcı konut projelerinin hızlandırılması gerektiğini vurguladı.</p>',
                    'en' => '<p>Adıyaman MPs submitted a research motion to the TBMM to draw attention to the city\'s chronic infrastructure problems. The motion calls for improvements to water treatment facilities, sewage infrastructure and the transportation network.</p><p>The MPs also emphasized the need to accelerate permanent housing projects following the earthquake.</p>',
                    'ku' => '<p>Parlamenterên Adiyamanê ji bo balkêşkirina li pirsgirêkên binesaziya kronîk ên bajêr, pêşniyareka lêkolînê pêşkêşî TBMM\'ê kirin. Di çarçoveya pêşniyarê de baştirkirina tesîsên paqijkirina avê, binesaziya kanalizasyonê û tora veguhastinê tê xwestin.</p>',
                ],
                'is_featured' => false,
                'is_breaking' => false,
                'tags' => ['adiyaman'],
            ],

            // EKONOMİ
            [
                'category' => 'ekonomi',
                'title' => [
                    'tr' => 'Adıyaman OSB\'ye 12 Yeni Fabrika Yatırımı',
                    'en' => '12 New Factory Investments for Adıyaman OIZ',
                    'ku' => '12 Veberhênana Fabrîkayên Nû ji bo OSB ya Adiyamanê',
                ],
                'summary' => [
                    'tr' => 'Organize Sanayi Bölgesi\'ne yapılacak yeni yatırımlarla 3 bin kişiye istihdam sağlanması hedefleniyor.',
                    'en' => 'With new investments to be made in the Organized Industrial Zone, it is aimed to provide employment for 3,000 people.',
                    'ku' => 'Bi veberhênanên nû yên ku dê li Herêma Pîşesaziya Rêkxistî werin kirin, armanca xebatê ji bo 3 hezar kesan e.',
                ],
                'content' => [
                    'tr' => '<p>Adıyaman Organize Sanayi Bölgesi\'nde 12 yeni fabrika yatırımı için protokol imzalandı. Gıda, tekstil ve makine sektörlerinden firmalar, toplam 2,1 milyar TL yatırım yapacak.</p><p>OSB Müdürlüğü, yatırımların 2026 yılı sonuna kadar tamamlanmasının planlandığını ve bölgeye yaklaşık 3 bin yeni istihdam sağlanacağını bildirdi.</p>',
                    'en' => '<p>A protocol was signed for 12 new factory investments in the Adıyaman Organized Industrial Zone. Companies from the food, textile and machinery sectors will make a total investment of 2.1 billion TL.</p><p>The OIZ Directorate reported that the investments are planned to be completed by the end of 2026 and will provide approximately 3,000 new jobs to the region.</p>',
                    'ku' => '<p>Ji bo 12 veberhênana fabrîkayên nû li Herêma Pîşesaziya Rêkxistî ya Adiyamanê protokol hat îmzekirin. Pîreyên ji sektorên xwarin, tekstîl û makîneyan dê bi giştî 2,1 milyar TL veberhênanê bikin.</p>',
                ],
                'is_featured' => true,
                'is_breaking' => false,
                'tags' => ['adiyaman', 'ekonomi', 'insaat'],
            ],
            [
                'category' => 'ekonomi',
                'title' => [
                    'tr' => 'Adıyaman\'da Tarımsal Üretim Değerleri Açıklandı',
                    'en' => 'Agricultural Production Figures Announced in Adıyaman',
                    'ku' => 'Nirxên Hilberîna Çandiniyê li Adiyamanê Hatin Ragihandin',
                ],
                'summary' => [
                    'tr' => 'İl Tarım Müdürlüğü, 2025 yılı tarımsal üretim verilerini paylaştı. Buğday ve pamuk üretiminde artış kaydedildi.',
                    'en' => 'The Provincial Agriculture Directorate shared the agricultural production data for 2025. Increases were recorded in wheat and cotton production.',
                    'ku' => 'Rêveberiya Çandiniya Parêzgehê daneyên hilberîna çandiniyê ya sala 2025 parve kir. Di hilberîna genim û pembûyê de zêdebûn hat tomarkirin.',
                ],
                'content' => [
                    'tr' => '<p>Adıyaman İl Tarım ve Orman Müdürlüğü, geçtiğimiz yılın tarımsal üretim istatistiklerini açıkladı. Verilere göre buğday üretiminde %12, pamuk üretiminde ise %8 artış sağlandı.</p><p>Müdür, Adıyaman\'ın özellikle antep fıstığı ve zeytin üretiminde Güneydoğu Anadolu Bölgesi\'nde önemli bir konuma sahip olduğunu vurguladı.</p>',
                    'en' => '<p>Adıyaman Provincial Agriculture and Forestry Directorate announced the agricultural production statistics of the previous year. According to the data, a 12% increase was achieved in wheat production and 8% in cotton production.</p><p>The Director emphasized that Adıyaman holds an important position in the Southeastern Anatolia Region, particularly in pistachio and olive production.</p>',
                    'ku' => '<p>Rêveberiya Çandinî û Daristaniyê ya Parêzgeha Adiyamanê amarên hilberîna çandiniya sala borî ragihand. Li gor daneyan, di hilberîna genim de %12 û di hilberîna pembûyê de %8 zêdebûn hat bidestxistin.</p>',
                ],
                'is_featured' => false,
                'is_breaking' => false,
                'tags' => ['adiyaman', 'tarim', 'ekonomi'],
            ],

            // SPOR
            [
                'category' => 'spor',
                'title' => [
                    'tr' => 'Adıyaman FK Deplasmanda 3 Puanı Aldı',
                    'en' => 'Adıyaman FK Takes 3 Points Away from Home',
                    'ku' => 'Adiyaman FK Li Derve 3 Xal Girt',
                ],
                'summary' => [
                    'tr' => 'Adıyaman 1954 Spor, deplasmanda oynadığı maçta rakibini 2-1 yenerek play-off umutlarını sürdürdü.',
                    'en' => 'Adıyaman 1954 Spor kept their play-off hopes alive by defeating their opponent 2-1 in an away match.',
                    'ku' => 'Adiyaman 1954 Sport, di maçê li derve de dijberê xwe 2-1 şikand û hêviyên play-off berdewam kirin.',
                ],
                'content' => [
                    'tr' => '<p>TFF 2. Lig\'de mücadele eden Adıyaman 1954 Spor, deplasmanda karşılaştığı rakibini 2-1 mağlup etti. İlk yarıda geriye düşen takım, ikinci yarıda Mehmet Yılmaz ve Ahmet Demir\'in golleriyle maçı tersine çevirdi.</p><p>Teknik direktör maç sonrası yaptığı açıklamada, "Oyuncularım ikinci yarıda büyük bir karakter ortaya koydu. Play-off hedefimiz devam ediyor" dedi.</p>',
                    'en' => '<p>Adıyaman 1954 Spor, competing in TFF 2nd League, defeated their away opponent 2-1. The team, who fell behind in the first half, turned the match around with goals from Mehmet Yılmaz and Ahmet Demir in the second half.</p><p>The head coach stated after the match, "My players showed great character in the second half. Our play-off goal continues."</p>',
                    'ku' => '<p>Adiyaman 1954 Sport ku li TFF Lîga 2. pêşbirk dike, dijberê xwe yê li derve 2-1 têk bir. Tîmê ku di nîvê yekem de paşketibû, di nîvê duyemîn de bi golên Mehmet Yılmaz û Ahmet Demir re maç vegerand.</p>',
                ],
                'is_featured' => false,
                'is_breaking' => false,
                'tags' => ['adiyaman', 'spor'],
            ],
            [
                'category' => 'spor',
                'title' => [
                    'tr' => 'Adıyaman\'da Gençlik Spor Festivali Düzenleniyor',
                    'en' => 'Youth Sports Festival Being Organized in Adıyaman',
                    'ku' => 'Li Adiyamanê Festîvala Werzîşa Ciwanên Tê Rêxistinkirin',
                ],
                'summary' => [
                    'tr' => 'Gençlik ve Spor İl Müdürlüğü, 15-25 yaş arası gençlere yönelik spor festivali organize ediyor.',
                    'en' => 'The Provincial Youth and Sports Directorate is organizing a sports festival for young people aged 15-25.',
                    'ku' => 'Rêveberiya Parêzgehê ya Ciwanan û Werzîşê festîvalek werzîşê ji bo ciwanên di navbera 15-25 salî de rêxistin dike.',
                ],
                'content' => [
                    'tr' => '<p>Adıyaman Gençlik ve Spor İl Müdürlüğü tarafından düzenlenen "Adıyaman Gençlik Spor Festivali" 15-17 Mart tarihleri arasında gerçekleştirilecek. Festival kapsamında atletizm, yüzme, basketbol ve voleybol turnuvaları yer alacak.</p><p>Festivale 30\'dan fazla okuldan yaklaşık 500 sporcu katılacak.</p>',
                    'en' => '<p>The "Adıyaman Youth Sports Festival" organized by the Adıyaman Provincial Youth and Sports Directorate will be held between March 15-17. Athletics, swimming, basketball and volleyball tournaments will take place within the scope of the festival.</p><p>Approximately 500 athletes from more than 30 schools will participate in the festival.</p>',
                    'ku' => '<p>"Festîvala Werzîşa Ciwanên Adiyamanê" ya ku ji aliyê Rêveberiya Ciwanan û Werzîşa Parêzgehê ve tê rêxistinkirin dê di navbera 15-17 Adar de were lidarxistin. Di çarçoveya festîvalê de pêşbirka atletîzm, avjeniyê, basketbolê û volebolê dê cih bigire.</p>',
                ],
                'is_featured' => false,
                'is_breaking' => false,
                'tags' => ['adiyaman', 'spor', 'genclik'],
            ],

            // EĞİTİM
            [
                'category' => 'egitim',
                'title' => [
                    'tr' => 'Adıyaman Üniversitesi\'ne 4 Yeni Bölüm Açıldı',
                    'en' => '4 New Departments Opened at Adıyaman University',
                    'ku' => '4 Beşên Nû li Zanîngeha Adiyamanê Hatin Vekirin',
                ],
                'summary' => [
                    'tr' => 'YÖK onayıyla Adıyaman Üniversitesi bünyesinde yazılım mühendisliği dahil 4 yeni bölüm eğitime başlıyor.',
                    'en' => 'With the approval of YÖK, 4 new departments including software engineering are starting education within Adıyaman University.',
                    'ku' => 'Bi pejirandina YÖK\'ê, 4 beşên nû di nav Zanîngeha Adiyamanê de, tevî endezyariya nermalava, dest bi perwerdeyê dikin.',
                ],
                'content' => [
                    'tr' => '<p>Adıyaman Üniversitesi, Yükseköğretim Kurulu\'nun onayıyla 4 yeni bölüm açtığını duyurdu. Yazılım Mühendisliği, Gastronomi ve Mutfak Sanatları, Fizyoterapi ve Rehabilitasyon ile Lojistik Yönetimi bölümleri 2026-2027 eğitim öğretim yılında öğrenci almaya başlayacak.</p><p>Rektör, yeni bölümlerin bölgenin ihtiyaçları doğrultusunda belirlendiğini ve istihdam odaklı eğitim verileceğini belirtti.</p>',
                    'en' => '<p>Adıyaman University announced the opening of 4 new departments with the approval of the Higher Education Council. The departments of Software Engineering, Gastronomy and Culinary Arts, Physiotherapy and Rehabilitation, and Logistics Management will start accepting students in the 2026-2027 academic year.</p><p>The Rector stated that the new departments were determined in line with the needs of the region and that employment-oriented education will be provided.</p>',
                    'ku' => '<p>Zanîngeha Adiyamanê bi pejirandina Encumena Perwerdeya Bilind vekrina 4 beşên nû ragihand. Beşên Endezyariya Nermalava, Gastronomî û Hunerên Aşpêjiyê, Fîzyoterapî û Rehabilîtasyon û Rêveberiya Lojîstîkê dê di sala xwendinê ya 2026-2027 de dest bi qebûla xwendekaran bikin.</p>',
                ],
                'is_featured' => true,
                'is_breaking' => false,
                'tags' => ['adiyaman', 'egitim', 'genclik'],
            ],

            // SAĞLIK
            [
                'category' => 'saglik',
                'title' => [
                    'tr' => 'Adıyaman Devlet Hastanesi\'ne Yeni MR Cihazı',
                    'en' => 'New MRI Machine for Adıyaman State Hospital',
                    'ku' => 'Cîhazekê MR yê Nû ji bo Nexweşxaneya Dewletê ya Adiyamanê',
                ],
                'summary' => [
                    'tr' => 'Sağlık Bakanlığı\'nın desteğiyle Adıyaman Devlet Hastanesi\'ne son teknoloji MR cihazı kuruldu.',
                    'en' => 'A state-of-the-art MRI machine was installed at Adıyaman State Hospital with the support of the Ministry of Health.',
                    'ku' => 'Bi piştgiriya Wezareta Tenduristiyê, cîhazekê MR yê teknolojiya dawî li Nexweşxaneya Dewletê ya Adiyamanê hate sazxistin.',
                ],
                'content' => [
                    'tr' => '<p>Adıyaman Eğitim ve Araştırma Hastanesi\'ne Sağlık Bakanlığı tarafından tahsis edilen son teknoloji 3 Tesla MR cihazı kurulumunu tamamladı. Cihaz sayesinde hastaların ileri görüntüleme için il dışına sevk edilme ihtiyacı azalacak.</p><p>Hastane başhekimi, yeni cihazın günlük 40 hasta kapasitesiyle hizmet vereceğini ve randevu sürecinin dijitalleştirildiğini açıkladı.</p>',
                    'en' => '<p>The state-of-the-art 3 Tesla MRI machine allocated by the Ministry of Health to Adıyaman Training and Research Hospital has completed its installation. Thanks to the device, the need for patients to be transferred outside the province for advanced imaging will decrease.</p><p>The Chief Physician announced that the new device will serve with a daily capacity of 40 patients and that the appointment process has been digitalized.</p>',
                    'ku' => '<p>Cîhazê MR yê 3 Tesla yê teknolojiya dawî yê ku ji aliyê Wezareta Tenduristiyê ve ji bo Nexweşxaneya Perwerde û Lêkolînê ya Adiyamanê hatibû veqetandin, sazkirina xwe temam kir. Bi saya cîhazê, hewcedariya nexweşan ya ji bo teswîra pêşkeftî li derveyî parêzgehê bişînin dê kêm bibe.</p>',
                ],
                'is_featured' => false,
                'is_breaking' => false,
                'tags' => ['adiyaman', 'saglik'],
            ],

            // KÜLTÜR-SANAT
            [
                'category' => 'kultur-sanat',
                'title' => [
                    'tr' => 'Nemrut Dağı Turizm Sezonu Açıldı',
                    'en' => 'Nemrut Mountain Tourism Season Opens',
                    'ku' => 'Demsala Turîzmê ya Çiyayê Nemrûdê Vebû',
                ],
                'summary' => [
                    'tr' => 'UNESCO Dünya Mirası Listesi\'ndeki Nemrut Dağı\'na bu yıl 500 bin ziyaretçi bekleniyor.',
                    'en' => 'Nemrut Mountain, on the UNESCO World Heritage List, is expected to receive 500,000 visitors this year.',
                    'ku' => 'Li Çiyayê Nemrûdê yê di Lîsteya Mîrateya Cîhanê ya UNESCO de, vê salê 500 hezar ziyaretvan tên hêvîkirin.',
                ],
                'content' => [
                    'tr' => '<p>Adıyaman\'ın en önemli turizm değeri olan ve UNESCO Dünya Mirası Listesi\'nde yer alan Nemrut Dağı\'nda yeni turizm sezonu açıldı. İl Kültür ve Turizm Müdürlüğü, bu yıl 500 bin ziyaretçi hedeflediklerini açıkladı.</p><p>Nemrut Dağı\'na ulaşım yollarının asfaltlanması, dinlenme alanlarının yenilenmesi ve gece gözlem platformunun eklenmesi gibi iyileştirmeler tamamlandı. Gün doğumu ve gün batımı turları için online rezervasyon sistemi de devreye alındı.</p>',
                    'en' => '<p>A new tourism season has opened at Nemrut Mountain, the most important tourism asset of Adıyaman and on the UNESCO World Heritage List. The Provincial Culture and Tourism Directorate announced that they aim to reach 500,000 visitors this year.</p><p>Improvements such as asphalting the roads to Nemrut Mountain, renewing rest areas and adding a night observation platform have been completed. An online reservation system for sunrise and sunset tours has also been launched.</p>',
                    'ku' => '<p>Li Çiyayê Nemrûdê yê girîngtirîn nirxa turîzmê ya Adiyamanê û di Lîsteya Mîrateya Cîhanê ya UNESCO de, demsaleke turîzmê ya nû vebû. Rêveberiya Çand û Turîzmê ya Parêzgehê ragihand ku vê salê hedef 500 hezar ziyaretvan e.</p>',
                ],
                'is_featured' => true,
                'is_breaking' => true,
                'tags' => ['nemrut', 'turizm', 'kultur', 'adiyaman'],
            ],
            [
                'category' => 'kultur-sanat',
                'title' => [
                    'tr' => 'Adıyaman\'da Uluslararası Kommagene Kültür Festivali',
                    'en' => 'International Commagene Culture Festival in Adıyaman',
                    'ku' => 'Li Adiyamanê Festîvala Çanda Navneteweyî ya Kommagene',
                ],
                'summary' => [
                    'tr' => '3. Uluslararası Kommagene Kültür Festivali, yerli ve yabancı sanatçıların katılımıyla düzenleniyor.',
                    'en' => 'The 3rd International Commagene Culture Festival is being organized with the participation of local and foreign artists.',
                    'ku' => 'Festîvala Çanda Navneteweyî ya Kommagene ya 3emîn, bi beşdariya hunermendên xwecihî û biyanî tê rêxistinkirin.',
                ],
                'content' => [
                    'tr' => '<p>Adıyaman Belediyesi\'nin ev sahipliğinde bu yıl 3. kez düzenlenen Uluslararası Kommagene Kültür Festivali, 20-25 Mart tarihleri arasında gerçekleştirilecek. Festival programında konserler, tiyatro gösterileri, fotoğraf sergileri ve yerel el sanatları atölyeleri yer alıyor.</p><p>Festival koordinatörü, bu yıl 8 farklı ülkeden sanatçıların katılacağını ve etkinliklerin şehrin farklı noktalarına yayılacağını belirtti.</p>',
                    'en' => '<p>The International Commagene Culture Festival, being organized for the 3rd time this year under the auspices of Adıyaman Municipality, will be held between March 20-25. The festival program includes concerts, theater performances, photography exhibitions and local handicraft workshops.</p><p>The festival coordinator stated that artists from 8 different countries will participate this year and that the events will be spread across different points of the city.</p>',
                    'ku' => '<p>Festîvala Çanda Navneteweyî ya Kommagene ya ku ev sal ji aliyê Şaredariya Adiyamanê ve cara 3emîn tê rêxistinkirin dê di navbera 20-25 Adar de were lidarxistin. Di bernameya festîvalê de konsert, temsîlên şanoyê, pêşangehên wêneyê û atolyeyên hunerên destî yên herêmî hene.</p>',
                ],
                'is_featured' => false,
                'is_breaking' => false,
                'tags' => ['adiyaman', 'kultur', 'festival'],
            ],

            // TEKNOLOJİ
            [
                'category' => 'teknoloji',
                'title' => [
                    'tr' => 'Adıyaman\'da Akıllı Şehir Projesi Hayata Geçiriliyor',
                    'en' => 'Smart City Project Being Implemented in Adıyaman',
                    'ku' => 'Li Adiyamanê Projeya Bajara Jîr Tê Cîbicîkirin',
                ],
                'summary' => [
                    'tr' => 'Belediye, akıllı aydınlatma ve trafik yönetim sistemiyle enerji tasarrufu hedefliyor.',
                    'en' => 'The municipality aims for energy savings with smart lighting and traffic management systems.',
                    'ku' => 'Şaredarî bi pergala ronakkirina jîr û rêveberiya seyrûseferê armanca teserrufa enerjiyê dike.',
                ],
                'content' => [
                    'tr' => '<p>Adıyaman Belediyesi, "Akıllı Şehir Adıyaman" projesi kapsamında IoT tabanlı aydınlatma ve trafik yönetim sistemlerini devreye almaya başladı. İlk etapta şehir merkezindeki 50 kavşağa akıllı trafik sinyalizasyonu kurulacak.</p><p>Proje kapsamında ayrıca ücretsiz Wi-Fi noktaları, akıllı otopark sistemleri ve belediye hizmetlerinin dijitalleştirilmesi de planlanıyor.</p>',
                    'en' => '<p>Adıyaman Municipality has begun activating IoT-based lighting and traffic management systems within the scope of the "Smart City Adıyaman" project. In the first phase, smart traffic signaling will be installed at 50 intersections in the city center.</p><p>Free Wi-Fi points, smart parking systems and digitalization of municipal services are also planned within the scope of the project.</p>',
                    'ku' => '<p>Şaredariya Adiyamanê di çarçoveya projeya "Adiyamana Bajara Jîr" de dest bi xebitandina pergalên ronakkirinê û rêveberiya seyrûseferê yên li ser bingeha IoT kir. Di qonaxa yekem de li 50 kavşakên navenda bajêr dê sînyalîzasyona seyrûseferê ya jîr were sazxistin.</p>',
                ],
                'is_featured' => false,
                'is_breaking' => false,
                'tags' => ['adiyaman', 'teknoloji', 'belediye'],
            ],

            // YAŞAM
            [
                'category' => 'yasam',
                'title' => [
                    'tr' => 'Adıyaman\'da Halk Pazarı Yenilendi',
                    'en' => 'Public Market Renovated in Adıyaman',
                    'ku' => 'Li Adiyamanê Bazara Gel Hat Nûkirin',
                ],
                'summary' => [
                    'tr' => 'Şehir merkezindeki halk pazarı, modern altyapıyla yeniden düzenlenerek hizmete açıldı.',
                    'en' => 'The public market in the city center has been reorganized with modern infrastructure and opened for service.',
                    'ku' => 'Bazara gelê ya di navenda bajêr de bi binesaziya nûjen hat ji nû ve rêxistinkirin û ji bo karûbarê hat vekirin.',
                ],
                'content' => [
                    'tr' => '<p>Adıyaman Belediyesi\'nin gerçekleştirdiği yenileme projesiyle şehir merkezindeki halk pazarı modern bir görünüme kavuştu. Yeni düzenlemede kapalı pazar alanı, ücretsiz otopark, engelli erişim rampası ve hijyen standartlarına uygun tezgahlar yer alıyor.</p><p>Esnaf temsilcileri, yeni pazarın hem alışveriş yapanlar hem de satıcılar için çok daha konforlu olduğunu belirtti.</p>',
                    'en' => '<p>The public market in the city center has acquired a modern look with the renovation project carried out by Adıyaman Municipality. The new arrangement features a covered market area, free parking, a disabled access ramp, and stands compliant with hygiene standards.</p><p>Merchant representatives stated that the new market is much more comfortable for both shoppers and sellers.</p>',
                    'ku' => '<p>Bi projeya nûkirinê ya ku ji aliyê Şaredariya Adiyamanê ve hate pêkanîn, bazara gelê ya di navenda bajêr de xwediyê xuyangeke nûjen bû. Di rêzikandina nû de qadeke bazarê ya girtî, parkingê belaş, rampaya gihiştina astengdaran û tezgehên li gorî standartên paqijiyê cih digirin.</p>',
                ],
                'is_featured' => false,
                'is_breaking' => false,
                'tags' => ['adiyaman', 'belediye'],
            ],

            // ASAYİŞ
            [
                'category' => 'asayis',
                'title' => [
                    'tr' => 'Adıyaman\'da Trafik Denetimleri Sıkılaştırıldı',
                    'en' => 'Traffic Controls Tightened in Adıyaman',
                    'ku' => 'Li Adiyamanê Kontrolên Seyrûseferê Hatin Tûjkirin',
                ],
                'summary' => [
                    'tr' => 'Emniyet müdürlüğü, artan trafik yoğunluğuna karşı denetim noktalarını artırdı.',
                    'en' => 'The police directorate increased control points against increased traffic density.',
                    'ku' => 'Rêveberiya ewlehiyê li hember zêdebûna trafîkê xalên kontrolê zêde kir.',
                ],
                'content' => [
                    'tr' => '<p>Adıyaman İl Emniyet Müdürlüğü Trafik Denetleme Şube Müdürlüğü, bahar döneminde artan araç trafiğine karşı denetim faaliyetlerini yoğunlaştırdı. Şehrin giriş-çıkış noktaları ve ana arterlerde oluşturulan kontrol noktalarında hız, alkol ve emniyet kemeri denetimleri yapılıyor.</p><p>Son bir haftada 2.500 araç kontrol edilirken, 87 sürücüye çeşitli ihlallerden ceza kesildi.</p>',
                    'en' => '<p>Adıyaman Provincial Police Directorate Traffic Supervision Branch has intensified inspection activities against increased vehicle traffic in spring. Speed, alcohol and seatbelt inspections are being carried out at control points established at the city\'s entry and exit points and main arteries.</p><p>While 2,500 vehicles were inspected in the past week, 87 drivers were fined for various violations.</p>',
                    'ku' => '<p>Şubeya Rêveberiya Kontrola Seyrûseferê ya Rêveberiya Ewlehiya Parêzgeha Adiyamanê li hember zêdebûna seyrûseferê di demsala biharê de çalakiyên kontrolê zêde kir. Li xalên kontrolê yên ku li ser xalên têketin û derketina bajêr û arterên sereke hatine danîn, kontrolên leza, alkol û kemberê ewlehiyê têne kirin.</p>',
                ],
                'is_featured' => false,
                'is_breaking' => false,
                'tags' => ['adiyaman'],
            ],
        ];

        foreach ($articles as $i => $data) {
            $categorySlug = $data['category'];
            $category = $categories->get($categorySlug);

            if (!$category) continue;

            $article = NewsArticle::firstOrCreate(
                ['slug' => Str::slug($data['title']['tr'])],
                [
                    'title'       => $data['title'],
                    'summary'     => $data['summary'],
                    'content'     => $data['content'],
                    'slug'        => Str::slug($data['title']['tr']),
                    'source'      => 'manuel',
                    'author_id'   => $admin?->id,
                    'category_id' => $category->id,
                    'city_code'   => 2,
                    'status'      => 'published',
                    'is_breaking' => $data['is_breaking'],
                    'is_featured' => $data['is_featured'],
                    'view_count'  => rand(50, 2500),
                    'published_at'=> now()->subHours(rand(1, 72)),
                ]
            );

            $tagSlugs = $data['tags'] ?? [];
            $tagIds = Tag::whereIn('slug', $tagSlugs)->pluck('id');
            $article->tags()->syncWithoutDetaching($tagIds);
        }
    }

    private function seedAdvertisements(): void
    {
        $ads = [
            [
                'name' => 'Header Banner — Adıyaman Belediyesi',
                'position' => 'header',
                'type' => 'banner',
                'link_url' => 'https://adiyaman.bel.tr',
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'name' => 'Sidebar Reklam 1',
                'position' => 'sidebar',
                'type' => 'banner',
                'link_url' => '#',
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'name' => 'Sidebar Reklam 2 — Google AdSense',
                'position' => 'sidebar',
                'type' => 'adsense',
                'adsense_slot' => 'ca-pub-XXXXXXX/YYYYYY',
                'is_active' => false,
                'sort_order' => 2,
            ],
            [
                'name' => 'Haber İçi Reklam',
                'position' => 'inline',
                'type' => 'adsense',
                'adsense_slot' => 'ca-pub-XXXXXXX/ZZZZZZ',
                'is_active' => false,
                'sort_order' => 1,
            ],
        ];

        foreach ($ads as $ad) {
            Advertisement::firstOrCreate(
                ['name' => $ad['name']],
                $ad
            );
        }
    }

    private function seedPages(): void
    {
        $pages = [
            [
                'title' => [
                    'tr' => 'Hakkımızda',
                    'en' => 'About Us',
                    'ku' => 'Derbarê Me',
                ],
                'slug' => 'hakkimizda',
                'content' => [
                    'tr' => '<h2>Adıyaman Dijital Haber</h2><p>Adıyaman Dijital Haber, Adıyaman ve çevresindeki gelişmeleri güncel, tarafsız ve hızlı bir şekilde okuyucularına ulaştırmayı hedefleyen dijital haber platformudur.</p><p>IHA (İhlas Haber Ajansı) iş birliğiyle ulusal haberleri de takipçilerine sunan platformumuz, yerel gazeteciliği dijital çağa taşıma vizyonuyla hareket etmektedir.</p><p>İletişim: info@adiyamandijitalhaber.com.tr</p>',
                    'en' => '<h2>Adıyaman Digital News</h2><p>Adıyaman Digital News is a digital news platform that aims to deliver developments in and around Adıyaman to its readers in a current, impartial and fast manner.</p><p>Our platform, which also presents national news to its followers through collaboration with IHA (İhlas News Agency), acts with the vision of bringing local journalism to the digital age.</p><p>Contact: info@adiyamandijitalhaber.com.tr</p>',
                    'ku' => '<h2>Adiyaman Nûçeyên Dîjîtal</h2><p>Adiyaman Nûçeyên Dîjîtal platformeke nûçeyên dîjîtal e ku armanca xwe ya gihiştina pêşketinan li Adiyaman û derdora wê bi rêyeke nûjen, bêalî û bilez bo xwendevanan dike.</p><p>Platforma me, ku di hevkariya bi IHA re nûçeyên neteweyî jî pêşkêşî şagirtên xwe dike, bi vîzyona anîna rojnamegeriya herêmî bo serdema dîjîtal tevdigere.</p>',
                ],
                'is_published' => true,
                'sort_order' => 1,
            ],
            [
                'title' => [
                    'tr' => 'İletişim',
                    'en' => 'Contact',
                    'ku' => 'Pêwendî',
                ],
                'slug' => 'iletisim',
                'content' => [
                    'tr' => '<h2>Bize Ulaşın</h2><p><strong>Adres:</strong> Adıyaman Merkez, Türkiye</p><p><strong>Telefon:</strong> Admin panelden güncellenecektir</p><p><strong>E-posta:</strong> info@adiyamandijitalhaber.com.tr</p><p><strong>Çalışma Saatleri:</strong> Pazartesi - Cumartesi, 09:00 - 18:00</p>',
                    'en' => '<h2>Get in Touch</h2><p><strong>Address:</strong> Adıyaman Central, Turkey</p><p><strong>Phone:</strong> Admin panelden güncellenecektir</p><p><strong>Email:</strong> info@adiyamandijitalhaber.com.tr</p><p><strong>Working Hours:</strong> Monday - Saturday, 09:00 - 18:00</p>',
                    'ku' => '<h2>Bi Me re Têkilî Daynin</h2><p><strong>Navnîşan:</strong> Navenda Adiyamanê, Tirkiye</p><p><strong>Telefon:</strong> Admin panelden güncellenecektir</p><p><strong>E-posta:</strong> info@adiyamandijitalhaber.com.tr</p><p><strong>Saetên Xebatê:</strong> Duşem - Şemî, 09:00 - 18:00</p>',
                ],
                'is_published' => true,
                'sort_order' => 2,
            ],
            [
                'title' => [
                    'tr' => 'Gizlilik Politikası',
                    'en' => 'Privacy Policy',
                    'ku' => 'Siyaseta Nepenîtiyê',
                ],
                'slug' => 'gizlilik-politikasi',
                'content' => [
                    'tr' => '<h2>Kisisel Verilerin Korunmasi Politikasi</h2><p>Bu politika, Adıyaman Dijital Haber tarafindan 6698 sayili Kisisel Verilerin Korunmasi Kanunu kapsaminda hazirlanmistir. Veri sorumlusu: Adıyaman Dijital Haber Yayıncılık; Adıyaman V.D. / T.C. No: 18343232668; adres: Adıyaman Merkez, Türkiye.</p><p>Toplanan veriler; ziyaret loglari, iletisim formlari ve teknik cerez kayitlariyla sinirlidir. Veriler, kanuni yukumluluklerin yerine getirilmesi, bilgi guvenliginin saglanmasi ve hizmet kalitesinin iyilestirilmesi amaclariyla islenir.</p><p>KVKK\'nin 11. maddesi kapsamindaki taleplerinizi yazili olarak veya kayitli e-posta yoluyla iletebilirsiniz.</p>',
                    'en' => '<h2>Personal Data Protection Policy</h2><p>This policy has been prepared by Adıyaman Dijital Haber within the scope of the Law on Protection of Personal Data No. 6698 (KVKK). Data controller: Adıyaman Dijital Haber Yayıncılık; Adıyaman Tax Office / T.C. No.: 18343232668; address: Adıyaman Merkez, Türkiye.</p><p>Collected data is limited to visit logs, contact forms and technical cookie records. Data is processed for the purposes of fulfilling legal obligations, ensuring information security and improving service quality.</p><p>You may submit your requests within the scope of Article 11 of KVKK in writing or via registered email.</p>',
                    'ku' => '<h2>Siyaseta Parastina Daneyên Kesane</h2><p>Ev siyaset ji aliyê Adıyaman Dijital Haber ve di çarçoveya Qanûna Parastina Daneyên Kesane ya Hejmara 6698 de hatiye amadekirin. Berpirsiyarê daneyê: Adıyaman Dijital Haber Yayıncılık; Adıyaman V.D. / T.C. No: 18343232668; navnîşan: Adıyaman Merkez, Türkiye.</p><p>Daneyên berhevkirî bi tomarên serdana malperê, formên pêwendiyê û tomarên çerezên teknîkî ve sînordar e.</p>',
                ],
                'is_published' => true,
                'sort_order' => 3,
            ],
            [
                'title' => [
                    'tr' => 'Çerez Politikası',
                    'en' => 'Cookie Policy',
                    'ku' => 'Siyaseta Çerezê',
                ],
                'slug' => 'cerez-politikasi',
                'content' => [
                    'tr' => '<h2>Cerez Politikasi</h2><p>Web sitemizde zorunlu cerezler her zaman aktif olup, analitik ve pazarlama cerezleri acik rizaya tabidir.</p><p>Kullanilan cerez kategorileri: zorunlu, analitik, pazarlama. Tercihlerinizi cerez panelinden degistirebilir veya geri cekebilirsiniz.</p><p>Cerez tercihleri 365 gun saklanir ve sure sonunda yeniden onay istenir.</p>',
                    'en' => '<h2>Cookie Policy</h2><p>On our website, essential cookies are always active, while analytics and marketing cookies are subject to explicit consent.</p><p>Cookie categories used: essential, analytics, marketing. You can change or withdraw your preferences from the cookie panel.</p><p>Cookie preferences are stored for 365 days and re-consent is requested at the end of the period.</p>',
                    'ku' => '<h2>Siyaseta Çerezê</h2><p>Li malperê me, çerezên pêwîst her dem çalak in, lê çerezên analîtîk û bazarkirinê li gorî razîbûna eşkere ne.</p><p>Kategoriyên çereza bikaranî: pêwîst, analîtîk, bazarkirin. Hûn dikarin tercîhên xwe ji panela çerezê biguhêzin an vekişin.</p>',
                ],
                'is_published' => true,
                'sort_order' => 4,
            ],
            [
                'title' => [
                    'tr' => 'KVKK Aydinlatma Metni',
                    'en' => 'KVKK Disclosure Text',
                    'ku' => 'Metna Ronîkirina KVKK',
                ],
                'slug' => 'kvkk-aydinlatma',
                'content' => [
                    'tr' => '<h2>KVKK Aydinlatma Metni</h2><p>Adıyaman Dijital Haber, veri sorumlusu sifatiyla Adıyaman V.D. / T.C. No: 18343232668 ile tescilli olup Adıyaman Merkez, Türkiye adresinde faaliyet gostermektedir. Iletisim kisisi: Adıyaman Dijital Haber Yayıncılık.</p><p>Kisisel verileriniz; haber bulteni kaydi, iletisim formu, teknik guvenlik kayitlari ve yasal yukumlulukler kapsaminda otomatik veya kismen otomatik yollarla islenebilir.</p><p>Isleme amaclari: hukuki yukumluluklerin yerine getirilmesi, bilgi guvenligi sureclerinin yurutulmesi, talep ve sikayet yonetimi, iletisim faaliyetleri.</p><p>Basvuru haklariniz: verinin islenip islenmedigini ogrenme, duzeltme, silme, aktarma ve itiraz talepleri. Basvurular KVKK\'nin 13. maddesine uygun olarak degerlendirilir.</p>',
                    'en' => '<h2>KVKK Disclosure Text</h2><p>Adıyaman Dijital Haber operates as data controller (Adıyaman Tax Office / T.C. No.: 18343232668) at Adıyaman Merkez, Türkiye. Contact person: Adıyaman Dijital Haber Yayıncılık.</p><p>Your personal data may be processed automatically or semi-automatically within the scope of newsletter registration, contact form, technical security records and legal obligations.</p><p>Processing purposes: fulfillment of legal obligations, execution of information security processes, request and complaint management, communication activities.</p><p>Your application rights: learning whether data is processed, correction, deletion, transfer and objection requests. Applications are evaluated in accordance with Article 13 of KVKK.</p>',
                    'ku' => '<h2>Metna Ronîkirina KVKK</h2><p>Adıyaman Dijital Haber weke berpirsiyarê daneyê (Adıyaman V.D. / T.C. No: 18343232668) li Adıyaman Merkez, Türkiye kar dike. Kesê pêwendiyê: Adıyaman Dijital Haber Yayıncılık.</p><p>Daneyên kesane yên we dibe ku di çarçoveya tomarkirina bültenê, forma pêwendiyê, tomarên ewlehiya teknîkî û peywirên yasayî de bi rêyên otomatîk an nîv-otomatîk werin karkirin.</p>',
                ],
                'is_published' => true,
                'sort_order' => 5,
            ],
        ];

        foreach ($pages as $page) {
            Page::firstOrCreate(
                ['slug' => $page['slug']],
                $page
            );
        }
    }
}
