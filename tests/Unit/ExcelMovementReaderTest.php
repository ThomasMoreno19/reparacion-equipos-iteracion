<?php

namespace Tests\Unit;

use App\Domain\Movement\ExcelMovementReader;
use Illuminate\Http\UploadedFile;
use PHPUnit\Framework\TestCase;
use ZipArchive;

class ExcelMovementReaderTest extends TestCase
{
    private ?string $workbookPath = null;

    protected function tearDown(): void
    {
        if ($this->workbookPath !== null && is_file($this->workbookPath)) {
            unlink($this->workbookPath);
        }

        parent::tearDown();
    }

    public function test_it_reads_movement_rows_from_an_xlsx_workbook(): void
    {
        $headers = [
            'IdMovimiento', 'IdEquipo', 'Equipo', 'Marca', 'Modelo', 'Nro Serie o Dominio',
            'Cliente', 'Estado', 'Fecha', 'Observaciones', 'CUIT', 'Password',
        ];
        $values = [
            '26', '0', 'no se', '', '', '', 'si se', 'Ingreso', '8/7/2025', '', '0', '123',
        ];
        $uploadedFile = $this->createWorkbook($headers, $values);

        $rows = (new ExcelMovementReader)->read($uploadedFile);

        $this->assertCount(1, $rows);
        $this->assertSame(2, $rows[0]['row']);
        $this->assertSame('26', $rows[0]['values']['IdMovimiento']);
        $this->assertSame('0', $rows[0]['values']['IdEquipo']);
        $this->assertSame('no se', $rows[0]['values']['Equipo']);
        $this->assertSame('8/7/2025', $rows[0]['values']['Fecha']);
        $this->assertSame('123', $rows[0]['values']['Password']);
    }

    public function test_it_rejects_workbooks_missing_required_movement_headers(): void
    {
        $uploadedFile = $this->createWorkbook(['Equipo', 'Cliente'], ['Router', 'Cliente Uno']);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('IdMovimiento e IdEquipo');

        (new ExcelMovementReader)->read($uploadedFile);
    }

    private function createWorkbook(array $headers, array $values): UploadedFile
    {
        $this->workbookPath = tempnam(sys_get_temp_dir(), 'movement-xlsx-');
        $archive = new ZipArchive;
        $opened = $archive->open($this->workbookPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        $this->assertTrue($opened === true);

        $strings = [];
        $stringIndexes = [];

        foreach (array_merge($headers, $values) as $value) {
            if ($value === '' || is_numeric($value)) {
                continue;
            }

            if (! array_key_exists($value, $stringIndexes)) {
                $stringIndexes[$value] = count($strings);
                $strings[] = $value;
            }
        }

        $sharedStringXml = '<sst xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">';

        foreach ($strings as $string) {
            $sharedStringXml .= '<si><t>'.htmlspecialchars($string, ENT_XML1 | ENT_QUOTES, 'UTF-8').'</t></si>';
        }

        $sharedStringXml .= '</sst>';
        $sheetXml = '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>';
        $sheetXml .= $this->rowXml(1, $headers, $stringIndexes, true);
        $sheetXml .= $this->rowXml(2, $values, $stringIndexes, false);
        $sheetXml .= '</sheetData></worksheet>';

        $archive->addFromString('xl/workbook.xml', '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Hoja1" sheetId="1" r:id="rId1"/></sheets></workbook>');
        $archive->addFromString('xl/_rels/workbook.xml.rels', '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/></Relationships>');
        $archive->addFromString('xl/sharedStrings.xml', $sharedStringXml);
        $archive->addFromString('xl/worksheets/sheet1.xml', $sheetXml);
        $archive->close();

        return new UploadedFile($this->workbookPath, 'Repara.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', UPLOAD_ERR_OK, true);
    }

    private function rowXml(int $rowNumber, array $values, array $stringIndexes, bool $headers): string
    {
        $xml = '<row r="'.$rowNumber.'">';

        foreach ($values as $column => $value) {
            if ($value === '') {
                continue;
            }

            $cellReference = $this->columnName($column + 1).$rowNumber;

            if ($headers || ! is_numeric($value)) {
                $xml .= '<c r="'.$cellReference.'" t="s"><v>'.$stringIndexes[$value].'</v></c>';
            } else {
                $xml .= '<c r="'.$cellReference.'" t="n"><v>'.$value.'</v></c>';
            }
        }

        return $xml.'</row>';
    }

    private function columnName(int $column): string
    {
        $name = '';

        while ($column > 0) {
            $remainder = ($column - 1) % 26;
            $name = chr(65 + $remainder).$name;
            $column = intdiv($column - 1, 26);
        }

        return $name;
    }
}
