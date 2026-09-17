<?php
namespace App\Http\Controllers;
use App\Models\Accreditation;
use App\Models\College;
use App\Models\Program;
use App\Models\Ranking;
use App\Models\RankingBody;
use App\Models\RankingBreakdown;
class DashboardController extends Controller {
 public function index(){
  $best=Ranking::join('ranking_bodies as rb','rb.id','=','rankings.ranking_body_id')->whereNotNull('rankings.rank_value')->orderByDesc('rankings.year')->orderBy('rankings.rank_value')->select('rankings.global_rank','rb.name as body_name','rankings.year')->first();
    $bestPrevious = Ranking::join('ranking_bodies as rb','rb.id','=','rankings.ranking_body_id')->whereNotNull('rankings.rank_value')->when($best, fn($query) => $query->where('rankings.year', '<', $best->year))->orderByDesc('rankings.year')->orderBy('rankings.rank_value')->value('rankings.rank_value');
    $bestRankValue = $best?->global_rank !== null ? Ranking::join('ranking_bodies as rb','rb.id','=','rankings.ranking_body_id')->where('rankings.global_rank',$best->global_rank)->where('rankings.year',$best->year)->value('rankings.rank_value') : null;
    $phRank=Ranking::whereNotNull('ph_rank')->orderByDesc('year')->first(['ph_rank','ph_rank_value','year']);$phPrevious=Ranking::whereNotNull('ph_rank_value')->when($phRank, fn($query) => $query->where('year', '<', $phRank->year))->orderByDesc('year')->value('ph_rank_value');$bodyCount=RankingBody::count();
  $bodyCards=Ranking::join('ranking_bodies as rb','rb.id','=','rankings.ranking_body_id')->whereRaw('rankings.year=(SELECT MAX(r2.year) FROM rankings r2 WHERE r2.ranking_body_id=rankings.ranking_body_id)')->orderBy('rb.short_name')->orderBy('rankings.category')->get(['rb.short_name','rb.name','rankings.category','rankings.global_rank','rankings.year','rankings.note']);
  $trend=Ranking::join('ranking_bodies as rb','rb.id','=','rankings.ranking_body_id')->where('rb.short_name','QS')->whereNull('rankings.category')->orderBy('rankings.year')->get(['rankings.year','rankings.rank_value','rankings.global_rank']);$trendYears=$trend->pluck('year')->values();$trendRanks=$trend->map(fn($r)=>$r->rank_value===null?null:(int)$r->rank_value)->values();$trendDisplay=$trend->pluck('global_rank')->values();
  $latestCollegeYear=College::max('year');$colleges=College::where('year',$latestCollegeYear)->orderByDesc('contribution_percent')->get(['name','short_code','contribution_percent']);$collegeLabels=$colleges->pluck('name')->values();$collegeValues=$colleges->pluck('contribution_percent')->map(fn($v)=>(float)$v)->values();
  $latestProgramYear=Program::max('year');$programs=Program::join('colleges as c','c.id','=','programs.college_id')->where('programs.year',$latestProgramYear)->orderBy('programs.national_rank')->get(['programs.national_rank','programs.name','c.short_code','programs.score','programs.movement']);
  $groups=RankingBreakdown::join('ranking_bodies as rb','rb.id','=','ranking_breakdowns.ranking_body_id')->whereRaw('ranking_breakdowns.year=(SELECT MAX(b2.year) FROM ranking_breakdowns b2 WHERE b2.ranking_body_id=ranking_breakdowns.ranking_body_id)')->groupBy('rb.id','rb.short_name','rb.name','ranking_breakdowns.year')->orderBy('rb.short_name')->get(['rb.id as body_id','rb.short_name','rb.name','ranking_breakdowns.year']);$breakdownSections=[];foreach($groups as $g){$items=RankingBreakdown::where('ranking_body_id',$g->body_id)->where('year',$g->year)->orderByRaw('(rank_value IS NULL)')->orderBy('rank_value')->orderBy('item_label')->get(['group_label','item_label','rank_display','rank_value']);if($items->count())$breakdownSections[]=['body'=>$g,'items'=>$items];}
  $accreditations=Accreditation::select('program_name','accrediting_body','year')->selectRaw('MAX(assessment_date) AS assessment_date')->selectRaw("MAX(CASE WHEN criterion='Overall Verdict' THEN score END) AS verdict")->selectRaw('AVG(numeric_score) AS avg_score')->groupBy('program_name','accrediting_body','year')->orderByDesc('year')->orderBy('program_name')->get();
    return view('user.dashboard',compact('best','bestPrevious','bestRankValue','phRank','phPrevious','bodyCount','bodyCards','trendYears','trendRanks','trendDisplay','collegeLabels','collegeValues','breakdownSections','programs','accreditations'));
 }
}
