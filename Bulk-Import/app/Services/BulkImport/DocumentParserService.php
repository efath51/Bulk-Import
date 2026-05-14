<?php

namespace App\Services\BulkImport;

use App\Models\ParserConfig;
use PhpOffice\PhpWord\IOFactory;
use Smalot\PdfParser\Parser as PdfParser;

class DocumentParserService
{
    public $itemTemplate = [];
    public $fields = [];
    public function getFields(): array
    {
        return $this->itemTemplate;
    }
    public function getItemTemplate(): array
    {
        return $this->fields;
    }


    public function parse(string $filePath, string $extension): array
    {
        $text = $this->extractText($filePath, $extension); 

        
        $blocks = preg_split('/\s*---\s*|\n\s*\n\s*\n/', $text, -1, PREG_SPLIT_NO_EMPTY);
     

        $this->FindFields($blocks[0]);
        $this->parseBlock($blocks[0]);

        $items = [];

        foreach ($blocks as $block) {
            if (trim($block) === '') continue; //if this block is null then move in to next block

            $parsedItems = $this->parseBlock($block);
            $items = array_merge($items, $parsedItems);
        }

       
        return [
            'items'  => $items,     
            'blocks' => $blocks,
        ];
    }


    private function extractText(string $path, string $ext): string
    {
        // PDF 
        if (in_array($ext, ['pdf', 'PDF'])) {
            $parser = new PdfParser();
            $pdf = $parser->parseFile($path);
            return $pdf->getText();
        }

        // DOCX
        if (in_array($ext, ['docx', 'DOCX'])) {
            $phpWord = IOFactory::load($path);
            $text = '';
            foreach ($phpWord->getSections() as $section) {
                foreach ($section->getElements() as $element) {
                    if (method_exists($element, 'getText')) {
                        $text .= $element->getText() . "\n";
                    }
                }
            }
            return $text;
        }

        throw new \Exception('Unsupported file type');
    }


    private function parseBlock(string $block): array
    {

        // $ItemPattern = '/Item\s+\d+/i';
        // $NameToNamePattern = '/Name:.*Slug:.*Name:/s';
        // $FieldsPattern = '/Name:.*?(?=Name:|$)/s';
        // $NewLinePattern = '/\n{2,}/';

        $active  = ParserConfig::where('is_active', true)->first();
        $pattern = $active?->pattern ?? '/\n{2,}/';


        if (preg_match('/Item\s+\d+/i', $block)) {
            preg_match_all('/Item\s+\d+\s+(.*?)(?=Item\s+\d+|$)/s', $block, $matches);
            $entries = $matches[1];


        } elseif ($pattern === '/Name:.*?(?=Name:|$)/s' && preg_match('/Name:.*?(?=Name:|$)/s', $block)) {

            preg_match_all('/Name:\s*(.*?)\s*(?=Name:|$)/s', $block, $matches);
            $entries = array_map(fn($e) => 'Name: ' . $e, $matches[1]);

        } else {
            $entries = preg_split($pattern, trim($block));
        }
        // dd($entries);
        $mappedData = array_map([$this, 'parseEntry'], $entries);
        return array_values(array_filter($mappedData));
    }


    public function FindFields(string $text)
    {
        preg_match_all('/(\w+):/', $text, $matches);
        $detectedFields = array_unique($matches[1]);   // Remove duplicates
        $detectedFields = array_values($detectedFields); // Re-index

        $this->fields = $detectedFields;   // Store raw fields like ['Name', 'Slug', ...]

        $this->itemTemplate = $this->createItemTemplate($detectedFields);
        return $detectedFields;
    }


    public function createItemTemplate(array $fields)
    {
        $item = [];

        foreach ($fields as $field) {
            $key = strtolower(trim($field));
            // Set default values
            switch ($key) {
                case 'status':
                    $item[$key] = 'active';     //default
                    break;

                case 'title':
                    $item[$key] = '';
                    break;

                default:
                    $item['image'] = '';
                    $item[$key] = '';           // Default empty for others
            }
        }
        return $item;
    }


    private function parseEntry(string $entry): ?array
    {
      
        $item = $this->itemTemplate ?? [
            'name' => '',
            'slug' => '',
            'status' => 'active',
            'description' => '',
        ];

        // If we have dynamically detected fields, use them
        if (!empty($this->fields)) {

            $totalFields = count($this->fields);

            for ($i = 0; $i < $totalFields; $i++) {
                $currentField = $this->fields[$i];
                $key = strtolower(trim($currentField));  

                // For the last field, match until the end
                if ($i === $totalFields - 1) {
                    $pattern = '/' . $currentField . ':\s*(.*)$/s';
                }
                // For other fields, match until the next field
                else {
                    $nextField = $this->fields[$i + 1];
                    $pattern = '/' . $currentField . ':\s*(.*?)\s+' . $nextField . ':/s';
                }

                if (preg_match($pattern, $entry, $m)) {
                    $value = trim($m[1]);
                    $item[$key] = $value;
                }
            }
        }
     
        else {
            $item = [
                'name' => '',
                'slug' => '',
                'status' => 'active',
                'description' => '',
            ];

            if (preg_match('/Name:\s*(.*?)\s+Slug:/s', $entry, $m)) {
                $item['name'] = trim($m[1]);
            }

            if (preg_match('/Slug:\s*(.*?)\s+Status:/s', $entry, $m)) {
                $item['slug'] = trim($m[1]);
            }

            if (preg_match('/Status:\s*(.*?)\s+Description:/s', $entry, $m)) {
                $item['status'] = trim($m[1]);
            }

            if (preg_match('/Description:\s*(.*)/s', $entry, $m)) {
                $item['description'] = trim($m[1]);
            }

            if (!empty($item['name'])) {
                $item[] = $item;
            }
            return !empty($item['name']) ? $item : null;
        }
        return !empty($item['name']) ? $item : null;
    }
}
