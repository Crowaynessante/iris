<?php
namespace App\Services;
use Illuminate\Support\Facades\Http;
class ClaudeExtractor {
 public const PROMPT=<<<'PROMPT'
You are extracting university ranking, accreditation, and organizational data
from a document (which may be a table, a report, an infographic, or a photo
of a printed page) for a dashboard system.

Return ONLY valid JSON (no markdown fences, no commentary) in exactly this shape:
{
  "rankings": [
    {"ranking_body_short_name": "QS", "year": 2024, "category": "World", "global_rank": "641", "ph_rank": "7", "note": "..."}
  ],
  "ranking_breakdowns": [
    {"ranking_body_short_name": "THE", "year": 2025, "group_label": "SDG", "item_label": "Zero Hunger", "rank_display": "301-400", "note": "..."}
  ],
  "colleges": [
    {"name": "College of Engineering", "short_code": "CEAT", "contribution_percent": 28, "year": 2024}
  ],
  "programs": [
    {"name": "Agricultural Engineering", "college_short_code": "CEAT", "national_rank": 1, "score": 94.2, "movement": 3, "year": 2024}
  ],
  "accreditations": [
    {"program_name": "Doctor of Veterinary Medicine", "accrediting_body": "AUN-QA", "year": 2024, "assessment_date": "July 9-11", "criterion": "Expected Learning Outcomes", "score": "4"}
  ]
}

Rules:
- Known ranking_body_short_name values: QS, THE, CWTS, Webometrics, URAP, SCImago, WURI, AppliedHE, "AD Scientific Index", EduRank. If the document names a different body, still include it with your best-guess short name.
- "global_rank" and "ph_rank" are STRINGS, exactly as published - they may be a plain number ("161"), a band ("801-1000", "1001-1100"), an ordinal ("2nd"), or status text with no number at all ("Reporter Status", "Not listed"). Never convert a band into a single number or invent a number that wasn't printed.
- "category" on a ranking distinguishes multiple lists the same body publishes in one year (e.g. QS "World" vs QS "Asia", THE "Impact" vs THE "World University Rankings"). Omit it if the body only publishes one list.
- Use "rankings" for a single headline rank per body/year/category. Use "ranking_breakdowns" for anything that is a sub-list underneath a headline rank: THE Impact's per-SDG ranks, WURI's top award categories with their own sub-rank, QS Stars' per-category star ratings (put the rating and score together in rank_display, e.g. "5 stars (123/150)"), AppliedHE's regional/national splits, etc. "group_label" names the kind of breakdown (e.g. "SDG", "QS Stars", "WURI Category"); "item_label" is the specific one (e.g. "Zero Hunger", "Teaching").
- Use "accreditations" for program-level accreditation or certification assessments (AUN-QA and similar) - one row per criterion per program per assessment, including a final row with criterion "Overall Verdict" whose score is the text verdict (e.g. "Adequate as Expected") rather than a number.
- If a field is not present in the document, omit it or use null - do not invent numbers or dates.
- If the document contains no data for one of the categories, return an empty array for it.
- "movement" is the change in rank versus the prior period; use 0 if not mentioned.
- Respond with the JSON object and nothing else.
PROMPT;
 public static function checks(): array {return ['api_key'=>['ok'=>(bool)env('CLAUDE_API_KEY'),'label'=>env('CLAUDE_API_KEY')?'CLAUDE_API_KEY is set — Word, PDF, Excel, and image uploads can reach the AI.':'CLAUDE_API_KEY is not set for this PHP process — Word/PDF/Excel/image uploads will fail until it is.'],'curl'=>['ok'=>function_exists('curl_init'),'label'=>function_exists('curl_init')?'cURL extension enabled.':'PHP cURL extension is missing — no file type can reach the AI without it.'],'zip'=>['ok'=>class_exists('ZipArchive'),'label'=>class_exists('ZipArchive')?'ZipArchive available (needed to open .docx/.xlsx files).':'PHP zip extension is missing — Word (.docx) and Excel (.xlsx) files cannot be opened. Enable "extension=zip" in php.ini and restart the server.'],'fileinfo'=>['ok'=>function_exists('finfo_open'),'label'=>function_exists('finfo_open')?'fileinfo extension enabled.':'PHP fileinfo extension is missing — file type detection may be less reliable.']];}
 public static function requirementsOk($checks): bool {foreach($checks as $k=>$c)if($k!=='fileinfo'&&!$c['ok'])return false;return true;}
 public static function extract(array $blocks): array { $key=env('CLAUDE_API_KEY');if(!$key)throw new \RuntimeException("AI extraction is not configured on this server: the CLAUDE_API_KEY environment variable isn't visible to PHP. If you already set it in Windows/System settings, make sure it was set BEFORE Apache/XAMPP started (restart the Apache service), or set it directly for this app instead — see the Setup section on this page.");if(!function_exists('curl_init'))throw new \RuntimeException("The PHP cURL extension is not enabled on this server, so it can't reach the Anthropic API.");$res=Http::timeout(90)->withHeaders(['x-api-key'=>$key,'anthropic-version'=>'2023-06-01'])->post('https://api.anthropic.com/v1/messages',['model'=>'claude-sonnet-4-6','max_tokens'=>2000,'messages'=>[['role'=>'user','content'=>array_merge([['type'=>'text','text'=>self::PROMPT]],$blocks)]]]);if(!$res->successful())throw new \RuntimeException('The Anthropic API rejected the request (HTTP '.$res->status().'): '.($res->json('error.message')??$res->body()));$text=$res->json('content.0.text');if(!$text)throw new \RuntimeException("The API response didn't contain the expected data. Please try again.");$start=strpos($text,'{');$end=strrpos($text,'}');if($start===false||$end===false||$end<$start)throw new \RuntimeException("Could not find structured data in the AI's response. Try again, or try a clearer file.");$parsed=json_decode(substr($text,$start,$end-$start+1),true);if(!is_array($parsed))throw new \RuntimeException("Could not parse the AI's response as valid data. Try again, or try a clearer file.");return ['rankings'=>$parsed['rankings']??[],'ranking_breakdowns'=>$parsed['ranking_breakdowns']??[],'colleges'=>$parsed['colleges']??[],'programs'=>$parsed['programs']??[],'accreditations'=>$parsed['accreditations']??[]]; }
 public static function textBlock($text): array{return ['type'=>'text','text'=>'Document content:\n\n'.$text];}
 public static function pdfBlock($path): array{return ['type'=>'document','source'=>['type'=>'base64','media_type'=>'application/pdf','data'=>base64_encode(file_get_contents($path))]];}
 public static function imageBlock($path,$mime): array{return ['type'=>'image','source'=>['type'=>'base64','media_type'=>$mime,'data'=>base64_encode(file_get_contents($path))]];}
}
