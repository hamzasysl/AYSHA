<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Garanti BBVA "TGB Yeni Maaş Dosyası" formatında toplu maaş ödeme Excel'i üretir.
 * Satır yapısı bankanın şablonuyla aynıdır: başlık bloğu (A1:B9), açıklama satırları,
 * 12. satırda sütun başlıkları, 13. satırdan itibaren personel.
 *
 * @param  Collection<int, array{name:string,tc:?string,bank_code:?string,branch_code:?string,account:?string,iban:?string,amount:float}>  $rows
 */
class GarantiPayrollFile
{
    public static function build(Collection $rows, string $description, \DateTimeInterface $paymentDate, string $paymentType = 'M'): Spreadsheet
    {
        $wb = new Spreadsheet;
        $ws = $wb->getActiveSheet();
        $ws->setTitle('TGB Yeni Maaş Dosyası');
        $wb->getDefaultStyle()->getFont()->setName('Arial')->setSize(10);

        $header = [
            ['Kurum Kodu', Setting::get('bank_corp_code', ''), 'Garanti Bankası tarafından verilen kurum kodunuz.'],
            ['Şube Kodu', Setting::get('bank_branch_code', ''), 'Şubenizden öğreniniz'],
            ['Hesap', Setting::get('bank_account', ''), 'Maaş ödemesinde kullanacağınız hesap. 1299998-2 şeklinde kontrol digiti girmeyiniz.'],
            ['Toplam Adet', '=COUNTA(A13:A5000)', 'Toplam maaş adedi. (Giriş yapıldıkça otomatik olarak hesaplanır.)'],
            ['Toplam Tutar', '=SUM(G13:G5000)', 'Toplam ödeme tutarı. (Giriş yapıldıkça otomatik olarak hesaplanır.)'],
            ['Döviz Kodu', 'TL ', 'Döviz kodunu listeden seçiniz.'],
            ['Ödeme Tarihi', $paymentDate->format('dmY'), 'GGAAYYYY formatında. (Örnek: 04032001 giriniz.)'],
            ['Ödeme Tipi', $paymentType, 'Ödeme tiplerini yandaki tabloda görebilirsiniz.'],
            ['Borç İzahat', $description, null],
        ];
        foreach ($header as $i => [$k, $v, $note]) {
            $r = $i + 1;
            $ws->setCellValue("A{$r}", $k);
            if (is_string($v) && str_starts_with($v, '=')) {
                $ws->setCellValue("B{$r}", $v);
            } else {
                $ws->setCellValueExplicit("B{$r}", (string) $v, DataType::TYPE_STRING);
            }
            if ($note) {
                $ws->setCellValue("C{$r}", $note);
            }
            $ws->getStyle("A{$r}")->getFont()->setBold(true);
        }
        $ws->setCellValue('A10', "BİLGİLENDİRME : Dosyanızdaki bilgiler banka sistemine otomatik olarak yüklenecektir. Banka kodu boş veya  62 ise havale, 62'den farklı ise EFT'dir. Kayıtlar içinde EFT varsa ödeme tarihi işgünü olmalıdır.");
        $ws->setCellValue('A11', 'Herhangi bir hataya yol açmamak için dosyanın formatını değiştirmeyiniz, açıklamalara uyunuz. ');

        // Ödeme tipleri referans tablosu (I1:L9) — bankanın şablonundaki gibi
        $types = [['O', 'SOSYAL YARDIM', 'G', 'PROMOSYON'], ['D', 'DÖNER SERMAYE', 'R', 'PRİM ÖDEMESİ'], ['C', 'KOMİSYON', 'S', 'EK DERS ÜCRETİ'], ['F', 'FAZLA MESAİ', 'H', 'HUZUR HAKKI'], ['I', 'İKRAMİYE', 'V', 'ASGARİ GEÇİM İNDİRİMİ'], ['K', 'KIDEM TAZMİNATI', 'Y', 'YOLLUK'], ['M', 'MAAŞ', 'Z', 'DİĞER'], ['N', 'AVANS', 'X', 'KESİNTİ']];
        $ws->setCellValue('I1', 'Ödeme Tipleri');
        $ws->getStyle('I1')->getFont()->setBold(true);
        foreach ($types as $i => $t) {
            $ws->fromArray($t, null, 'I'.($i + 2));
        }

        $cols = ['İsim', 'TCKN (Opsiyonel)', 'Banka Kodu', 'Şube Kodu', 'Hesap', 'IBAN (Boşluksuz 26 Karakter)', 'Tutar', 'Borç İzahat', 'Alacak izahat'];
        $ws->fromArray($cols, null, 'A12');
        $ws->getStyle('A12:I12')->getFont()->setBold(true);
        $ws->getStyle('A12:I12')->getFill()->setFillType('solid')->getStartColor()->setRGB('DFEEFB');

        $r = 13;
        foreach ($rows as $row) {
            $ws->setCellValue("A{$r}", mb_strtoupper($row['name'], 'UTF-8'));
            $ws->setCellValueExplicit("B{$r}", (string) ($row['tc'] ?? ''), DataType::TYPE_STRING);
            $ws->setCellValueExplicit("C{$r}", (string) ($row['bank_code'] ?? '62'), DataType::TYPE_STRING);
            $ws->setCellValueExplicit("D{$r}", (string) ($row['branch_code'] ?? ''), DataType::TYPE_STRING);
            $ws->setCellValueExplicit("E{$r}", (string) ($row['account'] ?? ''), DataType::TYPE_STRING);
            $ws->setCellValueExplicit("F{$r}", (string) ($row['iban'] ?? ''), DataType::TYPE_STRING);
            $ws->setCellValue("G{$r}", round((float) $row['amount'], 2));
            $ws->getStyle("G{$r}")->getNumberFormat()->setFormatCode('#,##0.00');
            $r++;
        }

        if ($r > 13) {
            $ws->getStyle('A12:I'.($r - 1))->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('CBD5E1');
        }
        foreach (['A' => 28, 'B' => 18, 'C' => 11, 'D' => 11, 'E' => 12, 'F' => 32, 'G' => 14, 'H' => 22, 'I' => 22, 'J' => 24, 'K' => 6, 'L' => 24] as $c => $w) {
            $ws->getColumnDimension($c)->setWidth($w);
        }
        $ws->getStyle('G13:G5000')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $ws->freezePane('A13');

        return $wb;
    }

    public static function download(Spreadsheet $wb, string $filename): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        return response()->streamDownload(function () use ($wb) {
            (new Xlsx($wb))->save('php://output');
        }, $filename, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }
}
