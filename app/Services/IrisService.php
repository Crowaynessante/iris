<?php
namespace App\Services;

use App\Models\Accreditation;
use App\Models\College;
use App\Models\Program;
use App\Models\Ranking;
use App\Models\RankingBody;
use App\Models\RankingBreakdown;
use Illuminate\Support\Facades\DB;

class IrisService
{
    public static function parseRankToValue($raw): ?int
    {
        $raw = trim((string)$raw); if ($raw === '') return null;
        if (preg_match('/\d+/', str_replace(['–','—'],'-', $raw), $m)) return (int)$m[0];
        return null;
    }
    public static function insertRanking($shortName,$year,$category,$globalRank,$phRank,$note): bool
    {
        $body=RankingBody::where('short_name',trim((string)$shortName))->first(); if(!$body)return false;
        Ranking::create(['ranking_body_id'=>$body->id,'year'=>(int)$year,'category'=>trim((string)($category??''))?:null,'global_rank'=>($globalRank!==''&&$globalRank!==null)?trim((string)$globalRank):null,'rank_value'=>self::parseRankToValue($globalRank),'ph_rank'=>($phRank!==''&&$phRank!==null)?trim((string)$phRank):null,'ph_rank_value'=>self::parseRankToValue($phRank),'note'=>trim((string)($note??''))]); return true;
    }
    public static function insertBreakdown($shortName,$year,$groupLabel,$itemLabel,$rankDisplay,$note): bool
    {
        $body=RankingBody::where('short_name',trim((string)$shortName))->first(); if(!$body || trim((string)$itemLabel)==='')return false;
        RankingBreakdown::create(['ranking_body_id'=>$body->id,'year'=>(int)$year,'group_label'=>trim((string)($groupLabel??''))?:null,'item_label'=>trim((string)$itemLabel),'rank_display'=>($rankDisplay!==''&&$rankDisplay!==null)?trim((string)$rankDisplay):null,'rank_value'=>self::parseRankToValue($rankDisplay),'note'=>trim((string)($note??''))]); return true;
    }
    public static function insertCollege($name,$shortCode,$percent,$year): bool
    { if(trim((string)$name)===''||trim((string)$shortCode)==='')return false; College::create(['name'=>trim((string)$name),'short_code'=>trim((string)$shortCode),'contribution_percent'=>(float)$percent,'year'=>(int)$year]);return true; }
    public static function insertProgram($name,$collegeCode,$natRank,$score,$movement,$year): bool
    { $college=College::where('short_code',trim((string)$collegeCode))->orderByDesc('year')->first();if(!$college)return false;Program::create(['name'=>trim((string)$name),'college_id'=>$college->id,'national_rank'=>(int)$natRank,'score'=>(float)$score,'movement'=>(int)$movement,'year'=>(int)$year]);return true; }
    public static function insertAccreditation($programName,$body,$year,$date,$criterion,$score): bool
    { if(trim((string)$programName)===''||trim((string)$criterion)==='')return false; $score=($score!==''&&$score!==null)?trim((string)$score):null; Accreditation::create(['program_name'=>trim((string)$programName),'accrediting_body'=>trim((string)($body??''))?:'AUN-QA','year'=>(int)$year,'assessment_date'=>trim((string)($date??''))?:null,'criterion'=>trim((string)$criterion),'score'=>$score,'numeric_score'=>($score!==null&&is_numeric($score))?(float)$score:null]);return true; }

    public static function csvRows($path): array
    { $h=fopen($path,'r');if($h===false)throw new \RuntimeException('Could not open the CSV file.');$bom=fread($h,3);if($bom!=="\xEF\xBB\xBF")rewind($h);$header=fgetcsv($h);if($header===false){fclose($h);throw new \RuntimeException('The CSV file appears to be empty.');}$header=array_map(fn($v)=>strtolower(trim((string)$v)),$header);$rows=[];while(($row=fgetcsv($h))!==false){if(count(array_filter($row,fn($c)=>trim((string)$c)!==''))===0)continue;$row=array_pad($row,count($header),null);$rows[]=array_combine($header,array_slice($row,0,count($header)));}fclose($h);return $rows; }
    public static function smartMapCsv($path): ?array
    { $rows=self::csvRows($path);if(!$rows)return ['rankings'=>[],'colleges'=>[],'programs'=>[]];$columns=array_keys($rows[0]);$templates=['rankings'=>['ranking_body_short_name','year','global_rank','ph_rank'],'colleges'=>['name','short_code','contribution_percent','year'],'programs'=>['name','college_short_code','national_rank','score']];$best=null;$score=0;foreach($templates as $type=>$cols){$s=count(array_intersect($cols,$columns));if($s>$score){$score=$s;$best=$type;}}if($best===null||$score<ceil(count($templates[$best])/2))return null;$result=['rankings'=>[],'colleges'=>[],'programs'=>[]];foreach($rows as $r){if($best==='rankings')$result['rankings'][]=['ranking_body_short_name'=>$r['ranking_body_short_name']??'','year'=>$r['year']??null,'global_rank'=>$r['global_rank']??null,'ph_rank'=>$r['ph_rank']??null,'note'=>$r['note']??''];elseif($best==='colleges')$result['colleges'][]=['name'=>$r['name']??'','short_code'=>$r['short_code']??'','contribution_percent'=>$r['contribution_percent']??null,'year'=>$r['year']??null];else $result['programs'][]=['name'=>$r['name']??'','college_short_code'=>$r['college_short_code']??'','national_rank'=>$r['national_rank']??null,'score'=>$r['score']??null,'movement'=>$r['movement']??0,'year'=>$r['year']??null];}return $result; }
    public static function csvText($path): string
    { $rows=self::csvRows($path);if(!$rows)throw new \RuntimeException('The CSV file appears to be empty.');$text=implode(' | ',array_keys($rows[0]))."\n";foreach($rows as $r)$text.=implode(' | ',array_map(fn($v)=>$v??'',$r))."\n";return $text; }
}
