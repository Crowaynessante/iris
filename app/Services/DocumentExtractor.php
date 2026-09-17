<?php
namespace App\Services;
use PhpOffice\PhpWord\IOFactory as WordIO;
use PhpOffice\PhpSpreadsheet\IOFactory as SheetIO;
class DocumentExtractor {
 public static function wordElement($e): string { $text=''; if(method_exists($e,'getText')){$t=$e->getText();return (is_array($t)?implode('',$t):$t).' ';} if(method_exists($e,'getRows')){foreach($e->getRows() as $row){$cells=[];foreach($row->getCells() as $cell){$ct='';foreach($cell->getElements() as $ce)$ct.=self::wordElement($ce);$cells[]=trim($ct);} $text.=implode(' | ',$cells)."\n";}return $text;} if(method_exists($e,'getElements')){foreach($e->getElements() as $c)$text.=self::wordElement($c);return $text."\n";}return $text; }
 public static function docx($path): string {$w=WordIO::load($path);$text='';foreach($w->getSections() as $s)foreach($s->getElements() as $e)$text.=self::wordElement($e);$text=trim($text);if($text==='')throw new \RuntimeException('No readable text found in this Word document (it may be scanned images pasted into the doc - try exporting it as a PDF instead, or upload a screenshot as an image).');return $text;}
 public static function spreadsheet($path): string {$s=SheetIO::load($path);$text='';foreach($s->getAllSheets() as $sheet){$rows=$sheet->toArray(null,true,true,false);$has=false;$block='Sheet: '.$sheet->getTitle()."\n";foreach($rows as $row){if(count(array_filter($row,fn($c)=>$c!==null&&$c!==''))===0)continue;$has=true;$block.=implode(' | ',array_map(fn($c)=>$c??'',$row))."\n";}if($has)$text.=$block;}$text=trim($text);if($text==='')throw new \RuntimeException('This spreadsheet appears to be empty, or all its sheets are blank.');return $text;}
}
