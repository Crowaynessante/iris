<?php
require_once __DIR__.'/../includes/functions.php'; require_auth();
$pdo=db();
$q=$pdo->query("SELECT r.global_rank, rb.name AS body_name, r.year FROM rankings r JOIN ranking_bodies rb ON rb.id=r.ranking_body_id WHERE r.rank_value IS NOT NULL ORDER BY r.year DESC,r.rank_value ASC LIMIT 1");$best=$q->fetch(PDO::FETCH_ASSOC) ?: null;
$q=$pdo->query("SELECT ph_rank,year FROM rankings WHERE ph_rank IS NOT NULL ORDER BY year DESC LIMIT 1");$phRank=$q->fetch(PDO::FETCH_ASSOC) ?: null;
$bodyCount=(int)$pdo->query('SELECT COUNT(*) FROM ranking_bodies')->fetchColumn();
$bodyCards=$pdo->query("SELECT rb.short_name,rb.name,r.category,r.global_rank,r.year,r.note FROM rankings r JOIN ranking_bodies rb ON rb.id=r.ranking_body_id JOIN (SELECT ranking_body_id,MAX(year) latest_year FROM rankings GROUP BY ranking_body_id) l ON l.ranking_body_id=r.ranking_body_id AND l.latest_year=r.year ORDER BY rb.short_name,r.category")->fetchAll(PDO::FETCH_ASSOC);
$trend=$pdo->query("SELECT r.year,r.rank_value,r.global_rank FROM rankings r JOIN ranking_bodies rb ON rb.id=r.ranking_body_id WHERE rb.short_name='QS' AND r.category IS NULL ORDER BY r.year")->fetchAll(PDO::FETCH_ASSOC);$trendYears=array_map(fn($x)=>(string)$x['year'],$trend);$trendRanks=array_map(fn($x)=>$x['rank_value']===null?null:(int)$x['rank_value'],$trend);$trendDisplay=array_column($trend,'global_rank');
$latestCollegeYear=$pdo->query('SELECT MAX(year) FROM colleges')->fetchColumn();$collegeRows=$latestCollegeYear?$pdo->prepare('SELECT * FROM colleges WHERE year=? ORDER BY contribution_percent DESC'):null;if($collegeRows){$collegeRows->execute([$latestCollegeYear]);$collegeRows=$collegeRows->fetchAll(PDO::FETCH_ASSOC);}else{$collegeRows=[];}$collegePieData=array_map(fn($r)=>['name'=>$r['short_code'].' - '.$r['name'],'value'=>(float)$r['contribution_percent']],$collegeRows);
$latestProgramYear=$pdo->query('SELECT MAX(year) FROM programs')->fetchColumn();$programRows=[];if($latestProgramYear){$q=$pdo->prepare("SELECT p.national_rank,p.name,c.short_code,p.score,p.movement FROM programs p JOIN colleges c ON c.id=p.college_id WHERE p.year=? ORDER BY p.national_rank");$q->execute([$latestProgramYear]);$programRows=$q->fetchAll(PDO::FETCH_ASSOC);}
$breakdownSections=[];$groups=$pdo->query("SELECT rb.id body_id,rb.short_name,rb.name,b.year FROM ranking_breakdowns b JOIN ranking_bodies rb ON rb.id=b.ranking_body_id JOIN (SELECT ranking_body_id,MAX(year) latest_year FROM ranking_breakdowns GROUP BY ranking_body_id) l ON l.ranking_body_id=b.ranking_body_id AND l.latest_year=b.year GROUP BY rb.id,rb.short_name,rb.name,b.year ORDER BY rb.short_name")->fetchAll(PDO::FETCH_ASSOC);$q=$pdo->prepare('SELECT group_label,item_label,rank_display,rank_value FROM ranking_breakdowns WHERE ranking_body_id=? AND year=? ORDER BY (rank_value IS NULL),rank_value,item_label');foreach($groups as $g){$q->execute([$g['body_id'],$g['year']]);$items=$q->fetchAll(PDO::FETCH_ASSOC);if($items)$breakdownSections[]=['body'=>$g,'items'=>$items];}
$accreditedCount=(int)$pdo->query('SELECT COUNT(DISTINCT program_name) FROM accreditations')->fetchColumn();$accreditationRows=$pdo->query("SELECT program_name,accrediting_body,year,MAX(assessment_date) assessment_date,MAX(CASE WHEN criterion='Overall Verdict' THEN score END) verdict,AVG(numeric_score) avg_score FROM accreditations GROUP BY program_name,accrediting_body,year ORDER BY year DESC,program_name")->fetchAll(PDO::FETCH_ASSOC);
$uploadedKpi=['file'=>'','global'=>null,'national'=>null,'bodies'=>null,'programs'=>null];
$uploadedStmt=$pdo->query("SELECT fileName,extractedData,scannedAt FROM records WHERE status='Approved' ORDER BY scannedAt DESC LIMIT 1");
$uploadedRecord=$uploadedStmt->fetch(PDO::FETCH_ASSOC) ?: null;
if($uploadedRecord){
    $uploadedKpi['file']=$uploadedRecord['fileName'];
    $uploadedSheets=json_decode((string)$uploadedRecord['extractedData'],true) ?: [];
    $uploadedSheetValues=array_values(is_array($uploadedSheets)?$uploadedSheets:[]);
    $uploadedProgramRows=0;
    foreach($uploadedSheetValues as $sheet){
        if(!is_array($sheet))continue;
        $headers=array_map(fn($h)=>strtolower(trim((string)$h)),(array)($sheet['headers']??[]));
        $rows=(array)($sheet['rows']??[]);
        foreach($headers as $index=>$header){
            $values=array_map(fn($row)=>$row[$index]??null,$rows);
            $numbers=array_values(array_filter($values,fn($value)=>is_numeric($value)&&$value>0));
            if($numbers && preg_match('/global.*rank|rank.*global/',$header))$uploadedKpi['global']=min(array_map('floatval',$numbers));
            if($numbers && preg_match('/national.*rank|ph.*rank|philippine.*rank/',$header))$uploadedKpi['national']=min(array_map('floatval',$numbers));
        }
        if(array_filter($headers,fn($header)=>preg_match('/program|accredit/',$header)))$uploadedProgramRows+=count($rows);
    }
    $uploadedKpi['bodies']=count($uploadedSheetValues);
    if($uploadedProgramRows>0)$uploadedKpi['programs']=$uploadedProgramRows;
}
$displayGlobal=$uploadedKpi['global']??($best['global_rank']??'—');
$displayNational=$uploadedKpi['national']??($phRank['ph_rank']??'—');
$displayBodies=$uploadedKpi['bodies']??$bodyCount;
$displayPrograms=$uploadedKpi['programs']??$accreditedCount;
$kpiSource=$uploadedKpi['file'] ? 'Uploaded: '.$uploadedKpi['file'] : 'Institutional database';
$uploadedAnalysis=['bodyCards'=>[],'trendYears'=>[],'trendRanks'=>[],'trendDisplay'=>[],'collegePieData'=>[],'programRows'=>[],'breakdownSections'=>[]];
$uploadedCollegeTotals=[];
$contributionChartTitle='College Contribution to Score';
$contributionChartSubtitle='Top college contributions from the uploaded workbook';
$contributionChartBadge='COLLEGES';
if($uploadedKpi['file']){
    foreach($uploadedSheetValues as $sheetIndex=>$sheet){
        if(!is_array($sheet))continue;
        $sheetName=(string)($sheet['name']??('Worksheet '.($sheetIndex+1)));
        $headers=array_map(fn($h)=>trim((string)$h),(array)($sheet['headers']??[]));
        $rows=array_values(array_filter((array)($sheet['rows']??[]),fn($row)=>is_array($row)));
        if(!$headers||!$rows)continue;
        $normalizedHeaders=array_map(fn($header)=>preg_replace('/[^a-z0-9]+/',' ',strtolower($header)), $headers);
        $findColumn=function(array $patterns)use($normalizedHeaders){foreach($patterns as $pattern){foreach($normalizedHeaders as $index=>$header){if(preg_match($pattern,$header))return $index;}}return null;};
        $nameColumn=$findColumn(['/program\s*(name|title)?/','/course\s*(name|title)?/','/^name$/','/indicator/','/item/','/category/']);
        $rankColumn=$findColumn(['/national\s*rank/','/ph\s*rank/','/philippine\s*rank/','/^rank$/','/ranking/']);
        $headerCollegeColumn=$findColumn(['/college/','/faculty/','/department/','/school/','/academic\s*unit/','/college\s*name/']);
        $collegeColumn=$headerCollegeColumn;
        $scoreColumn=$findColumn(['/score/','/points?/','/value/','/rating/','/metric/','/result/']);
        $movementColumn=$findColumn(['/movement/','/change/','/delta/','/trend/']);
        $numericColumn=$scoreColumn;
        if($numericColumn===null){foreach($normalizedHeaders as $columnIndex=>$header){$values=array_values(array_filter(array_map(fn($row)=>$row[$columnIndex]??null,$rows),fn($value)=>is_numeric($value)));if(count($values)>0){$numericColumn=$columnIndex;break;}}}
        if($nameColumn===null){foreach($normalizedHeaders as $columnIndex=>$header){if($columnIndex!==$rankColumn&&$columnIndex!==$numericColumn&&$columnIndex!==$movementColumn){$nameColumn=$columnIndex;break;}}}
        if($nameColumn===null)$nameColumn=0;
        if($numericColumn===null)continue;
        if($collegeColumn===null && preg_match('/college|faculty|department|academic\s*unit/i',$sheetName)){
            $bestCollegeScore=0;
            foreach($headers as $columnIndex=>$header){
                if($columnIndex===$numericColumn||$columnIndex===$rankColumn)continue;
                $candidateValues=array_values(array_filter(array_map(fn($row)=>trim((string)($row[$columnIndex]??'')),$rows),fn($value)=>$value!==''&&!preg_match('/^\d+(?:\s*[-–]\s*\d+)?$/',$value)&&!preg_match('/^(the impact rankings|qs|times higher education|wuri|ui greenmetric)$/i',$value)));
                $uniqueCount=count(array_unique($candidateValues));
                $candidateScore=$uniqueCount+(count($candidateValues)>0?($uniqueCount/count($candidateValues)):0);
                if($uniqueCount>=2&&$candidateScore>$bestCollegeScore){$collegeColumn=$columnIndex;$bestCollegeScore=$candidateScore;}
            }
        }
        $categoryColumn=$nameColumn;
        $labels=[];$values=[];
        foreach($rows as $row){
            if(!isset($row[$numericColumn])||!is_numeric($row[$numericColumn]))continue;
            $label=trim((string)($row[$categoryColumn]??''));
            if($label==='')$label='Item '.(count($labels)+1);
            $labels[]=$label;
            $values[]=(float)$row[$numericColumn];
        }
        if(!$values)continue;
        $uploadedAnalysis['bodyCards'][]=['short_name'=>$sheetName,'name'=>$sheetName,'category'=>$headers[$numericColumn]??'Metric','global_rank'=>$values[0],'year'=>date('Y',strtotime($uploadedRecord['scannedAt']??'now')),'note'=>'Derived from uploaded workbook'];
        if(!$uploadedAnalysis['trendYears']){
            $uploadedAnalysis['trendYears']=array_map(fn($index)=>(string)($index+1),array_keys($values));
            $uploadedAnalysis['trendRanks']=array_map('intval',$values);
            $uploadedAnalysis['trendDisplay']=array_map(fn($value)=>(string)$value,$values);
        }
        if($headerCollegeColumn!==null || preg_match('/college|faculty|department|academic\s*unit/i',$sheetName)){
            $contributionColumn=$headerCollegeColumn!==null?$headerCollegeColumn:$nameColumn;
            foreach($rows as $row){
                $collegeName=trim((string)($row[$contributionColumn]??''));
                $collegeValue=$scoreColumn!==null&&isset($row[$scoreColumn])&&is_numeric($row[$scoreColumn])?(float)$row[$scoreColumn]:(is_numeric($row[$numericColumn]??null)?(float)$row[$numericColumn]:null);
                if($collegeName!==''&&$collegeValue!==null)$uploadedCollegeTotals[$collegeName]=($uploadedCollegeTotals[$collegeName]??0)+$collegeValue;
            }
        }
        foreach(array_slice($rows,0,100) as $row){
            $name=trim((string)($row[$nameColumn]??''));
            $score=$scoreColumn!==null&&isset($row[$scoreColumn])&&is_numeric($row[$scoreColumn])?(float)$row[$scoreColumn]:(is_numeric($row[$numericColumn]??null)?(float)$row[$numericColumn]:null);
            $rank=$rankColumn!==null?trim((string)($row[$rankColumn]??'')):'';
            $college=$collegeColumn!==null?trim((string)($row[$collegeColumn]??'')):$sheetName;
            $movement=$movementColumn!==null?trim((string)($row[$movementColumn]??'')):'';
            if($name===''&&$rank==='')continue;
            $uploadedAnalysis['programRows'][]=['national_rank'=>$rank,'name'=>$name!==''?$name:'Unlabeled item','short_code'=>$college!==''?$college:$sheetName,'score'=>$score,'movement'=>$movement];
        }
        $uploadedAnalysis['breakdownSections'][]=['body'=>['short_name'=>$sheetName,'name'=>$sheetName,'year'=>date('Y',strtotime($uploadedRecord['scannedAt']??'now'))],'items'=>array_map(fn($label,$value)=>['item_label'=>$label,'rank_display'=>(string)$value,'rank_value'=>$value],$labels,$values)];
    }
    if($uploadedAnalysis['bodyCards'])$bodyCards=$uploadedAnalysis['bodyCards'];
    if($uploadedAnalysis['trendYears']){$trendYears=$uploadedAnalysis['trendYears'];$trendRanks=$uploadedAnalysis['trendRanks'];$trendDisplay=$uploadedAnalysis['trendDisplay'];}
    if($uploadedCollegeTotals){
        arsort($uploadedCollegeTotals,SORT_NUMERIC);
        $uploadedCollegeTotals=array_slice($uploadedCollegeTotals,0,12,true);
        $collegePieData=array_map(fn($name,$value)=>['name'=>$name,'value'=>round((float)$value,2)],array_keys($uploadedCollegeTotals),array_values($uploadedCollegeTotals));
        $latestCollegeYear='Uploaded workbook';
    }
    if($uploadedAnalysis['programRows'])$programRows=$uploadedAnalysis['programRows'];
    if($uploadedAnalysis['breakdownSections'])$breakdownSections=$uploadedAnalysis['breakdownSections'];
}
if($uploadedKpi['file'] && !$uploadedCollegeTotals){
    $programTotals=[];
    foreach($uploadedSheetValues as $sheet){
        if(!is_array($sheet))continue;
        $headers=array_map(fn($header)=>strtolower(trim((string)$header)),(array)($sheet['headers']??[]));
        $programColumn=null;$scoreColumn=null;
        foreach($headers as $index=>$header){
            if($programColumn===null&&preg_match('/program|course|degree/',$header))$programColumn=$index;
            if($scoreColumn===null&&preg_match('/score|rating|value|result/',$header))$scoreColumn=$index;
        }
        if($programColumn===null||$scoreColumn===null)continue;
        foreach((array)($sheet['rows']??[]) as $row){
            $program=trim((string)($row[$programColumn]??''));
            $score=$row[$scoreColumn]??null;
            if($program!==''&&is_numeric($score))$programTotals[$program]=($programTotals[$program]??0)+(float)$score;
        }
    }
    if($programTotals){
        arsort($programTotals,SORT_NUMERIC);
        $programTotals=array_slice($programTotals,0,12,true);
        $collegePieData=array_map(fn($name,$value)=>['name'=>$name,'value'=>round((float)$value,2)],array_keys($programTotals),array_values($programTotals));
        $contributionChartTitle='Program Contribution to Score';
        $contributionChartSubtitle='Top program scores from the uploaded workbook (no College column was provided)';
        $contributionChartBadge='PROGRAMS';
        $latestCollegeYear='Uploaded workbook';
    }
}
?>

<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CLSU Performance Observatory - IRIS</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        brand: {
                            50: '#ecfdf5',
                            100: '#d1fae5',
                            500: '#10b981',
                            600: '#059669',
                            700: '#047857',
                            800: '#065f46',
                            900: '#064e3b',
                            gold: '#f59e0b'
                        }
                    }
                }
            }
        }
    </script>
    <!-- Flowbite CSS & JS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.3.0/flowbite.min.css" rel="stylesheet" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.3.0/flowbite.min.js"></script>
    <!-- Apache ECharts CDN -->
    <script src="https://cdn.jsdelivr.net/npm/echarts@5.5.0/dist/echarts.min.js"></script>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        #page-loader{position:fixed;inset:0;display:flex;align-items:center;justify-content:center;background:rgba(15,23,42,.68);backdrop-filter:blur(6px);z-index:10000;transition:opacity .3s ease,visibility .3s ease;}
        #page-loader.hidden{opacity:0;visibility:hidden;pointer-events:none;}
        .iris-loader{position:relative;width:72px;height:72px;border-radius:50%;background:conic-gradient(#10b981,#34d399,#fbbf24,#10b981);animation:spin 1s linear infinite;box-shadow:0 0 30px rgba(16,185,129,.5)}
        .iris-loader::before{content:"";position:absolute;inset:10px;border-radius:50%;background:rgba(15,23,42,.9);border:2px solid rgba(255,255,255,.18)}
        .iris-loader::after{content:"IRIS";position:absolute;inset:0;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:800;letter-spacing:.12em;color:#d1fae5}
        @keyframes spin{to{transform:rotate(360deg)}}
    </style>
</head>
<body class="bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-gray-100 min-h-screen flex flex-col">
    <div id="page-loader" aria-live="polite" aria-label="Loading page">
        <div class="iris-loader" aria-hidden="true"></div>
    </div>

    <!-- Top Navigation Bar -->
    <nav class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 sticky top-0 z-50 backdrop-blur-md bg-opacity-90 dark:bg-opacity-90">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <!-- Brand / Logo -->
                <div class="flex items-center space-x-3">
                    <a href="<?= e(base_url('user/dashboard.php')) ?>" class="logo-refresh-trigger flex items-center space-x-3" data-target="<?= e(base_url('user/dashboard.php')) ?>">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-emerald-600 to-teal-400 flex items-center justify-center text-white font-black text-xl shadow-lg shadow-emerald-500/20 ring-2 ring-emerald-400/30">
                            <img src="<?= e(base_url('images/iris-logo.png')) ?>" alt="IRIS Logo" class="w-10 h-10 object-contain">
                        </div>
                            <div>
                            <div class="flex items-center space-x-2">
                                <span class="text-xl font-bold tracking-tight bg-gradient-to-r from-emerald-600 to-teal-500 dark:from-emerald-400 dark:to-teal-300 bg-clip-text text-transparent">IRIS</span>
                                <span class="text-xs px-2 py-0.5 font-medium rounded-full bg-emerald-100 dark:bg-emerald-900/60 text-emerald-800 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-700/50">CLSU</span>
                            </div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 hidden sm:block">Performance Observatory</p>
                        </div>
                    </a>
                </div>

                <!-- Nav Center Links -->
                <div class="hidden md:flex items-center space-x-1">
                    <a href="#headline-rankings" class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-600 dark:text-gray-300 hover:text-emerald-600 dark:hover:text-emerald-400 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors">
                        <i class="fa-solid fa-trophy mr-2"></i> Headline Rankings
                    </a>
                    <a href="#program-rankings" class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-600 dark:text-gray-300 hover:text-emerald-600 dark:hover:text-emerald-400 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors">
                        <i class="fa-solid fa-graduation-cap mr-2"></i> Programs
                    </a>
                </div>

                <!-- Right Actions: Dark Mode & User Profile -->
                <div class="flex items-center space-x-3">
                    <!-- Theme Toggle -->
                    <button id="theme-toggle" type="button" class="text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 focus:outline-none focus:ring-4 focus:ring-gray-200 dark:focus:ring-gray-700 rounded-lg text-sm p-2.5">
                        <i id="theme-toggle-dark-icon" class="hidden fa-solid fa-moon text-base"></i>
                        <i id="theme-toggle-light-icon" class="hidden fa-solid fa-sun text-base text-amber-400"></i>
                    </button>

                    <!-- User Profile Dropdown -->
                    <div class="relative">
                        <button type="button" class="flex items-center space-x-2 text-sm bg-gray-100 dark:bg-gray-700 p-1.5 rounded-full focus:ring-4 focus:ring-emerald-300 dark:focus:ring-emerald-800" id="user-menu-button" aria-expanded="false" data-dropdown-toggle="user-dropdown" data-dropdown-placement="bottom">
                            <div class="w-8 h-8 rounded-full bg-gradient-to-tr from-emerald-500 to-amber-400 flex items-center justify-center text-white font-bold text-xs shadow">
                                <?= strtoupper(substr(($_SESSION['username'] ?? 'U'), 0, 2)) ?>
                            </div>
                            <span class="hidden sm:inline-block font-medium text-xs px-1 text-gray-700 dark:text-gray-200"><?= htmlspecialchars(($_SESSION['username'] ?? 'U')) ?></span>
                        </button>
                        <!-- Dropdown Menu -->
                        <div class="z-50 hidden my-4 text-base list-none bg-white divide-y divide-gray-100 rounded-xl shadow-lg dark:bg-gray-700 dark:divide-gray-600" id="user-dropdown">
                            <div class="px-4 py-3">
                                <span class="block text-sm font-semibold text-gray-900 dark:text-white"><?= htmlspecialchars(($_SESSION['username'] ?? 'U')) ?></span>
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-900/60 dark:text-emerald-300 mt-1">
                                    <?= ($_SESSION['role'] ?? null) === 'admin' ? 'ADMINISTRATOR' : 'VIEWER' ?>
                                </span>
                            </div>
                            <ul class="py-2" aria-labelledby="user-menu-button">
                                <?php if (($_SESSION['role'] ?? null) === 'admin'): ?>
                                    <li>
                                        <a href="<?= e(base_url('admin/dashboard.php')) ?>" class="block px-4 py-2 text-sm text-emerald-600 hover:bg-emerald-50 dark:hover:bg-gray-600 dark:text-emerald-400 font-medium">
                                            <i class="fa-solid fa-shield-halved mr-2"></i> Admin Portal
                                        </a>
                                    </li>
                                <?php endif; ?>
                            </ul>
                            <div class="py-1">
                                <form method="POST" action="<?= e(base_url('auth/logout.php')) ?>">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="block w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-gray-100 dark:hover:bg-gray-600 dark:text-red-400">
                                        <i class="fa-solid fa-right-from-bracket mr-2"></i> Sign Out
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Container -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 flex-1 w-full space-y-8" id="overview">
        <?php if (flash('success')): ?>
            <div class="flex items-center p-4 text-emerald-800 rounded-xl bg-emerald-50 dark:bg-gray-800 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800" role="alert"><i class="fa-solid fa-circle-check text-lg mr-3"></i><div class="text-sm font-medium"><?= htmlspecialchars(flash('success')) ?></div></div>
        <?php endif; ?>
        <?php if (flash('error')): ?>
            <div class="flex items-center p-4 text-red-800 rounded-xl bg-red-50 dark:bg-gray-800 dark:text-red-400 border border-red-200 dark:border-red-800" role="alert"><i class="fa-solid fa-circle-exclamation text-lg mr-3"></i><div class="text-sm font-medium"><?= htmlspecialchars(flash('error')) ?></div></div>
        <?php endif; ?>
        
        <!-- Welcome Hero Banner -->
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 bg-gradient-to-r from-emerald-800 to-teal-900 dark:from-emerald-950 dark:to-gray-800 text-white rounded-2xl p-6 shadow-xl relative overflow-hidden">
            <div class="absolute -right-10 -bottom-10 w-64 h-64 bg-white/5 rounded-full blur-2xl pointer-events-none"></div>
            <div class="relative z-10">
                <div class="flex items-center space-x-2 text-emerald-300 text-xs font-semibold uppercase tracking-wider mb-1">
                    <i class="fa-solid fa-building-columns"></i>
                    <span>Central Luzon State University &bull; Institutional Performance</span>
                </div>
                <h1 class="text-2xl sm:text-3xl font-black tracking-tight text-white">Observatory & Analytics Engine</h1>
                <p class="text-sm text-emerald-100/80 mt-1 max-w-xl">
                    Live telemetry across global registries, research citations, and AACCUP / AUN-QA quality frameworks.
                </p>
            </div>
            <div class="relative z-10 flex flex-wrap items-center gap-3">
                <?php if (($_SESSION['role'] ?? null) === 'admin'): ?>
                    <a href="<?= e(base_url('admin/dashboard.php')) ?>" class="inline-flex items-center px-4 py-2.5 text-sm font-semibold text-white bg-emerald-600 hover:bg-emerald-500 rounded-xl shadow-md transition-all">
                        <i class="fa-solid fa-sliders mr-2"></i> Manage Datasets
                    </a>
                <?php endif; ?>
                <div class="flex items-center bg-black/30 backdrop-blur px-3 py-2 rounded-xl text-xs border border-white/10 text-emerald-200">
                    <i class="fa-solid fa-circle text-emerald-400 text-[10px] mr-2 animate-pulse"></i> Telemetry Active
                </div>
            </div>
        </div>

        <!-- 4-Card Executive KPI Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
            <!-- Best Global Rank -->
            <div class="p-5 bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm relative overflow-hidden">
                <div class="absolute top-0 left-0 h-1 w-full bg-gradient-to-r from-emerald-500 to-teal-400"></div>
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Best Global Rank</span>
                    <div class="w-8 h-8 rounded-lg bg-emerald-50 dark:bg-emerald-900/40 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
                        <i class="fa-solid fa-globe"></i>
                    </div>
                </div>
                <div class="mt-4 text-3xl font-extrabold text-gray-900 dark:text-white"><?= htmlspecialchars((string)$displayGlobal) ?></div>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-2 font-medium">
                    <?= htmlspecialchars($uploadedKpi['file'] ? 'Uploaded workbook' : ($best['body_name'] ?? 'Registries')) ?> &bull; <?= htmlspecialchars($uploadedKpi['file'] ? date('Y',strtotime($uploadedRecord['scannedAt']??'now')) : ($best['year'] ?? date('Y'))) ?>
                </p>
            </div>

            <!-- National Rank -->
            <div class="p-5 bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm relative overflow-hidden">
                <div class="absolute top-0 left-0 h-1 w-full bg-gradient-to-r from-amber-500 to-yellow-400"></div>
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">National Rank (PH)</span>
                    <div class="w-8 h-8 rounded-lg bg-amber-50 dark:bg-amber-900/40 text-amber-600 dark:text-amber-400 flex items-center justify-center">
                        <i class="fa-solid fa-trophy"></i>
                    </div>
                </div>
                <div class="mt-4 text-3xl font-extrabold text-gray-900 dark:text-white"><?= htmlspecialchars((string)$displayNational) ?></div>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-2 font-medium">
                    <?= htmlspecialchars($uploadedKpi['file'] ? 'Uploaded workbook' : 'Philippines Ranking Benchmark') ?> <?= !$uploadedKpi['file'] && !empty($phRank['year']) ? '&bull; ' . htmlspecialchars($phRank['year']) : '' ?>
                </p>
            </div>

            <!-- Bodies Monitored -->
            <div class="p-5 bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm relative overflow-hidden">
                <div class="absolute top-0 left-0 h-1 w-full bg-gradient-to-r from-cyan-500 to-blue-400"></div>
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider"><?= htmlspecialchars($uploadedKpi['file'] ? 'Worksheets in Upload' : 'Bodies Monitored') ?></span>
                    <div class="w-8 h-8 rounded-lg bg-cyan-50 dark:bg-cyan-900/40 text-cyan-600 dark:text-cyan-400 flex items-center justify-center">
                        <i class="fa-solid fa-layer-group"></i>
                    </div>
                </div>
                <div class="mt-4 text-3xl font-extrabold text-gray-900 dark:text-white"><?= (int)$displayBodies ?></div>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-2 font-medium"><?= htmlspecialchars($uploadedKpi['file'] ? 'Worksheets detected in uploaded workbook' : 'QS, THE, WURI, UI GreenMetric &amp; Bodies') ?></p>
            </div>

            <!-- Accredited Programs -->
            <div class="p-5 bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm relative overflow-hidden">
                <div class="absolute top-0 left-0 h-1 w-full bg-gradient-to-r from-emerald-600 to-green-500"></div>
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Accredited Programs</span>
                    <div class="w-8 h-8 rounded-lg bg-emerald-50 dark:bg-emerald-900/40 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
                        <i class="fa-solid fa-certificate"></i>
                    </div>
                </div>
                <div class="mt-4 text-3xl font-extrabold text-gray-900 dark:text-white"><?= (int)$displayPrograms ?></div>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-2 font-medium"><?= htmlspecialchars($uploadedKpi['file'] ? 'Program/accreditation rows in uploaded workbook' : 'AUN-QA &amp; International Standards') ?></p>
            </div>
        </div>
        <?php if($uploadedKpi['file']): ?><p class="text-xs text-gray-500 dark:text-gray-400 -mt-5">KPI values reflect the latest approved upload: <?= htmlspecialchars($uploadedKpi['file']) ?></p><?php endif; ?>

        <!-- Headline Rankings Cards -->
        <div id="headline-rankings" class="space-y-4">
            <div>
                <h2 class="text-xl font-bold text-gray-900 dark:text-white flex items-center">
                    <i class="fa-solid fa-ranking-star text-amber-500 mr-2"></i> Published Headline Rankings
                </h2>
                <p class="text-xs text-gray-500 dark:text-gray-400">Latest standings published by major evaluation boards</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                <?php foreach ($bodyCards as $row): ?>
                    <div class="p-5 bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm hover:border-emerald-500/50 transition-colors">
                        <div class="flex justify-between items-start mb-2">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-100 text-amber-800 dark:bg-amber-900/60 dark:text-amber-300">
                                <?= htmlspecialchars($row['short_name']) ?><?= $row['category'] ? ' &bull; ' . htmlspecialchars($row['category']) : '' ?>
                            </span>
                            <span class="text-xs font-bold text-gray-500 dark:text-gray-400"><?= htmlspecialchars($row['year']) ?></span>
                        </div>
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-white mt-2"><?= htmlspecialchars($row['name']) ?></h3>
                        <div class="text-2xl font-black text-emerald-600 dark:text-emerald-400 my-2">
                            <?= htmlspecialchars($row['global_rank'] ?? '—') ?>
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Official Global Standing</p>
                        <?php if ($row['note']): ?>
                            <div class="mt-3 p-2.5 rounded-xl bg-gray-50 dark:bg-gray-700/50 text-xs text-gray-600 dark:text-gray-300 border border-gray-100 dark:border-gray-700">
                                <i class="fa-solid fa-circle-info text-cyan-500 mr-1"></i> <?= htmlspecialchars($row['note']) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Apache ECharts Visual Analytics Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- QS Global Rank Trajectory (ECharts) -->
            <div class="bg-white dark:bg-gray-800 p-6 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm">
                <div class="flex items-center justify-between pb-4 border-b border-gray-100 dark:border-gray-700">
                    <div>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center">
                            <i class="fa-solid fa-chart-line text-emerald-500 mr-2"></i> QS Global Rank Trajectory
                        </h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Multi-year progression (Lower number = Higher Rank)</p>
                    </div>
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-emerald-100 text-emerald-800 dark:bg-emerald-900/60 dark:text-emerald-300">
                        QS WORLD
                    </span>
                </div>
                <div id="trendChart" class="w-full h-72 pt-4"></div>
            </div>

            <!-- College Contribution Doughnut (ECharts) -->
            <div class="bg-white dark:bg-gray-800 p-6 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm">
                <div class="flex items-center justify-between pb-4 border-b border-gray-100 dark:border-gray-700">
                    <div>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center">
                            <i class="fa-solid fa-chart-pie text-amber-500 mr-2"></i> <?= htmlspecialchars($contributionChartTitle) ?>
                        </h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400"><?= htmlspecialchars($contributionChartSubtitle) ?> (<?= htmlspecialchars((string)$latestCollegeYear) ?>)</p>
                    </div>
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-amber-100 text-amber-800 dark:bg-amber-900/60 dark:text-amber-300">
                        <?= htmlspecialchars($contributionChartBadge) ?>
                    </span>
                </div>
                <div id="collegeChart" class="w-full h-72 pt-4"></div>
            </div>
        </div>

        <!-- Scanner-Published Analytics -->
        <section id="scanner-published-graphs" class="space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-3">
                <div>
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white flex items-center">
                        <i class="fa-solid fa-chart-column text-emerald-500 mr-2"></i> Scanner-Published Analytics
                    </h2>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Approved visualizations published from the IRIS Scanner</p>
                </div>
                <span id="publishedGraphCount" class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800 dark:bg-emerald-900/60 dark:text-emerald-300">
                    <i class="fa-solid fa-circle-check mr-1"></i> Loading published graphs
                </span>
            </div>
            <div id="scannerPublishedGraphsGrid" class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div id="scannerPublishedGraphsEmpty" class="lg:col-span-2 p-6 rounded-2xl border border-dashed border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-800/60 text-center text-sm text-gray-500 dark:text-gray-400">
                    <i class="fa-solid fa-chart-simple text-lg mr-1"></i> No approved scanner graphs have been published yet.
                </div>
            </div>
        </section>

        <!-- Program Rankings Table with Live Search -->
        <div class="bg-white dark:bg-gray-800 p-6 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm space-y-4" id="program-rankings">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-4 border-b border-gray-100 dark:border-gray-700 gap-3">
                <div>
                    <h2 class="text-lg font-bold text-gray-900 dark:text-white flex items-center">
                        <i class="fa-solid fa-graduation-cap text-emerald-500 mr-2"></i> National Program Rankings
                    </h2>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Academic degree rankings &amp; year-over-year movement</p>
                </div>
                <div class="flex items-center space-x-3">
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                            <i class="fa-solid fa-magnifying-glass text-gray-400 text-xs"></i>
                        </div>
                        <input type="text" id="programSearch" class="block p-2 pl-9 text-xs text-gray-900 border border-gray-300 rounded-lg w-52 bg-gray-50 focus:ring-emerald-500 focus:border-emerald-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" placeholder="Search program or college..." onkeyup="filterPrograms()">
                    </div>
                    <span class="text-xs text-gray-500 dark:text-gray-400 font-medium whitespace-nowrap" id="programCountLabel">Showing <?= count($programRows) ?> programs</span>
                </div>
            </div>

            <div class="overflow-x-auto rounded-xl border border-gray-100 dark:border-gray-700">
                <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400" id="programTable">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-100 dark:bg-gray-700 dark:text-gray-300">
                        <tr>
                            <th scope="col" class="px-4 py-3">Nat. Rank</th>
                            <th scope="col" class="px-4 py-3">College</th>
                            <th scope="col" class="px-4 py-3">Program Name</th>
                            <th scope="col" class="px-4 py-3">Score</th>
                            <th scope="col" class="px-4 py-3">Movement</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        <?php if (empty($programRows)): ?>
                            <tr><td colspan="5" class="text-center py-6 text-gray-500 dark:text-gray-400">No program ranking data recorded yet.</td></tr>
                        <?php else: ?>
                            <?php foreach ($programRows as $row):
                                $movement = trim((string)($row['movement'] ?? ''));
                                $numericMovement = is_numeric($movement) ? (float)$movement : null;
                                $moveBadge = $numericMovement !== null && $numericMovement > 0
                                    ? '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-900/60 dark:text-emerald-300"><i class="fa-solid fa-arrow-up mr-1"></i> +' . htmlspecialchars((string)$movement) . '</span>'
                                    : ($numericMovement !== null && $numericMovement < 0
                                        ? '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-rose-100 text-rose-800 dark:bg-rose-900/60 dark:text-rose-300"><i class="fa-solid fa-arrow-down mr-1"></i> ' . htmlspecialchars((string)$movement) . '</span>'
                                        : '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">' . htmlspecialchars($movement !== '' ? $movement : '—') . '</span>');
                            ?>
                                <tr class="bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-750 transition-colors">
                                    <td class="px-4 py-3 font-extrabold text-emerald-600 dark:text-emerald-400 font-mono">
                                        <?= htmlspecialchars((string)($row['national_rank'] !== '' && $row['national_rank'] !== null ? $row['national_rank'] : '—')) ?>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-600">
                                            <?= htmlspecialchars($row['short_code']) ?>
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 font-semibold text-gray-900 dark:text-white"><?= htmlspecialchars($row['name']) ?></td>
                                    <td class="px-4 py-3 font-mono font-bold text-gray-900 dark:text-white"><?= $row['score'] !== null && $row['score'] !== '' ? number_format((float)$row['score'], 1) : '—' ?></td>
                                    <td class="px-4 py-3"><?= $moveBadge ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Ranking Breakdown Sections (ECharts Horizontal Bars) -->
        <?php if (count($breakdownSections)): ?>
            <div class="space-y-4">
                <div>
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white flex items-center">
                        <i class="fa-solid fa-bullseye text-cyan-500 mr-2"></i> Category Breakdowns &amp; SDG Performance
                    </h2>
                    <p class="text-xs text-gray-500 dark:text-gray-400">In-depth sub-category evaluations &amp; indicators</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php foreach ($breakdownSections as $idx => $section): ?>
                        <div class="bg-white dark:bg-gray-800 p-5 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm flex flex-col">
                            <div class="flex justify-between items-start mb-2">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-100 text-amber-800 dark:bg-amber-900/60 dark:text-amber-300">
                                    <?= htmlspecialchars($section['body']['short_name']) ?> &bull; <?= htmlspecialchars($section['body']['year']) ?>
                                </span>
                            </div>
                            <h3 class="text-base font-bold text-gray-900 dark:text-white mb-2"><?= htmlspecialchars($section['items'][0]['group_label'] ?? 'Breakdown') ?></h3>
                            
                            <!-- ECharts mini bar chart container -->
                            <div id="breakdownChart<?= $idx ?>" class="w-full h-48"></div>

                            <!-- List breakdown table -->
                            <div class="overflow-y-auto max-h-48 mt-3 rounded-lg border border-gray-100 dark:border-gray-700">
                                <table class="w-full text-xs text-left text-gray-500 dark:text-gray-400">
                                    <thead class="text-[10px] uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-300">
                                        <tr>
                                            <th class="px-3 py-2">Indicator</th>
                                            <th class="px-3 py-2 text-right">Score/Rank</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                        <?php foreach ($section['items'] as $item): ?>
                                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                                <td class="px-3 py-2 text-gray-800 dark:text-gray-200"><?= htmlspecialchars($item['item_label']) ?></td>
                                                <td class="px-3 py-2 text-right font-mono font-bold text-emerald-600 dark:text-emerald-400">
                                                    <?= htmlspecialchars($item['rank_display'] ?? '—') ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Program Accreditation (AUN-QA) -->
        <?php if ($accreditedCount > 0): ?>
            <div class="bg-white dark:bg-gray-800 p-6 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-4 border-b border-gray-100 dark:border-gray-700 gap-3">
                    <div>
                        <h2 class="text-lg font-bold text-gray-900 dark:text-white flex items-center">
                            <i class="fa-solid fa-stamp text-emerald-500 mr-2"></i> Program Quality Accreditations (AUN-QA &amp; International)
                        </h2>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Independent external peer reviews</p>
                    </div>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                            <i class="fa-solid fa-magnifying-glass text-gray-400 text-xs"></i>
                        </div>
                        <input type="text" id="accredSearch" class="block p-2 pl-9 text-xs text-gray-900 border border-gray-300 rounded-lg w-52 bg-gray-50 focus:ring-emerald-500 focus:border-emerald-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" placeholder="Filter accreditations..." onkeyup="filterAccred()">
                    </div>
                </div>

                <div class="overflow-x-auto rounded-xl border border-gray-100 dark:border-gray-700">
                    <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400" id="accredTable">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-100 dark:bg-gray-700 dark:text-gray-300">
                            <tr>
                                <th scope="col" class="px-4 py-3">Program</th>
                                <th scope="col" class="px-4 py-3">Accrediting Body</th>
                                <th scope="col" class="px-4 py-3">Cycle Year</th>
                                <th scope="col" class="px-4 py-3">Assessment Date</th>
                                <th scope="col" class="px-4 py-3">Avg. Score</th>
                                <th scope="col" class="px-4 py-3">Overall Verdict</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <?php foreach ($accreditationRows as $row):
                                $v = $row['verdict'] ?? '—';
                                $isApproved = stripos($v, 'adequate') !== false || stripos($v, 'certified') !== false || stripos($v, 'pass') !== false;
                            ?>
                                <tr class="bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-750 transition-colors">
                                    <td class="px-4 py-3 font-semibold text-gray-900 dark:text-white"><?= htmlspecialchars($row['program_name']) ?></td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-cyan-100 text-cyan-800 dark:bg-cyan-900/60 dark:text-cyan-300">
                                            <?= htmlspecialchars($row['accrediting_body']) ?>
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 font-mono"><?= htmlspecialchars($row['year']) ?></td>
                                    <td class="px-4 py-3 text-xs text-gray-500 dark:text-gray-400"><?= htmlspecialchars($row['assessment_date'] ?? '—') ?></td>
                                    <td class="px-4 py-3 font-mono font-bold text-gray-900 dark:text-white">
                                        <?= $row['avg_score'] !== null ? number_format((float)$row['avg_score'], 2) : '—' ?>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold <?= $isApproved ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/60 dark:text-emerald-300' : 'bg-amber-100 text-amber-800 dark:bg-amber-900/60 dark:text-amber-300' ?>">
                                            <i class="fa-solid <?= $isApproved ? 'fa-circle-check' : 'fa-clock' ?> mr-1"></i>
                                            <?= htmlspecialchars($v) ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

        <!-- AI Observatory Executive Summary -->
        <section class="p-6 bg-gradient-to-r from-emerald-900/30 to-teal-900/30 border border-emerald-500/30 rounded-2xl shadow-sm" id="intelligence-summary">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-4 border-b border-emerald-500/20 gap-3">
                <div class="flex items-center space-x-2">
                    <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold bg-emerald-500 text-white shadow-sm">
                        <i class="fa-solid fa-wand-magic-sparkles mr-1.5"></i> IRIS Intelligence Summary
                    </span>
                    <span class="text-xs text-gray-500 dark:text-gray-400">Explanation based on the latest approved uploaded workbook</span>
                </div>
                <button onclick="loadSummary()" class="inline-flex items-center px-3 py-1.5 text-xs font-semibold text-emerald-600 dark:text-emerald-300 bg-white dark:bg-gray-800 border border-emerald-300 dark:border-emerald-700 hover:bg-emerald-50 rounded-lg transition-colors" id="regenBtn">
                    <i class="fa-solid fa-arrows-rotate mr-1.5"></i> Regenerate Brief
                </button>
            </div>
            <div id="ai-summary" class="mt-4 text-sm text-gray-700 dark:text-gray-200 leading-relaxed">
                <div class="animate-pulse space-y-2">
                    <div class="h-4 bg-emerald-200/50 dark:bg-gray-700 rounded w-3/4"></div>
                    <div class="h-4 bg-emerald-200/50 dark:bg-gray-700 rounded w-5/6"></div>
                    <div class="h-4 bg-emerald-200/50 dark:bg-gray-700 rounded w-1/2"></div>
                </div>
            </div>
        </section>
    </main>

    <!-- Footer -->
    <footer class="bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 py-6 mt-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row items-center justify-between text-xs text-gray-500 dark:text-gray-400 gap-4">
            <div class="flex items-center space-x-2">
                <span class="font-bold text-gray-800 dark:text-gray-200">IRIS</span>
                <span>&bull; IAO'S INTERNATIONAL RAPPORT INSIGHT SYSTEM</span>
            </div>
            <div>
                Crafted for Central Luzon State University &bull; Powered by Flowbite &amp; Apache ECharts
            </div>
        </div>
    </footer>

    <!-- Theme Toggle & ECharts Script Initialization -->
    <script>
        (function () {
            const loader = document.getElementById('page-loader');
            const hideLoader = () => {
                if (loader) {
                    loader.classList.add('hidden');
                }
            };

            document.querySelectorAll('.logo-refresh-trigger').forEach((link) => {
                link.addEventListener('click', function (event) {
                    const target = this.getAttribute('data-target') || this.href;
                    event.preventDefault();
                    loader && loader.classList.remove('hidden');
                    const currentUrl = window.location.href.split('#')[0];
                    if (target && target.split('#')[0] === currentUrl.split('#')[0]) {
                        window.location.reload();
                        return;
                    }
                    window.location.href = target;
                });
            });

            setTimeout(hideLoader, 90);
            window.addEventListener('load', hideLoader);
        })();

        // --- Dark Mode Logic ---
        const themeToggleDarkIcon = document.getElementById('theme-toggle-dark-icon');
        const themeToggleLightIcon = document.getElementById('theme-toggle-light-icon');
        const themeToggleBtn = document.getElementById('theme-toggle');

        if (document.documentElement.classList.contains('dark')) {
            themeToggleLightIcon.classList.remove('hidden');
        } else {
            themeToggleDarkIcon.classList.remove('hidden');
        }

        themeToggleBtn.addEventListener('click', function() {
            themeToggleDarkIcon.classList.toggle('hidden');
            themeToggleLightIcon.classList.toggle('hidden');

            if (document.documentElement.classList.contains('dark')) {
                document.documentElement.classList.remove('dark');
                localStorage.setItem('color-theme', 'light');
            } else {
                document.documentElement.classList.add('dark');
                localStorage.setItem('color-theme', 'dark');
            }
            renderAllCharts();
        });

        // --- Apache ECharts Data & Initialization ---
        const trendYears = <?= json_encode($trendYears) ?>;
        const trendRanks = <?= json_encode($trendRanks) ?>;
        const trendDisplay = <?= json_encode($trendDisplay) ?>;
        const collegePieData = <?= json_encode($collegePieData) ?>;
        const breakdownSections = <?= json_encode(array_map(function ($s) {
            return [
                'labels' => array_map(fn($i) => $i['item_label'], $s['items']),
                'values' => array_map(fn($i) => $i['rank_value'] !== null ? (int)$i['rank_value'] : 0, $s['items']),
                'displays' => array_map(fn($i) => $i['rank_display'] ?? '', $s['items']),
            ];
        }, $breakdownSections)) ?>;

        let chartInstances = [];

        function renderAllCharts() {
            chartInstances.forEach(c => c && c.dispose());
            chartInstances = [];

            const isDark = document.documentElement.classList.contains('dark');
            const textColor = isDark ? '#9ca3af' : '#4b5563';
            const splitLineColor = isDark ? '#374151' : '#f3f4f6';
            const tooltipBg = isDark ? '#1f2937' : '#ffffff';
            const tooltipBorder = isDark ? '#374151' : '#e5e7eb';
            const tooltipText = isDark ? '#f9fafb' : '#111827';

            // 1. QS Rank Line Chart
            const trendElem = document.getElementById('trendChart');
            if (trendElem) {
                const trendChart = echarts.init(trendElem);
                chartInstances.push(trendChart);
                trendChart.setOption({
                    backgroundColor: 'transparent',
                    tooltip: {
                        trigger: 'axis',
                        backgroundColor: tooltipBg,
                        borderColor: tooltipBorder,
                        textStyle: { color: tooltipText },
                        formatter: function(params) {
                            const idx = params[0].dataIndex;
                            return `Year: <b>${trendYears[idx]}</b><br/>Standing: <b>${trendDisplay[idx] || '—'}</b>`;
                        }
                    },
                    grid: { left: '3%', right: '4%', bottom: '3%', top: '10%', containLabel: true },
                    xAxis: {
                        type: 'category',
                        boundaryGap: false,
                        data: trendYears,
                        axisLine: { lineStyle: { color: splitLineColor } },
                        axisLabel: { color: textColor }
                    },
                    yAxis: {
                        type: 'value',
                        inverse: true, // Lower number = higher rank
                        splitLine: { lineStyle: { color: splitLineColor } },
                        axisLabel: { color: textColor }
                    },
                    series: [{
                        name: 'Rank',
                        type: 'line',
                        smooth: true,
                        data: trendRanks,
                        lineStyle: { width: 3, color: '#10b981' },
                        itemStyle: { color: '#10b981' },
                        areaStyle: {
                            color: new echarts.graphic.LinearGradient(0, 0, 0, 1, [
                                { offset: 0, color: 'rgba(16, 185, 129, 0.35)' },
                                { offset: 1, color: 'rgba(16, 185, 129, 0.0)' }
                            ])
                        }
                    }]
                });
            }

            // 2. College Contribution Doughnut Chart
            const collegeElem = document.getElementById('collegeChart');
            if (collegeElem) {
                const collegeChart = echarts.init(collegeElem);
                chartInstances.push(collegeChart);
                collegeChart.setOption({
                    backgroundColor: 'transparent',
                    tooltip: {
                        trigger: 'item',
                        backgroundColor: tooltipBg,
                        borderColor: tooltipBorder,
                        textStyle: { color: tooltipText },
                        formatter: params => `${params.name}<br/><strong>${Number(params.value).toLocaleString(undefined, { maximumFractionDigits: 2 })}</strong> (${params.percent}%)`
                    },
                    legend: {
                        orient: 'vertical',
                        right: '1%',
                        top: 'middle',
                        width: '42%',
                        type: 'scroll',
                        textStyle: { color: textColor, fontSize: 11 },
                        formatter: name => String(name).length > 24 ? `${String(name).slice(0, 24)}...` : name
                    },
                    series: [{
                        type: 'pie',
                        radius: ['45%', '70%'],
                        center: ['32%', '50%'],
                        label: {
                            show: true,
                            color: textColor,
                            fontSize: 10,
                            formatter: params => {
                                const name = String(params.name || 'College');
                                return name.length > 18 ? `${name.slice(0, 18)}...` : name;
                            }
                        },
                        labelLine: { show: true, length: 10, length2: 8 },
                        itemStyle: {
                            borderRadius: 6,
                            borderColor: isDark ? '#1f2937' : '#ffffff',
                            borderWidth: 2
                        },
                        data: collegePieData
                    }]
                });
            }

            // 3. Category Breakdown Mini Bar Charts
            breakdownSections.forEach((section, idx) => {
                const elem = document.getElementById('breakdownChart' + idx);
                if (!elem) return;
                const chart = echarts.init(elem);
                chartInstances.push(chart);
                chart.setOption({
                    backgroundColor: 'transparent',
                    tooltip: {
                        trigger: 'axis',
                        axisPointer: { type: 'shadow' },
                        backgroundColor: tooltipBg,
                        borderColor: tooltipBorder,
                        textStyle: { color: tooltipText },
                        formatter: function(params) {
                            const dataIndex = params[0].dataIndex;
                            return `${section.labels[dataIndex]}<br/>Rank/Score: <b>${section.displays[dataIndex]}</b>`;
                        }
                    },
                    grid: { left: '3%', right: '5%', bottom: '3%', top: '5%', containLabel: true },
                    xAxis: {
                        type: 'value',
                        inverse: true,
                        splitLine: { lineStyle: { color: splitLineColor } },
                        axisLabel: { color: textColor, fontSize: 10 }
                    },
                    yAxis: {
                        type: 'category',
                        data: section.labels,
                        axisLine: { lineStyle: { color: splitLineColor } },
                        axisLabel: {
                            color: textColor,
                            fontSize: 10,
                            formatter: function(val) {
                                return val.length > 15 ? val.slice(0, 15) + '...' : val;
                            }
                        }
                    },
                    series: [{
                        type: 'bar',
                        data: section.values,
                        itemStyle: {
                            color: '#10b981',
                            borderRadius: [4, 0, 0, 4]
                        }
                    }]
                });
            });
        }

        // Live Filters
        const canManagePublishedGraphs = <?= json_encode(($_SESSION['role'] ?? null) === 'admin') ?>;

        function renderPublishedScannerGraphs(graphs) {
            const grid = document.getElementById('scannerPublishedGraphsGrid');
            const empty = document.getElementById('scannerPublishedGraphsEmpty');
            const count = document.getElementById('publishedGraphCount');
            if (!grid || !count) return;

            grid.querySelectorAll('.scanner-published-card').forEach(el => el.remove());
            if (!Array.isArray(graphs) || graphs.length === 0) {
                if (empty) empty.style.display = '';
                count.innerHTML = '<i class="fa-solid fa-circle-info mr-1"></i> 0 published graphs';
                return;
            }
            if (empty) empty.style.display = 'none';
            count.innerHTML = `<i class="fa-solid fa-circle-check mr-1"></i> ${graphs.length} published graph${graphs.length === 1 ? '' : 's'}`;

            const isDark = document.documentElement.classList.contains('dark');
            const textColor = isDark ? '#9ca3af' : '#4b5563';
            const splitLineColor = isDark ? '#374151' : '#f3f4f6';
            const tooltipBg = isDark ? '#1f2937' : '#ffffff';
            const tooltipBorder = isDark ? '#374151' : '#e5e7eb';
            const tooltipText = isDark ? '#f9fafb' : '#111827';

            graphs.forEach((graph, index) => {
                const card = document.createElement('div');
                card.className = 'scanner-published-card bg-white dark:bg-gray-800 p-6 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm';
                const chartId = `scannerPublishedChart_${index}`;
                const labels = Array.isArray(graph.labels) ? graph.labels.map(v => String(v ?? '')) : [];
                const values = Array.isArray(graph.values_data) ? graph.values_data.map(v => {
                    const n = Number(v);
                    return Number.isFinite(n) ? n : 0;
                }) : [];
                const type = String(graph.chart_type || 'bar').toLowerCase();
                const source = graph.source_file_name ? `Source: ${graph.source_file_name}` : 'Source: IRIS Scanner';
                card.innerHTML = `
                    <div class="flex items-start justify-between gap-4 pb-4 border-b border-gray-100 dark:border-gray-700">
                        <div class="min-w-0">
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center">
                                <i class="fa-solid fa-chart-line text-emerald-500 mr-2"></i>${escapeHtmlDashboard(graph.title || 'Published Observatory Chart')}
                            </h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">${escapeHtmlDashboard(source)}</p>
                        </div>
                        <div class="shrink-0 flex items-center gap-2">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-emerald-100 text-emerald-800 dark:bg-emerald-900/60 dark:text-emerald-300">APPROVED</span>
                            ${canManagePublishedGraphs ? `<button type="button" class="published-graph-delete inline-flex items-center px-2.5 py-1.5 rounded-lg text-xs font-semibold text-red-700 bg-red-50 border border-red-200 hover:bg-red-100 dark:text-red-300 dark:bg-red-900/30 dark:border-red-800" data-graph-id="${escapeHtmlDashboard(graph.id)}" title="Remove this published chart"><i class="fa-solid fa-trash mr-1" aria-hidden="true"></i> Delete</button>` : ''}
                        </div>
                    </div>
                    <div id="${chartId}" class="w-full h-72 pt-4"></div>`;
                grid.appendChild(card);

                card.querySelector('.published-graph-delete')?.addEventListener('click', async event => {
                    const button = event.currentTarget;
                    if (!confirm(`Remove "${graph.title || 'this published chart'}" from the Observatory?`)) return;
                    button.disabled = true;
                    try {
                        const response = await fetch('<?= e(base_url('api/iris.php')) ?>?resource=graphs&id=' + encodeURIComponent(graph.id), { method: 'DELETE', headers: { 'Accept': 'application/json' } });
                        const payload = await response.json().catch(() => ({}));
                        if (!response.ok) throw new Error(payload.error || 'Unable to delete published chart.');
                        loadPublishedScannerGraphs();
                    } catch (error) {
                        button.disabled = false;
                        alert(error.message);
                    }
                });

                const elem = document.getElementById(chartId);
                if (!elem) return;
                const chart = echarts.init(elem);
                chartInstances.push(chart);
                const circular = ['pie', 'doughnut', 'polararea'].includes(type);
                const horizontal = type === 'bar' && String(graph.orientation || '').toLowerCase() === 'horizontal';
                const semanticRank = graph.rank_semantic === true;
                const reverse = graph.value_axis_reversed === true && !semanticRank;
                const axisMin = graph.value_axis_min !== null ? Number(graph.value_axis_min) : undefined;
                const axisMax = graph.value_axis_max !== null ? Number(graph.value_axis_max) : undefined;

                if (circular) {
                    const pieType = type === 'polararea' ? 'pie' : 'pie';
                    chart.setOption({
                        backgroundColor: 'transparent',
                        tooltip: { trigger: 'item', backgroundColor: tooltipBg, borderColor: tooltipBorder, textStyle: { color: tooltipText }, formatter: '{b}: {c} ({d}%)' },
                        legend: { type: 'scroll', bottom: 0, textStyle: { color: textColor, fontSize: 11 } },
                        series: [{
                            type: pieType,
                            radius: type === 'doughnut' ? ['45%', '70%'] : type === 'polararea' ? ['15%', '70%'] : '65%',
                            center: ['50%', '45%'],
                            data: labels.map((label, i) => ({ name: label || `Item ${i + 1}`, value: values[i] ?? 0 })),
                            itemStyle: { borderColor: isDark ? '#1f2937' : '#ffffff', borderWidth: 2 }
                        }]
                    });
                } else {
                    const seriesData = values;
                    const option = {
                        backgroundColor: 'transparent',
                        tooltip: {
                            trigger: 'axis',
                            axisPointer: { type: 'shadow' },
                            backgroundColor: tooltipBg,
                            borderColor: tooltipBorder,
                            textStyle: { color: tooltipText }
                        },
                        grid: { left: '4%', right: '4%', bottom: labels.length > 7 ? '15%' : '6%', top: '8%', containLabel: true },
                        xAxis: horizontal ? { type: 'value', min: axisMin, max: axisMax, inverse: reverse, splitLine: { lineStyle: { color: splitLineColor } }, axisLabel: { color: textColor } } : { type: 'category', data: labels, axisLabel: { color: textColor, rotate: labels.length > 6 ? 35 : 0 }, axisLine: { lineStyle: { color: splitLineColor } } },
                        yAxis: horizontal ? { type: 'category', data: labels, axisLabel: { color: textColor }, axisLine: { lineStyle: { color: splitLineColor } } } : { type: 'value', min: axisMin, max: axisMax, inverse: reverse || semanticRank, splitLine: { lineStyle: { color: splitLineColor } }, axisLabel: { color: textColor } },
                        series: [{ type: type === 'line' ? 'line' : 'bar', smooth: type === 'line', data: seriesData, itemStyle: { color: '#10b981', borderRadius: type === 'bar' ? [4, 4, 0, 0] : undefined }, lineStyle: type === 'line' ? { width: 3, color: '#10b981' } : undefined }]
                    };
                    chart.setOption(option);
                }
            });
        }

        function escapeHtmlDashboard(value) {
            return String(value ?? '').replace(/[&<>'"]/g, ch => ({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#39;','"':'&quot;'}[ch]));
        }

        function loadPublishedScannerGraphs() {
            fetch('<?= e(base_url('api/dashboard_graphs.php')) ?>', { headers: { 'Accept': 'application/json' } })
                .then(res => {
                    if (!res.ok) throw new Error('Published graph request failed');
                    return res.json();
                })
                .then(data => renderPublishedScannerGraphs(data.graphs || []))
                .catch(() => {
                    const count = document.getElementById('publishedGraphCount');
                    const empty = document.getElementById('scannerPublishedGraphsEmpty');
                    if (count) count.innerHTML = '<i class="fa-solid fa-triangle-exclamation mr-1"></i> Unable to load published graphs';
                    if (empty) empty.innerHTML = '<i class="fa-solid fa-triangle-exclamation text-amber-500 text-lg mr-1"></i> Published scanner graphs could not be loaded.';
                });
        }

        function filterPrograms() {
            const q = document.getElementById('programSearch').value.toLowerCase();
            const rows = document.querySelectorAll('#programTable tbody tr');
            let visibleCount = 0;
            rows.forEach(r => {
                const text = r.textContent.toLowerCase();
                const match = text.includes(q);
                r.style.display = match ? '' : 'none';
                if (match) visibleCount++;
            });
            document.getElementById('programCountLabel').textContent = `Showing ${visibleCount} programs`;
        }

        function filterAccred() {
            const q = document.getElementById('accredSearch').value.toLowerCase();
            const rows = document.querySelectorAll('#accredTable tbody tr');
            rows.forEach(r => {
                r.style.display = r.textContent.toLowerCase().includes(q) ? '' : 'none';
            });
        }

        // AI Summary Fetch
        function loadSummary() {
            const summaryEl = document.getElementById('ai-summary');
            const regenBtn = document.getElementById('regenBtn');
            
            summaryEl.innerHTML = `
                <div class="animate-pulse space-y-2">
                    <div class="h-4 bg-emerald-200/50 dark:bg-gray-700 rounded w-3/4"></div>
                    <div class="h-4 bg-emerald-200/50 dark:bg-gray-700 rounded w-5/6"></div>
                    <div class="h-4 bg-emerald-200/50 dark:bg-gray-700 rounded w-1/2"></div>
                </div>
            `;
            if (regenBtn) regenBtn.disabled = true;

            fetch('<?= e(base_url('api/summary.php')) ?>')
                .then(res => res.json())
                .then(data => {
                    summaryEl.textContent = data.summary;
                })
                .catch(() => {
                    summaryEl.textContent = "Unable to fetch automated AI summary at this moment.";
                })
                .finally(() => {
                    if (regenBtn) regenBtn.disabled = false;
                });
        }

        document.addEventListener('DOMContentLoaded', () => {
            renderAllCharts();
            loadPublishedScannerGraphs();
            loadSummary();
            window.addEventListener('resize', () => {
                chartInstances.forEach(c => c && c.resize());
            });
        });
    </script>
</body>
</html>
