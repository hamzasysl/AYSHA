<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <title>{{ $leave->employee->full_name }} - İzin Formu</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: Arial, Helvetica, sans-serif; color: #111; margin: 0; padding: 28px; font-size: 13px; }
        .sheet { max-width: 820px; margin: 0 auto; }
        .head { display: grid; grid-template-columns: 1fr 2fr; gap: 16px; margin-bottom: 14px; }
        .box { border: 1px solid #111; height: 70px; }
        .title { text-align: center; font-weight: bold; font-size: 15px; padding-top: 10px; }
        table { width: 100%; border-collapse: collapse; }
        td { border: 1px solid #111; padding: 9px 10px; vertical-align: middle; }
        td.k { width: 42%; font-weight: bold; text-transform: uppercase; font-size: 12px; }
        td.v { font-size: 14px; }
        .reasons { display: flex; justify-content: space-around; padding: 6px 0; }
        .reasons span { display: inline-flex; align-items: center; gap: 6px; }
        .chk { display: inline-block; width: 13px; height: 13px; border: 1px solid #111; text-align: center; line-height: 12px; font-size: 11px; }
        .sig { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; margin-top: 40px; text-align: center; font-size: 12px; }
        .sig div { border-top: 1px solid #111; padding-top: 6px; }
        .meta { margin-top: 10px; font-size: 11px; color: #555; }
        .toolbar { text-align: right; margin-bottom: 12px; }
        .toolbar button { padding: 8px 14px; border: 1px solid #ccc; background: #fff; border-radius: 6px; cursor: pointer; }
        /* Tarayıcı üst/alt bilgisi (URL, tarih, "Laravel") basılmasın: sayfa marjı 0, boşluk body padding ile */
        @page { size: A4 portrait; margin: 0; }
        @media print { .toolbar { display: none; } body { padding: 22mm 16mm 16mm; } }
    </style>
</head>
<body>
<div class="sheet">
    
    <div class="head">
        <div class="box"></div>
        <div class="box title">TTB GRUP İZİN FORMU<div style="text-align:right;font-weight:normal;font-size:12px;padding:8px 10px 0 0">Tarih: {{ $leave->created_at->format('d.m.Y') }}</div></div>
    </div>
    @php $fmt = fn ($d) => rtrim(rtrim(number_format((float) $d, 1, ',', '.'), '0'), ','); $e = $leave->employee; @endphp
    <table>
        <tr><td class="k">Adı - Soyadı</td><td class="v">{{ $e->full_name }}</td></tr>
        <tr><td class="k">Bölümü ve Görevi</td><td class="v">{{ $e->position }}</td></tr>
        <tr><td class="k">İşe Giriş Tarihi</td><td class="v">{{ $e->hire_date?->format('d.m.Y') ?? '' }}</td></tr>
        <tr><td class="k">İznin Ait Olduğu Yıl</td><td class="v">{{ $leave->leave_year }}</td></tr>
        <tr><td class="k">İzin Süresi (İş Günü)</td><td class="v">{{ $fmt($leave->days) }} iş günü @if ($leave->deduct_annual)<span style="font-size:11px;color:#555">(yıllık izinden düşüldü · kalan {{ $fmt($balance['remaining']) }} gün)</span>@endif</td></tr>
        <tr><td class="k">İzin Nedeni</td><td class="v">
            <div class="reasons">
                @foreach (['annual' => 'Yıllık izin', 'unpaid' => 'Ücretsiz izin', 'marriage' => 'Evlilik', 'birth' => 'Doğum', 'death' => 'Vefat'] as $k => $l)
                    <span><span class="chk">{{ $leave->type === $k ? '✓' : '' }}</span>{{ $l }}</span>
                @endforeach
            </div>
            @if (! in_array($leave->type, ['annual', 'unpaid', 'marriage', 'birth', 'death']))<div style="margin-top:4px;font-size:12px"><span class="chk">✓</span> {{ $leave->type_label }}</div>@endif
        </td></tr>
        <tr><td class="k">İzne Çıkış Tarihi</td><td class="v">{{ $leave->start_date->format('d.m.Y') }}</td></tr>
        <tr><td class="k">İzin Bitiş Tarihi</td><td class="v">{{ $leave->end_date->format('d.m.Y') }}</td></tr>
        <tr><td class="k">İş Başı Tarihi</td><td class="v">{{ $leave->return_date?->format('d.m.Y') ?? '' }}</td></tr>
        <tr><td class="k" style="height:64px">İzni Talep Edenin<br>Adı - Soyadı</td><td class="v">{{ $leave->requested_by ?: $e->full_name }}</td></tr>
    </table>
    @if ($leave->note)<div class="meta">Not: {{ $leave->note }}</div>@endif
    <div class="sig"><div>İzni Talep Eden</div><div>Birim Amiri</div><div>Onaylayan</div></div>
</div>
<script>
    // Sayfa açılınca doğrudan yazdırma penceresi; iptal/bitince sekme kapanır
    window.addEventListener('load', function () { setTimeout(function () { window.print(); }, 150); });
    window.addEventListener('afterprint', function () { window.close(); });
</script>
</body>
</html>
