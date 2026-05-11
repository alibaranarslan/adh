<h1>ADH Iletisim Formu</h1>

<p><strong>Ad Soyad:</strong> {{ $submission['name'] }}</p>
<p><strong>E-posta:</strong> {{ $submission['email'] }}</p>
<p><strong>Konu:</strong> {{ $submission['subject'] ?: 'Belirtilmedi' }}</p>

<hr>

<p style="white-space: pre-line;">{{ $submission['message'] }}</p>
