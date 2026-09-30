<?php

namespace App\Domain\Movement;

use DateTimeImmutable;
use DateTimeZone;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Http\UploadedFile;
use RuntimeException;
use ZipArchive;

final class ExcelMovementReader
{
    private const SPREADSHEET_NAMESPACE = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';

    private const RELATIONSHIP_NAMESPACE = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships';

    private const PACKAGE_RELATIONSHIP_NAMESPACE = 'http://schemas.openxmlformats.org/package/2006/relationships';

    private const MAX_XML_BYTES = 104857600;

    /**
     * @return list<array{row: int, values: array<string, string>}>
     */
    public function read(UploadedFile $file): array
    {
        $archive = new ZipArchive;

        if ($archive->open($file->getRealPath()) !== true) {
            throw new RuntimeException('El archivo no es un Excel .xlsx válido.');
        }

        try {
            $workbook = $this->document($this->entry($archive, 'xl/workbook.xml'));
            $workbookPath = new DOMXPath($workbook);
            $workbookPath->registerNamespace('x', self::SPREADSHEET_NAMESPACE);
            $workbookPath->registerNamespace('r', self::RELATIONSHIP_NAMESPACE);

            $sheets = $workbookPath->query('/x:workbook/x:sheets/x:sheet');
            $sheet = null;

            foreach ($sheets ?: [] as $candidate) {
                if ($candidate->getAttribute('state') !== 'hidden') {
                    $sheet = $candidate;
                    break;
                }
            }

            if (! $sheet instanceof DOMElement) {
                throw new RuntimeException('El Excel no contiene una hoja visible con movimientos.');
            }

            $relationshipId = $sheet->getAttributeNS(self::RELATIONSHIP_NAMESPACE, 'id');
            $sheetPath = $this->sheetPath($archive, $relationshipId);
            $sharedStrings = $this->sharedStrings($archive);
            $worksheet = $this->document($this->entry($archive, $sheetPath));
            $worksheetPath = new DOMXPath($worksheet);
            $worksheetPath->registerNamespace('x', self::SPREADSHEET_NAMESPACE);
            $rows = $worksheetPath->query('/x:worksheet/x:sheetData/x:row');

            if ($rows === false || $rows->length === 0) {
                throw new RuntimeException('La hoja de Excel está vacía.');
            }

            $headerRowIndex = null;
            $headersByColumn = [];
            $normalizedHeaders = [];

            foreach ($rows as $rowIndex => $row) {
                if (! $row instanceof DOMElement) {
                    continue;
                }

                $cells = $this->readCells($row, $worksheetPath, $sharedStrings);
                $headers = [];

                foreach ($cells as $column => $cell) {
                    $header = trim($cell['value'], " \t\n\r\0\x0B\xEF\xBB\xBF");

                    if ($header !== '') {
                        $headers[$column] = $header;
                    }
                }

                if ($headers === []) {
                    continue;
                }

                $headersByColumn = $headers;
                $headerRowIndex = $rowIndex;

                foreach ($headersByColumn as $header) {
                    $normalized = $this->normalizeHeader($header);

                    if (isset($normalizedHeaders[$normalized])) {
                        throw new RuntimeException("El encabezado {$header} está repetido en el Excel.");
                    }

                    $normalizedHeaders[$normalized] = true;
                }

                break;
            }

            if ($headerRowIndex === null) {
                throw new RuntimeException('No se encontró la fila de encabezados en el Excel.');
            }

            if (! $this->hasHeader($headersByColumn, ['idmovimiento', 'id_movimiento'])
                || ! $this->hasHeader($headersByColumn, ['idequipo', 'id_equipo'])) {
                throw new RuntimeException('El Excel debe incluir las columnas IdMovimiento e IdEquipo.');
            }

            $date1904 = $this->uses1904DateSystem($workbookPath);
            $records = [];

            foreach ($rows as $rowIndex => $row) {
                if ($rowIndex <= $headerRowIndex || ! $row instanceof DOMElement) {
                    continue;
                }

                $cells = $this->readCells($row, $worksheetPath, $sharedStrings);
                $values = [];

                foreach ($headersByColumn as $column => $header) {
                    $cell = $cells[$column] ?? ['value' => '', 'type' => ''];
                    $values[$header] = $this->normalizeValue($header, $cell, $date1904);
                }

                if (collect($values)->every(static fn (string $value): bool => trim($value) === '')) {
                    continue;
                }

                $records[] = [
                    'row' => (int) $row->getAttribute('r'),
                    'values' => $values,
                ];
            }

            return $records;
        } finally {
            $archive->close();
        }
    }

    private function entry(ZipArchive $archive, string $path): string
    {
        $stat = $archive->statName($path);

        if ($stat === false || $stat['size'] > self::MAX_XML_BYTES) {
            throw new RuntimeException("Falta el componente {$path} o excede el tamaño permitido.");
        }

        $contents = $archive->getFromName($path);

        if ($contents === false) {
            throw new RuntimeException("No se pudo leer el componente {$path} del Excel.");
        }

        return $contents;
    }

    private function document(string $xml): DOMDocument
    {
        $document = new DOMDocument;
        $previousErrorMode = libxml_use_internal_errors(true);

        try {
            if (! $document->loadXML($xml, LIBXML_NONET | LIBXML_COMPACT)) {
                throw new RuntimeException('El Excel contiene XML inválido.');
            }
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previousErrorMode);
        }

        return $document;
    }

    private function sheetPath(ZipArchive $archive, string $relationshipId): string
    {
        if ($relationshipId === '') {
            throw new RuntimeException('No se pudo identificar la hoja de movimientos.');
        }

        $relationships = $this->document($this->entry($archive, 'xl/_rels/workbook.xml.rels'));
        $xpath = new DOMXPath($relationships);
        $xpath->registerNamespace('p', self::PACKAGE_RELATIONSHIP_NAMESPACE);
        $nodes = $xpath->query('/p:Relationships/p:Relationship');

        foreach ($nodes ?: [] as $relationship) {
            if (! $relationship instanceof DOMElement || $relationship->getAttribute('Id') !== $relationshipId) {
                continue;
            }

            $target = ltrim($relationship->getAttribute('Target'), '/');
            $path = str_starts_with($target, 'xl/') ? $target : 'xl/'.$target;
            $segments = [];

            foreach (explode('/', $path) as $segment) {
                if ($segment === '' || $segment === '.') {
                    continue;
                }

                if ($segment === '..') {
                    array_pop($segments);

                    continue;
                }

                $segments[] = $segment;
            }

            return implode('/', $segments);
        }

        throw new RuntimeException('No se encontró la relación de la hoja de movimientos en el Excel.');
    }

    /**
     * @return array<int, string>
     */
    private function sharedStrings(ZipArchive $archive): array
    {
        if ($archive->locateName('xl/sharedStrings.xml') === false) {
            return [];
        }

        $document = $this->document($this->entry($archive, 'xl/sharedStrings.xml'));
        $xpath = new DOMXPath($document);
        $xpath->registerNamespace('x', self::SPREADSHEET_NAMESPACE);
        $items = $xpath->query('/x:sst/x:si');
        $strings = [];

        foreach ($items ?: [] as $item) {
            $parts = $xpath->query('.//x:t', $item);
            $value = '';

            foreach ($parts ?: [] as $part) {
                $value .= $part->textContent;
            }

            $strings[] = $value;
        }

        return $strings;
    }

    /**
     * @param  array<int, string>  $sharedStrings
     * @return array<int, array{value: string, type: string}>
     */
    private function readCells(DOMElement $row, DOMXPath $xpath, array $sharedStrings): array
    {
        $cells = [];
        $nodes = $xpath->query('./x:c', $row);

        foreach ($nodes ?: [] as $cell) {
            if (! $cell instanceof DOMElement) {
                continue;
            }

            $reference = $cell->getAttribute('r');
            $column = $this->columnIndex($reference);
            $type = $cell->getAttribute('t');
            $valueNodes = $xpath->query('./x:v', $cell);
            $valueNode = $valueNodes === false ? null : $valueNodes->item(0);

            if ($type === 'inlineStr') {
                $textNodes = $xpath->query('./x:is//x:t', $cell);
                $value = '';

                foreach ($textNodes ?: [] as $textNode) {
                    $value .= $textNode->textContent;
                }
            } else {
                $value = $valueNode?->textContent ?? '';

                if ($type === 's' && $value !== '') {
                    $sharedString = $sharedStrings[(int) $value] ?? null;

                    if ($sharedString === null) {
                        throw new RuntimeException("El Excel referencia una cadena compartida inexistente en {$reference}.");
                    }

                    $value = $sharedString;
                }
            }

            if ($column !== null) {
                $cells[$column] = ['value' => trim($value), 'type' => $type];
            }
        }

        return $cells;
    }

    private function columnIndex(string $reference): ?int
    {
        if (! preg_match('/^([A-Z]+)/i', $reference, $matches)) {
            return null;
        }

        $index = 0;

        foreach (str_split(strtoupper($matches[1])) as $letter) {
            $index = ($index * 26) + (ord($letter) - 64);
        }

        return $index - 1;
    }

    private function normalizeHeader(string $header): string
    {
        return mb_strtolower(trim($header));
    }

    /**
     * @param  array<int, string>  $headers
     * @param  list<string>  $aliases
     */
    private function hasHeader(array $headers, array $aliases): bool
    {
        foreach ($headers as $header) {
            if (in_array($this->normalizeHeader($header), $aliases, true)) {
                return true;
            }
        }

        return false;
    }

    private function uses1904DateSystem(DOMXPath $workbookPath): bool
    {
        $properties = $workbookPath->query('/x:workbook/x:workbookPr')->item(0);
        $date1904 = $properties instanceof DOMElement ? strtolower($properties->getAttribute('date1904')) : '';

        return in_array($date1904, ['1', 'true'], true);
    }

    /**
     * @param  array{value: string, type: string}  $cell
     */
    private function normalizeValue(string $header, array $cell, bool $date1904): string
    {
        if ($this->normalizeHeader($header) !== 'fecha' || ! is_numeric($cell['value']) || in_array($cell['type'], ['s', 'str', 'inlineStr'], true)) {
            return $cell['value'];
        }

        $days = (int) floor((float) $cell['value']);
        $baseDate = new DateTimeImmutable($date1904 ? '1904-01-01' : '1899-12-30', new DateTimeZone('UTC'));

        return $baseDate->modify("+{$days} days")->format('j/n/Y');
    }
}
